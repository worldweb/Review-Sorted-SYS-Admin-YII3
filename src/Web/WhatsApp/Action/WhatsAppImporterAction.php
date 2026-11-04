<?php

declare(strict_types=1);

namespace App\Web\WhatsApp\Action;

use App\Domain\User\UserRepositoryInterface;
use App\Domain\WhatsApp\WhatsAppRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use App\Shared\CsvImportService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * WhatsAppImporterAction.
 * Processes CSV upload, parses, normalizes phone numbers to E.164, and imports them.
 * Replicates frontend\controllers\WhatsappController::actionImporter (POST) in Yii 3.
 */
final class WhatsAppImporterAction
{
    public function __construct(
        private WhatsAppRepositoryInterface $whatsappRepo,
        private UserRepositoryInterface $userRepo,
        private TenantContextProviderInterface $tenantContextProvider,
        private CsvImportService $csvImportService,
        private SessionInterface $session,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        if (!$currentUser->isAdmin() && !$currentUser->isAccountAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: You are not authorized to import WhatsApp contacts.');
            return $response;
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() !== 'POST') {
            return $this->redirect(\App\Environment::url('/whatsapp/importer'));
        }

        $targetAccountId = $context->getScopedAccountId() ?? ($context->getAccountId() ?? $currentUser->getAccountId());
        $parsedBody = (array) $request->getParsedBody();
        if ($context->isUnrestricted() && isset($parsedBody['account_id']) && (int) $parsedBody['account_id'] > 0) {
            $targetAccountId = (int) $parsedBody['account_id'];
        }

        if ($targetAccountId === null || $targetAccountId <= 0) {
            $flash->add('error', 'Cannot import contacts: no tenant account is active.');
            return $this->redirect(\App\Environment::url('/whatsapp/importer'));
        }

        $uploadedFiles = $request->getUploadedFiles();
        /** @var UploadedFileInterface|null $file */
        $file = $uploadedFiles['file'] ?? ($uploadedFiles['csv_file'] ?? null);

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $flash->add('error', 'Please select a CSV file to upload.');
            return $this->redirect(\App\Environment::url('/whatsapp/importer'));
        }

        $result = $this->csvImportService->parse($file, ['name', 'phone']);

        if ($result->hasHeaderError()) {
            $flash->add('error', (string) $result->getHeaderError());
            return $this->redirect(\App\Environment::url('/whatsapp/importer'));
        }

        $records = $result->getRecords();
        $validRows = [];
        $rowErrors = $result->getRowErrors();
        $rowNum = 1;

        foreach ($records as $record) {
            $rowNum++;
            $name = trim((string) ($record['name'] ?? ''));
            $phone = trim((string) ($record['phone'] ?? ''));

            if ($name === '') {
                $rowErrors[] = ['row' => $rowNum, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }

            if ($phone === '') {
                $rowErrors[] = ['row' => $rowNum, 'field' => 'phone', 'message' => 'Phone number is required'];
                continue;
            }

            // Normalize phone number per E.164
            $cleaned = preg_replace('/[^0-9+]/', '', $phone) ?? '';
            if (str_starts_with($cleaned, '04') && strlen($cleaned) === 10) {
                $normalizedPhone = '+61' . substr($cleaned, 1);
            } elseif (!str_starts_with($cleaned, '+') && strlen($cleaned) >= 8) {
                $normalizedPhone = '+' . $cleaned;
            } else {
                $normalizedPhone = $cleaned;
            }

            if (strlen(preg_replace('/[^0-9]/', '', $normalizedPhone) ?? '') < 7) {
                $rowErrors[] = ['row' => $rowNum, 'field' => 'phone', 'message' => sprintf('Invalid phone number "%s"', $phone)];
                continue;
            }

            $validRows[] = [
                'name' => $name,
                'phone' => $normalizedPhone,
            ];
        }

        if (!empty($rowErrors)) {
            $firstErrors = array_slice($rowErrors, 0, 5);
            $msg = 'There were errors importing some rows: ' . implode('; ', array_map(static fn ($e): string => sprintf('Row %d: %s', $e['row'], $e['message']), $firstErrors));
            if (count($rowErrors) > 5) {
                $msg .= sprintf(' (and %d more)', count($rowErrors) - 5);
            }
            $flash->add('error', $msg);
        }

        if (!empty($validRows)) {
            try {
                $importedCount = $this->whatsappRepo->saveContactsBatch($validRows, $targetAccountId);
                $flash->add('success', sprintf('%d phone numbers imported successfully.', $importedCount));
            } catch (\Throwable $e) {
                $flash->add('error', 'Database error: ' . $e->getMessage());
            }
        }

        return $this->redirect(\App\Environment::url('/whatsapp/importer'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}

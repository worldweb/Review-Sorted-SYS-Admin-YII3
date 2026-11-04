<?php

declare(strict_types=1);

namespace App\Web\Email\Action;

use App\Domain\Email\EmailRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContextProviderInterface;
use App\Shared\CsvImportService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

/**
 * EmailImporterAction.
 * Processes CSV upload, parses and validates recipient contacts, and imports them.
 * Replicates frontend\controllers\EmailImporterController::actionIndex (POST) in Yii 3.
 */
final class EmailImporterAction
{
    public function __construct(
        private EmailRepositoryInterface $emailRepo,
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
            $response->getBody()->write('Forbidden: You are not authorized to import email contacts.');
            return $response;
        }

        $flash = new Flash($this->session);

        if ($request->getMethod() !== 'POST') {
            return $this->redirect(\App\Environment::url('/email/importer'));
        }

        $targetAccountId = $context->getScopedAccountId() ?? ($context->getAccountId() ?? $currentUser->getAccountId());
        $parsedBody = (array) $request->getParsedBody();
        if ($context->isUnrestricted() && isset($parsedBody['account_id']) && (int) $parsedBody['account_id'] > 0) {
            $targetAccountId = (int) $parsedBody['account_id'];
        }

        if ($targetAccountId === null || $targetAccountId <= 0) {
            $flash->add('error', 'Cannot import contacts: no tenant account is active.');
            return $this->redirect(\App\Environment::url('/email/importer'));
        }

        $uploadedFiles = $request->getUploadedFiles();
        /** @var UploadedFileInterface|null $file */
        $file = $uploadedFiles['file'] ?? ($uploadedFiles['csv_file'] ?? null);

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $flash->add('error', 'Please select a CSV file to upload.');
            return $this->redirect(\App\Environment::url('/email/importer'));
        }

        $result = $this->csvImportService->parse($file, ['name', 'email', 'type']);

        if ($result->hasHeaderError()) {
            $flash->add('error', (string) $result->getHeaderError());
            return $this->redirect(\App\Environment::url('/email/importer'));
        }

        $records = $result->getRecords();
        $validRows = [];
        $rowErrors = $result->getRowErrors();
        $rowNum = 1;

        foreach ($records as $record) {
            $rowNum++;
            $name = trim((string) ($record['name'] ?? ''));
            $email = trim((string) ($record['email'] ?? ''));
            $type = strtolower(trim((string) ($record['type'] ?? 'to')));

            if ($name === '') {
                $rowErrors[] = ['row' => $rowNum, 'field' => 'name', 'message' => 'Name is required'];
                continue;
            }

            if ($email === '') {
                $rowErrors[] = ['row' => $rowNum, 'field' => 'email', 'message' => 'Email is required'];
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = ['row' => $rowNum, 'field' => 'email', 'message' => sprintf('Invalid email address "%s"', $email)];
                continue;
            }

            if (!in_array($type, ['to', 'cc', 'bcc'], true)) {
                $type = 'to';
            }

            $validRows[] = [
                'name' => $name,
                'email' => $email,
                'type' => $type,
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
                $importedCount = $this->emailRepo->saveContactsBatch($validRows, $targetAccountId);
                $flash->add('success', sprintf('%d emails imported successfully.', $importedCount));
            } catch (\Throwable $e) {
                $flash->add('error', 'Database error: ' . $e->getMessage());
            }
        }

        return $this->redirect(\App\Environment::url('/email/importer'));
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}

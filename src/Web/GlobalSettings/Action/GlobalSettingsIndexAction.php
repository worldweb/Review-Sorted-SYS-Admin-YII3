<?php

declare(strict_types=1);

namespace App\Web\GlobalSettings\Action;

use App\Domain\Setting\SettingRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Migration\MigrationSyncService;
use App\Infrastructure\Persistence\Schema\DatabaseHealthService;
use App\Infrastructure\Persistence\Schema\SchemaSynchronizationService;
use App\Security\Authorization\RolePermissionService;
use App\Security\TenantContextProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class GlobalSettingsIndexAction
{
    public function __construct(
        private SettingRepositoryInterface $settingRepo,
        private UserRepositoryInterface $userRepo,
        private RolePermissionService $rolePermissionService,
        private TenantContextProviderInterface $tenantContextProvider,
        private SessionInterface $session,
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private ?CsrfTokenInterface $csrfToken = null,
        private ?DatabaseHealthService $dbHealthService = null,
        private ?MigrationSyncService $migrationSyncService = null,
        private ?SchemaSynchronizationService $schemaSyncService = null,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->tenantContextProvider->get();
        if ($context->isGuest()) {
            return $this->redirect(\App\Environment::url('/login'));
        }

        $currentUser = $this->userRepo->findById($context->getUserId(), $context);
        if ($currentUser === null || !$currentUser->isAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Forbidden: Only platform administrators can access Global Settings.');
            return $response;
        }

        $flash = new Flash($this->session);
        $queryParams = $request->getQueryParams();
        $section = (string) ($queryParams['section'] ?? 'general');
        $allowedSections = ['general', 'security', 'reviews', 'media', 'subscriptions', 'queue', 'roles', 'database_migration'];
        if (!in_array($section, $allowedSections, true)) {
            $section = 'general';
        }

        // Selected role for Role Permissions tab
        $targetRole = (string) ($queryParams['target_role'] ?? 'account_admin');
        if (!in_array($targetRole, ['account_admin', 'worker', 'admin'], true)) {
            $targetRole = 'account_admin';
        }

        if ($request->getMethod() === 'POST') {
            $post = (array) $request->getParsedBody();

            // Handle Database Migration Execution (PART 2)
            if (($post['action'] ?? '') === 'run_migration') {
                if ($this->migrationSyncService !== null) {
                    $migResult = $this->migrationSyncService->applyPendingMigrations();
                    if (!empty($migResult['error'])) {
                        $flash->set('error', 'Migration execution failed: ' . $migResult['error']);
                    } elseif (($migResult['appliedCount'] ?? 0) > 0) {
                        $appliedNames = implode(', ', (array) ($migResult['applied'] ?? []));
                        $flash->set('success', 'Successfully applied ' . (int)$migResult['appliedCount'] . ' migration(s): ' . $appliedNames);
                    } else {
                        $flash->set('info', $migResult['message'] ?? 'No pending migrations were applied.');
                    }
                } else {
                    $flash->set('error', 'Migration service is currently unavailable.');
                }

                return $this->redirect(\App\Environment::url('/global-settings?section=database_migration'));
            }

            // Handle Database Schema Synchronization (PART 1: RS-SYNC-005)
            if (($post['action'] ?? '') === 'sync_database') {
                $confirmBackup = (string) ($post['confirm_backup'] ?? '0');
                if ($confirmBackup !== '1') {
                    $flash->set('error', 'Database synchronization cancelled: You must acknowledge the pre-sync backup and DDL recovery notice before applying changes.');
                    return $this->redirect(\App\Environment::url('/global-settings?section=database_migration'));
                }

                if ($this->schemaSyncService !== null) {
                    $report = $this->schemaSyncService->executePlan();
                    if (!empty($report['error'])) {
                        $flash->set('error', (string) $report['error']);
                    } elseif (($report['failedCount'] ?? 0) > 0) {
                        $flash->set('error', 'Database schema synchronization encountered ' . (int)$report['failedCount'] . ' error(s). Check the execution report below.');
                    } elseif (($report['successCount'] ?? 0) > 0) {
                        $flash->set('success', 'Database schema synchronized successfully! Applied ' . (int)$report['successCount'] . ' safe operation(s).');
                    } else {
                        $flash->set('info', 'Database schema is already fully synchronized with the expected manifest. No changes were applied.');
                    }
                } else {
                    $flash->set('error', 'Schema synchronization service is currently unavailable.');
                }

                return $this->redirect(\App\Environment::url('/global-settings?section=database_migration'));
            }

            // Handle Role Permissions Save
            if (isset($post['RolePermissionsForm'])) {
                $roleFormData = (array) $post['RolePermissionsForm'];
                $submittedRole = (string) ($roleFormData['target_role'] ?? $targetRole);
                if (in_array($submittedRole, ['account_admin', 'worker', 'admin'], true)) {
                    $targetRole = $submittedRole;
                }
                $selectedFeatures = (array) ($roleFormData['selected_features'] ?? []);
                $result = $this->rolePermissionService->updateRolePermissions($targetRole, $selectedFeatures, $currentUser);

                if (!empty($result['success'])) {
                    $flash->set('success', $result['message']);
                } else {
                    $flash->set('info', $result['message']);
                }

                return $this->redirect(\App\Environment::url('/global-settings?section=roles&target_role=' . urlencode($targetRole)));
            }

            // Handle General/Security Settings Save
            $settingsData = (array) ($post['settings'] ?? []);
            $allSuccess = true;
            foreach ($settingsData as $key => $val) {
                if (!$this->settingRepo->set((string) $key, (string) $val)) {
                    $allSuccess = false;
                }
            }

            if ($allSuccess && !empty($settingsData)) {
                $flash->set('success', 'Global settings updated successfully.');
            } else {
                $flash->set('error', 'Failed to update settings.');
            }

            return $this->redirect(\App\Environment::url('/global-settings?section=' . urlencode($section)));
        }

        $allSettings = $this->settingRepo->getAll();
        $roleMatrix = $this->rolePermissionService->getRoleMatrix($targetRole);
        $roleOptions = RolePermissionService::getEditableRoleOptions();

        $dbHealth = [];
        $schemaReport = [];
        $migrationStatus = [];
        $syncPlan = [];
        $lastExecutionReport = null;

        if ($section === 'database_migration') {
            if ($this->dbHealthService !== null) {
                $dbHealth = $this->dbHealthService->getConnectionHealth();
                $schemaReport = $this->dbHealthService->inspectSchema();
            }
            if ($this->migrationSyncService !== null) {
                $migrationStatus = $this->migrationSyncService->getMigrationStatus();
            }
            if ($this->schemaSyncService !== null) {
                $syncPlan = $this->schemaSyncService->generatePlan();
                $lastExecutionReport = $this->schemaSyncService->getLastExecutionReport();
            }
        }

        return $this->viewRenderer
            ->withViewPath('@src/Web/GlobalSettings/views')
            ->render('index', [
                'section' => $section,
                'settings' => $allSettings,
                'targetRole' => $targetRole,
                'roleMatrix' => $roleMatrix,
                'roleOptions' => $roleOptions,
                'currentUser' => $currentUser,
                'flash' => $flash,
                'csrfToken' => $this->csrfToken?->getValue() ?? '',
                'dbHealth' => $dbHealth,
                'schemaReport' => $schemaReport,
                'migrationStatus' => $migrationStatus,
                'syncPlan' => $syncPlan,
                'lastExecutionReport' => $lastExecutionReport,
            ]);
    }

    private function redirect(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse(302)->withHeader('Location', $url);
    }
}

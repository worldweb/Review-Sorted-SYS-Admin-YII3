<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * MigrationSyncService.
 *
 * Discovers migration files in the codebase, reads actual database migration history
 * from the `migration` table, and synchronizes status (applied, pending, missing files).
 *
 * Supports both modern Yii3 and standard Yii migration naming conventions:
 * - Modern: M261116000001AddSocialPostStudioRbac.php
 * - Standard: m260930_100000_create_location_group_service_config_table.php
 *
 * Never performs DDL/DML mutations or runs migrations during status inspection.
 */
final class MigrationSyncService
{
    public const STATUS_UP_TO_DATE = 'UP_TO_DATE';
    public const STATUS_PENDING_MIGRATIONS = 'PENDING_MIGRATIONS';
    public const STATUS_HISTORY_MISSING_FILES = 'HISTORY_MISSING_FILES';
    public const STATUS_TABLE_MISSING = 'MIGRATION_TABLE_MISSING';
    public const STATUS_DATABASE_ERROR = 'DATABASE_CONNECTION_ERROR';

    private const MIGRATION_MUTEX_NAME = 'rs_migration_execution_mutex';
    private const MIGRATION_MUTEX_TIMEOUT = 5;

    /** @var resource|null */
    private $migrationFileLockHandle = null;

    public function __construct(
        private ConnectionInterface $db,
        private ?string $migrationsDir = null,
    ) {
    }

    /**
     * Inspects actual database migration status dynamically.
     *
     * @return array{
     *     status: string,
     *     statusMessage: string,
     *     appliedCount: int,
     *     pendingCount: int,
     *     missingFileCount: int,
     *     conflictCount: int,
     *     pendingMigrations: list<array>,
     *     appliedMigrations: list<array>,
     *     missingFileMigrations: list<array>,
     *     latestApplied: ?string,
     *     latestAvailable: ?string,
     *     error: ?string
     * }
     */
    public function getMigrationStatus(): array
    {
        $defaultResult = [
            'status' => self::STATUS_DATABASE_ERROR,
            'statusMessage' => 'Database connection failed.',
            'appliedCount' => 0,
            'pendingCount' => 0,
            'missingFileCount' => 0,
            'conflictCount' => 0,
            'pendingMigrations' => [],
            'appliedMigrations' => [],
            'missingFileMigrations' => [],
            'latestApplied' => null,
            'latestAvailable' => null,
            'error' => null,
        ];

        try {
            $this->db->open();
        } catch (Throwable $e) {
            $defaultResult['error'] = 'Could not connect to database: ' . $e->getMessage();
            return $defaultResult;
        }

        // 1. Check if migration table exists
        $migrationTableName = 'migration';
        try {
            $tableSchema = $this->db->getSchema()->getTableSchema($migrationTableName);
            if ($tableSchema === null) {
                $defaultResult['status'] = self::STATUS_TABLE_MISSING;
                $defaultResult['statusMessage'] = 'Migration history table ("migration") does not exist in the connected database. The database may be fresh or uninitialized.';
                $defaultResult['error'] = 'Migration table missing.';
                return $defaultResult;
            }
        } catch (Throwable $e) {
            $defaultResult['status'] = self::STATUS_DATABASE_ERROR;
            $defaultResult['statusMessage'] = 'Failed to inspect migration table: ' . $e->getMessage();
            $defaultResult['error'] = $e->getMessage();
            return $defaultResult;
        }

        // 2. Fetch all applied migrations from database
        $appliedMap = [];
        $appliedList = [];
        try {
            $rows = (new Query($this->db))
                ->select(['version', 'apply_time'])
                ->from($migrationTableName)
                ->orderBy(['apply_time' => SORT_DESC, 'version' => SORT_DESC])
                ->all();

            foreach ($rows as $row) {
                $version = (string) $row['version'];
                $applyTime = (int) $row['apply_time'];
                $appliedMap[$version] = $applyTime;
                // Case-insensitive mapping for compatibility
                $appliedMap[strtolower($version)] = $applyTime;

                $appliedList[] = [
                    'version' => $version,
                    'applyTime' => $applyTime,
                    'applyDate' => $applyTime > 0 ? date('Y-m-d H:i:s', $applyTime) : 'Initial Baseline',
                ];
            }
        } catch (Throwable $e) {
            $defaultResult['status'] = self::STATUS_DATABASE_ERROR;
            $defaultResult['statusMessage'] = 'Failed to query migration history: ' . $e->getMessage();
            $defaultResult['error'] = $e->getMessage();
            return $defaultResult;
        }

        // 3. Discover migration files on disk
        $migrationDirs = $this->resolveMigrationDirectories();
        $discoveredFiles = [];

        foreach ($migrationDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.php');
            if ($files === false) {
                continue;
            }

            foreach ($files as $filePath) {
                $baseName = basename($filePath, '.php');

                // Check naming patterns
                $matched = false;
                $timestamp = '';
                $description = '';

                // Pattern A: Modern Yii3 format: M261116000001AddSocialPostStudioRbac.php
                if (preg_match('/^M(\d{12})(.*)$/', $baseName, $matches)) {
                    $matched = true;
                    $timestamp = $matches[1];
                    $description = $this->humanizeName($matches[2]);
                }
                // Pattern B: Standard Yii format: m260930_100000_create_location_group_service_config_table.php
                elseif (preg_match('/^m(\d{6}_\d{6})_(.*)$/i', $baseName, $matches)) {
                    $matched = true;
                    $timestamp = $matches[1];
                    $description = $this->humanizeName($matches[2]);
                }

                if ($matched) {
                    $discoveredFiles[$baseName] = [
                        'version' => $baseName,
                        'fileName' => basename($filePath),
                        'filePath' => $filePath,
                        'timestamp' => $timestamp,
                        'description' => $description,
                        'formattedDate' => $this->parseTimestampToDate($timestamp),
                        'isDestructive' => $this->detectDestructiveOps($filePath),
                    ];
                }
            }
        }

        // Sort discovered files chronologically
        ksort($discoveredFiles);

        // 4. Compare Discovered Files vs DB History
        $pendingMigrations = [];
        $latestAvailable = null;

        foreach ($discoveredFiles as $version => $fileInfo) {
            $latestAvailable = $version;
            $isApplied = isset($appliedMap[$version]) || isset($appliedMap[strtolower($version)]);

            if (!$isApplied) {
                $fileInfo['status'] = 'PENDING';
                $fileInfo['riskLevel'] = $fileInfo['isDestructive'] ? 'POTENTIALLY_DESTRUCTIVE' : 'STANDARD';
                $pendingMigrations[] = $fileInfo;
            }
        }

        // 5. Detect DB history entries without corresponding disk files
        $missingFileMigrations = [];
        foreach ($appliedList as $appliedItem) {
            $ver = $appliedItem['version'];
            if (!isset($discoveredFiles[$ver]) && !isset($discoveredFiles[strtolower($ver)])) {
                $missingFileMigrations[] = $appliedItem;
            }
        }

        $appliedCount = count($appliedList);
        $pendingCount = count($pendingMigrations);
        $missingFileCount = count($missingFileMigrations);

        // Determine status
        if ($pendingCount > 0) {
            $status = self::STATUS_PENDING_MIGRATIONS;
            $statusMessage = "{$pendingCount} pending migration(s) detected. The database schema requires updating.";
        } elseif ($missingFileCount > 0 && $appliedCount > 0) {
            $status = self::STATUS_UP_TO_DATE;
            $statusMessage = "All {$appliedCount} applied migrations are active in the database. (Note: {$missingFileCount} historical migrations were applied in earlier development environments).";
        } else {
            $status = self::STATUS_UP_TO_DATE;
            $statusMessage = "All migrations are reconciled and up to date ({$appliedCount} applied).";
        }

        return [
            'status' => $status,
            'statusMessage' => $statusMessage,
            'appliedCount' => $appliedCount,
            'pendingCount' => $pendingCount,
            'missingFileCount' => $missingFileCount,
            'conflictCount' => 0,
            'pendingMigrations' => $pendingMigrations,
            'appliedMigrations' => array_slice($appliedList, 0, 15), // latest 15 applied for view
            'missingFileMigrations' => $missingFileMigrations,
            'latestApplied' => $appliedList[0]['version'] ?? null,
            'latestAvailable' => $latestAvailable,
            'error' => null,
        ];
    }

    /**
     * Acquires server-side mutex lock for migration execution.
     */
    public function acquireMigrationLock(): bool
    {
        $lockFile = dirname(__DIR__, 4) . '/runtime/migration_exec.lock';
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = @fopen($lockFile, 'c+');
        if (!$fp || !@flock($fp, LOCK_EX | LOCK_NB)) {
            if ($fp) {
                @fclose($fp);
            }
            return false;
        }
        $this->migrationFileLockHandle = $fp;

        try {
            $val = (int) $this->db->createCommand(
                'SELECT GET_LOCK(:name, :timeout)',
                [':name' => self::MIGRATION_MUTEX_NAME, ':timeout' => self::MIGRATION_MUTEX_TIMEOUT]
            )->queryScalar();

            if ($val !== 1) {
                $this->releaseMigrationLock();
                return false;
            }
        } catch (Throwable) {
            // Keep file lock active
        }

        return true;
    }

    /**
     * Releases server-side mutex lock for migration execution.
     */
    public function releaseMigrationLock(): void
    {
        try {
            $this->db->createCommand(
                'SELECT RELEASE_LOCK(:name)',
                [':name' => self::MIGRATION_MUTEX_NAME]
            )->execute();
        } catch (Throwable) {
            // Ignore
        }

        if ($this->migrationFileLockHandle !== null) {
            @flock($this->migrationFileLockHandle, LOCK_UN);
            @fclose($this->migrationFileLockHandle);
            $this->migrationFileLockHandle = null;
        }
    }

    /**
     * Safely executes all pending migrations in strict chronological order
     * and registers them in the `migration` history table.
     *
     * @return array{
     *     success: bool,
     *     appliedCount: int,
     *     applied: list<string>,
     *     message: string,
     *     error: ?string
     * }
     */
    public function applyPendingMigrations(): array
    {
        if (!$this->acquireMigrationLock()) {
            return [
                'success' => false,
                'appliedCount' => 0,
                'applied' => [],
                'message' => 'Another migration execution is currently in progress.',
                'error' => 'Another migration execution is currently in progress. Please wait a few moments and try again.',
            ];
        }

        try {
            $status = $this->getMigrationStatus();
            if ($status['status'] === self::STATUS_DATABASE_ERROR) {
                return [
                    'success' => false,
                    'appliedCount' => 0,
                    'applied' => [],
                    'message' => 'Database connection failed.',
                    'error' => $status['error'] ?? 'Database connection error.',
                ];
            }

            if ($status['pendingCount'] === 0 || empty($status['pendingMigrations'])) {
                return [
                    'success' => true,
                    'appliedCount' => 0,
                    'applied' => [],
                    'message' => 'No pending migrations to apply. Database is already up to date.',
                    'error' => null,
                ];
            }

            $applied = [];

            foreach ($status['pendingMigrations'] as $migInfo) {
                $version = (string) $migInfo['version'];
                $filePath = (string) $migInfo['filePath'];

                if (!file_exists($filePath)) {
                    return [
                        'success' => false,
                        'appliedCount' => count($applied),
                        'applied' => $applied,
                        'message' => "Migration file missing: {$filePath}",
                        'error' => "Migration file missing on disk: {$filePath}",
                    ];
                }

                // Security: Validate file path is strictly within approved migration directories
                $realPath = realpath($filePath);
                $isAllowedPath = false;
                foreach ($this->resolveMigrationDirectories() as $allowedDir) {
                    $realAllowed = realpath($allowedDir);
                    if ($realAllowed !== false && $realPath !== false && str_starts_with($realPath, $realAllowed)) {
                        $isAllowedPath = true;
                        break;
                    }
                }

                if (!$isAllowedPath) {
                    return [
                        'success' => false,
                        'appliedCount' => count($applied),
                        'applied' => $applied,
                        'message' => "Untrusted migration file path: {$filePath}",
                        'error' => "Migration file rejected: path '{$filePath}' is outside approved migration directories.",
                    ];
                }

                // Concurrency defense: Re-verify version is not already applied in database
                $alreadyRecorded = (new Query($this->db))
                    ->from('migration')
                    ->where(['version' => $version])
                    ->exists();
                if ($alreadyRecorded) {
                    continue;
                }

                require_once $filePath;

                // Resolve class name
                $className = null;
                $candidates = [
                    'App\\Infrastructure\\Persistence\\Migration\\' . $version,
                    $version,
                    'App\\Migration\\' . $version,
                ];

                foreach ($candidates as $cand) {
                    if (class_exists($cand)) {
                        $className = $cand;
                        break;
                    }
                }

                if ($className === null) {
                    return [
                        'success' => false,
                        'appliedCount' => count($applied),
                        'applied' => $applied,
                        'message' => "Migration class for '{$version}' could not be resolved.",
                        'error' => "Migration class for '{$version}' not found in file '{$filePath}'.",
                    ];
                }

                // Instantiate migration
                $ref = new \ReflectionClass($className);
                $constructor = $ref->getConstructor();
                if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                    $instance = new $className($this->db);
                } else {
                    $instance = new $className();
                }

                // Execute safeUp() or up() following Yii conventions
                try {
                    if (method_exists($instance, 'safeUp')) {
                        $needsTx = method_exists($this->db, 'getTransaction') && $this->db->getTransaction() === null;
                        if ($needsTx) {
                            $tx = $this->db->beginTransaction();
                            try {
                                $instance->safeUp();
                                $tx->commit();
                            } catch (Throwable $txEx) {
                                $tx->rollBack();
                                throw $txEx;
                            }
                        } else {
                            $instance->safeUp();
                        }
                    } elseif (method_exists($instance, 'up')) {
                        $instance->up();
                    } else {
                        throw new \RuntimeException("Migration class '{$className}' must implement up() or safeUp() method.");
                    }

                    // Register migration in history table
                    $this->db->createCommand()->insert('migration', [
                        'version' => $version,
                        'apply_time' => time(),
                    ])->execute();

                    $applied[] = $version;
                } catch (Throwable $e) {
                    return [
                        'success' => false,
                        'appliedCount' => count($applied),
                        'applied' => $applied,
                        'message' => "Failed applying migration '{$version}': " . $e->getMessage(),
                        'error' => "Failed applying migration '{$version}': " . $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => true,
                'appliedCount' => count($applied),
                'applied' => $applied,
                'message' => 'Successfully applied ' . count($applied) . ' migration(s).',
                'error' => null,
            ];
        } finally {
            $this->releaseMigrationLock();
        }
    }

    /**
     * Resolves migration search directory paths.
     *
     * @return list<string>
     */
    private function resolveMigrationDirectories(): array
    {
        if ($this->migrationsDir !== null) {
            return [$this->migrationsDir];
        }

        $baseDir = dirname(__DIR__, 4); // Root of sys-yii3
        return [
            $baseDir . '/src/Infrastructure/Persistence/Migration',
            $baseDir . '/resources/migrations',
            $baseDir . '/migrations',
        ];
    }

    /**
     * Converts CamelCase or snake_case names into human-readable descriptions.
     */
    private function humanizeName(string $name): string
    {
        // Replace underscores with spaces
        $clean = str_replace('_', ' ', $name);
        // Insert space before capital letters in CamelCase
        $clean = (string) preg_replace('/([a-z])([A-Z])/', '$1 $2', $clean);
        return trim(ucfirst($clean));
    }

    /**
     * Parses migration timestamp string into human-readable date.
     */
    private function parseTimestampToDate(string $ts): string
    {
        // Pattern: 261116000001 (YYMMDDHHMMSS)
        if (preg_match('/^(\d{2})(\d{2})(\d{2})/', $ts, $m)) {
            $year = 2000 + (int) $m[1];
            $month = $m[2];
            $day = $m[3];
            return "{$year}-{$month}-{$day}";
        }
        return 'Unknown';
    }

    /**
     * Detects if migration file contains potentially destructive operations.
     */
    private function detectDestructiveOps(string $filePath): bool
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $destructiveKeywords = [
            'dropTable',
            'dropColumn',
            'dropForeignKey',
            'dropIndex',
            'truncate',
            'DROP TABLE',
            'DROP COLUMN',
            'DELETE FROM',
        ];

        foreach ($destructiveKeywords as $kw) {
            if (stripos($content, $kw) !== false) {
                return true;
            }
        }

        return false;
    }
}

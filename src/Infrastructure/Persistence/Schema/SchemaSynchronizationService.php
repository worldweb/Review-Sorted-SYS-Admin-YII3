<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Schema;

use App\Environment;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;

/**
 * SchemaSynchronizationService.
 *
 * Compares actual live database schema against the canonical ExpectedSchemaManifest
 * and generates a structured, risk-classified database synchronization plan:
 *
 * - Safe Additions (Create tables, add columns, add indexes, add foreign keys)
 * - Modifications (Safe type changes, size widening, default values)
 * - Destructive Changes (Drop tables, drop columns, drop indexes - NEVER auto-executed)
 * - Manual Review Required (Operations with potential data loss, truncation, PK conflicts, or referential mismatch)
 *
 * Provides safe, dependency-ordered, idempotent execution of approved safe operations.
 * Separated strictly from the migration execution workflow (preserves migration table accuracy).
 */
final class SchemaSynchronizationService
{
    public const RISK_SAFE_ADDITION = 'SAFE_ADDITION';
    public const RISK_MODIFICATION = 'MODIFICATION';
    public const RISK_DESTRUCTIVE = 'DESTRUCTIVE';
    public const RISK_MANUAL_REVIEW = 'MANUAL_REVIEW';

    private const MUTEX_NAME = 'rs_schema_sync_mutex';
    private const MUTEX_TIMEOUT_SECONDS = 5;
    private const MAX_HISTORY_RECORDS = 10;

    private DatabaseHealthService $healthService;
    private string $historyFile;
    private string $lockFile;
    /** @var resource|null */
    private $fileLockHandle = null;

    /** @var ?array<string, array<string, array{refTable: string, columns: array<string, string>, onDelete: string, onUpdate: string}>> */
    private ?array $dbForeignKeysCache = null;

    public function __construct(
        private ConnectionInterface $db,
        ?DatabaseHealthService $healthService = null,
        ?string $historyFile = null,
    ) {
        $this->healthService = $healthService ?? new DatabaseHealthService($this->db);
        $runtimeDir = dirname(__DIR__, 4) . '/runtime';
        $this->historyFile = $historyFile ?? ($runtimeDir . '/schema_sync_history.json');
        $this->lockFile = $runtimeDir . '/schema_sync.lock';
    }

    /**
     * Acquires server-side synchronization mutex lock to prevent concurrent executions.
     * Uses both non-blocking file locking (instant local protection) and MySQL GET_LOCK (database-level protection).
     */
    public function acquireLock(): bool
    {
        // 1. Attempt file-based non-blocking lock first for instant local collision protection
        $dir = dirname($this->lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = @fopen($this->lockFile, 'c+');
        if (!$fp || !@flock($fp, LOCK_EX | LOCK_NB)) {
            if ($fp) {
                @fclose($fp);
            }
            return false;
        }
        $this->fileLockHandle = $fp;

        // 2. Also acquire MySQL/MariaDB advisory lock for database-level protection
        try {
            $val = (int) $this->db->createCommand(
                'SELECT GET_LOCK(:name, :timeout)',
                [':name' => self::MUTEX_NAME, ':timeout' => self::MUTEX_TIMEOUT_SECONDS]
            )->queryScalar();

            if ($val !== 1) {
                $this->releaseLock();
                return false;
            }
        } catch (Throwable) {
            // If advisory lock throws, file lock is already acquired and active
        }

        return true;
    }

    /**
     * Releases server-side synchronization mutex lock.
     */
    public function releaseLock(): void
    {
        // 1. Release MySQL/MariaDB advisory lock
        try {
            $this->db->createCommand(
                'SELECT RELEASE_LOCK(:name)',
                [':name' => self::MUTEX_NAME]
            )->execute();
        } catch (Throwable) {
            // Ignore on cleanup
        }

        // 2. Release file-based lock if held
        if ($this->fileLockHandle !== null) {
            @flock($this->fileLockHandle, LOCK_UN);
            @fclose($this->fileLockHandle);
            $this->fileLockHandle = null;
        }
    }

    /**
     * Dynamically retrieves active database character set.
     */
    public function getActiveCharset(): string
    {
        try {
            $charset = (string) $this->db->createCommand('SELECT @@character_set_database')->queryScalar();
            if ($charset !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
                return $charset;
            }
        } catch (Throwable) {
            // Keep default
        }

        return 'utf8mb4';
    }

    /**
     * Dynamically retrieves active database collation.
     */
    public function getActiveCollation(): string
    {
        try {
            $collation = (string) $this->db->createCommand('SELECT @@collation_database')->queryScalar();
            if ($collation !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $collation)) {
                return $collation;
            }
        } catch (Throwable) {
            // Keep default
        }

        return 'utf8mb4_general_ci';
    }

    /**
     * Compares the live database schema against ExpectedSchemaManifest and generates
     * a categorized, risk-classified synchronization plan.
     *
     * @return array{
     *     planId: string,
     *     timestamp: int,
     *     formattedDate: string,
     *     database: string,
     *     isSynchronized: bool,
     *     canAutoSynchronize: bool,
     *     requiresManualReview: bool,
     *     hasDestructiveChanges: bool,
     *     counts: array{
     *         total: int,
     *         safeAdditions: int,
     *         modifications: int,
     *         destructive: int,
     *         manualReview: int
     *     },
     *     safeAdditions: list<array>,
     *     modifications: list<array>,
     *     destructiveChanges: list<array>,
     *     manualReviewRequired: list<array>,
     *     allItems: list<array>,
     *     dbHealth: array,
     *     schemaReport: array
     * }
     */
    public function generatePlan(): array
    {
        $dbHealth = $this->healthService->getConnectionHealth();
        $schemaReport = $this->healthService->inspectSchema();

        $planId = 'plan_' . date('Ymd_His') . '_' . substr(md5((string) microtime(true)), 0, 6);
        $expectedTables = ExpectedSchemaManifest::getTables();
        $existingTables = [];

        if ($dbHealth['connected']) {
            try {
                $existingTables = $this->db->getSchema()->getTableNames();
            } catch (Throwable) {
                $existingTables = [];
            }
        }
        $existingMap = array_fill_keys($existingTables, true);

        // Preload foreign key definitions from information_schema
        $liveFks = $this->inspectDatabaseForeignKeys();

        $safeAdditions = [];
        $modifications = [];
        $destructiveChanges = [];
        $manualReviewRequired = [];

        // 1. Missing Tables (Safe Additions)
        foreach ($expectedTables as $tableName => $tableDef) {
            if ($tableName === 'migration') {
                continue; // System migration table managed strictly by MigrationSyncService
            }

            if (!isset($existingMap[$tableName])) {
                $createSql = $this->buildCreateTableSql($tableName, $tableDef);
                $colsCount = count($tableDef['columns'] ?? []);

                $safeAdditions[] = [
                    'id' => "create_table_{$tableName}",
                    'tableName' => $tableName,
                    'objectType' => 'Table',
                    'objectName' => $tableName,
                    'category' => 'Safe Addition',
                    'risk' => self::RISK_SAFE_ADDITION,
                    'badgeClass' => 'rs-badge--success',
                    'operation' => 'CREATE_TABLE',
                    'currentDefinition' => 'Table missing from database',
                    'expectedDefinition' => "CREATE TABLE `{$tableName}` with {$colsCount} column(s)",
                    'sql' => $createSql,
                    'description' => "Create missing application table `{$tableName}` ({$tableDef['label']}).",
                    'impactsData' => false,
                    'canAutoApply' => true,
                ];

                // Foreign keys for missing table planned as separate dependency items
                foreach ($tableDef['foreignKeys'] ?? [] as $fkName => $fkDef) {
                    $fkSql = $this->buildAddForeignKeySql($tableName, $fkName, $fkDef);
                    $safeAdditions[] = [
                        'id' => "add_fk_{$tableName}_{$fkName}",
                        'tableName' => $tableName,
                        'objectType' => 'Foreign Key',
                        'objectName' => "{$tableName}.{$fkName}",
                        'category' => 'Safe Addition',
                        'risk' => self::RISK_SAFE_ADDITION,
                        'badgeClass' => 'rs-badge--success',
                        'operation' => 'ADD_FOREIGN_KEY',
                        'currentDefinition' => 'Constraint missing',
                        'expectedDefinition' => "FK to `{$fkDef[0]}` (ON DELETE " . ($fkDef['onDelete'] ?? 'RESTRICT') . " ON UPDATE " . ($fkDef['onUpdate'] ?? 'CASCADE') . ")",
                        'sql' => $fkSql,
                        'description' => "Add foreign key constraint `{$fkName}` on `{$tableName}` referencing `{$fkDef[0]}`.",
                        'impactsData' => false,
                        'canAutoApply' => true,
                    ];
                }
            }
        }

        // 2. Extra/Unexpected Tables (Destructive - NEVER Auto-Applied)
        foreach ($existingTables as $tableName) {
            if ($tableName === 'migration') {
                continue; // System migration history table is always preserved
            }
            if (!isset($expectedTables[$tableName])) {
                $destructiveChanges[] = [
                    'id' => "drop_table_{$tableName}",
                    'tableName' => $tableName,
                    'objectType' => 'Table',
                    'objectName' => $tableName,
                    'category' => 'Destructive Change',
                    'risk' => self::RISK_DESTRUCTIVE,
                    'badgeClass' => 'rs-badge--danger',
                    'operation' => 'DROP_TABLE',
                    'currentDefinition' => "Existing extra table `{$tableName}` in database",
                    'expectedDefinition' => 'Not defined in application schema manifest',
                    'sql' => "DROP TABLE IF EXISTS `{$tableName}`;",
                    'description' => "Table `{$tableName}` is not recognized by the Reviews Sorted application. Dropping will delete all its data.",
                    'impactsData' => true,
                    'canAutoApply' => false,
                ];
            }
        }

        // 3. Columns, Indexes, Constraints in Existing Tables
        foreach ($expectedTables as $tableName => $tableDef) {
            if ($tableName === 'migration') {
                continue; // Isolated
            }
            if (!isset($existingMap[$tableName])) {
                continue; // Handled in missing tables
            }

            $tableSchema = $this->db->getSchema()->getTableSchema($tableName);
            if ($tableSchema === null) {
                continue;
            }

            $actualCols = $tableSchema->getColumns();
            $expectedCols = $tableDef['columns'] ?? [];
            $tableRowCount = $this->getTableRowCount($tableName);

            // A. Missing Columns in Existing Table
            foreach ($expectedCols as $colName => $colDef) {
                if (!isset($actualCols[$colName])) {
                    $alterSql = $this->buildAddColumnSql($tableName, $colName, $colDef);
                    $isNotNull = empty($colDef['allowNull']);
                    $hasDefault = array_key_exists('defaultValue', $colDef) && $colDef['defaultValue'] !== null;

                    // RS-SYNC-002: Check if table has rows and column is NOT NULL without default
                    if ($tableRowCount > 0 && $isNotNull && !$hasDefault) {
                        $manualReviewRequired[] = [
                            'id' => "add_col_notnull_{$tableName}_{$colName}",
                            'tableName' => $tableName,
                            'objectType' => 'Column',
                            'objectName' => "{$tableName}.{$colName}",
                            'category' => 'Manual Review Required',
                            'risk' => self::RISK_MANUAL_REVIEW,
                            'badgeClass' => 'rs-badge--danger',
                            'operation' => 'ADD_COLUMN_NOT_NULL',
                            'currentDefinition' => 'Column does not exist',
                            'expectedDefinition' => "`{$colName}` {$colDef['dbType']} NOT NULL (No Default)",
                            'sql' => $alterSql,
                            'description' => "Cannot automatically add NOT NULL column `{$colName}` without default value to table `{$tableName}` containing {$tableRowCount} row(s).",
                            'riskReason' => "Table `{$tableName}` contains {$tableRowCount} active row(s). Adding a NOT NULL column without a default value will fail in MySQL strict mode (Error 1364) or insert blank/zero placeholder data.",
                            'manualAction' => "Specify an explicit compatible default value for `{$colName}` or prepare existing records before applying NOT NULL constraint.",
                            'impactsData' => true,
                            'canAutoApply' => false,
                        ];
                    } else {
                        $safeAdditions[] = [
                            'id' => "add_col_{$tableName}_{$colName}",
                            'tableName' => $tableName,
                            'objectType' => 'Column',
                            'objectName' => "{$tableName}.{$colName}",
                            'category' => 'Safe Addition',
                            'risk' => self::RISK_SAFE_ADDITION,
                            'badgeClass' => 'rs-badge--success',
                            'operation' => 'ADD_COLUMN',
                            'currentDefinition' => 'Column does not exist',
                            'expectedDefinition' => "`{$colName}` {$colDef['dbType']}" . ($colDef['allowNull'] ? ' NULL' : ' NOT NULL'),
                            'sql' => $alterSql,
                            'description' => "Add missing column `{$colName}` to table `{$tableName}`.",
                            'impactsData' => false,
                            'canAutoApply' => true,
                        ];
                    }
                }
            }

            // B. Extra Columns in Existing Table (Destructive - NEVER Auto-Applied)
            foreach ($actualCols as $actualColName => $actualCol) {
                if (!isset($expectedCols[$actualColName])) {
                    $destructiveChanges[] = [
                        'id' => "drop_col_{$tableName}_{$actualColName}",
                        'tableName' => $tableName,
                        'objectType' => 'Column',
                        'objectName' => "{$tableName}.{$actualColName}",
                        'category' => 'Destructive Change',
                        'risk' => self::RISK_DESTRUCTIVE,
                        'badgeClass' => 'rs-badge--danger',
                        'operation' => 'DROP_COLUMN',
                        'currentDefinition' => "`{$actualColName}` {$actualCol->getDbType()}",
                        'expectedDefinition' => 'Not defined in expected schema',
                        'sql' => "ALTER TABLE `{$tableName}` DROP COLUMN `{$actualColName}`;",
                        'description' => "Extra column `{$actualColName}` found in table `{$tableName}`. Dropping will delete all column values.",
                        'impactsData' => true,
                        'canAutoApply' => false,
                    ];
                }
            }

            // C. Column Modifications (RS-SYNC-007: Comprehensive Attributes Inspection)
            foreach ($expectedCols as $colName => $colDef) {
                if (!isset($actualCols[$colName])) {
                    continue;
                }
                $actualCol = $actualCols[$colName];
                $diff = $this->compareColumnDefinition($actualCol, $colDef, $tableRowCount, $tableName, $colName);

                if ($diff !== null) {
                    $modifySql = $this->buildModifyColumnSql($tableName, $colName, $colDef);
                    $item = [
                        'id' => "modify_col_{$tableName}_{$colName}",
                        'tableName' => $tableName,
                        'objectType' => 'Column Definition',
                        'objectName' => "{$tableName}.{$colName}",
                        'currentDefinition' => $diff['actualDefinition'],
                        'expectedDefinition' => $diff['expectedDefinition'],
                        'sql' => $modifySql,
                        'description' => "Update column definition for `{$tableName}.{$colName}`: " . implode('; ', $diff['reasons']),
                        'impactsData' => $tableRowCount > 0,
                    ];

                    if ($diff['isDangerous']) {
                        $item['category'] = 'Manual Review Required';
                        $item['risk'] = self::RISK_MANUAL_REVIEW;
                        $item['badgeClass'] = 'rs-badge--danger';
                        $item['riskReason'] = implode('; ', $diff['riskReasons']);
                        $item['manualAction'] = $diff['manualAction'];
                        $item['canAutoApply'] = false;
                        $manualReviewRequired[] = $item;
                    } else {
                        $item['category'] = 'Modification';
                        $item['risk'] = self::RISK_MODIFICATION;
                        $item['badgeClass'] = 'rs-badge--warning';
                        $item['canAutoApply'] = true;
                        $modifications[] = $item;
                    }
                }
            }

            // D. Missing or Mismatched Primary Key (RS-SYNC-003)
            $actualPk = (array) $tableSchema->getPrimaryKey();
            $expectedPk = (array) ($tableDef['primaryKey'] ?? []);
            if ($this->normalizeColumns($actualPk) !== $this->normalizeColumns($expectedPk) && !empty($expectedPk)) {
                $pkColsSql = implode(', ', array_map(fn($c) => "`{$c}`", $expectedPk));
                $actualPkStr = empty($actualPk) ? 'None' : implode(', ', $actualPk);
                $expectedPkStr = implode(', ', $expectedPk);

                if (!empty($actualPk)) {
                    // Conflicting existing PK: Never auto-drop or replace
                    $manualReviewRequired[] = [
                        'id' => "alter_pk_{$tableName}",
                        'tableName' => $tableName,
                        'objectType' => 'Primary Key',
                        'objectName' => "{$tableName}.PRIMARY",
                        'category' => 'Manual Review Required',
                        'risk' => self::RISK_MANUAL_REVIEW,
                        'badgeClass' => 'rs-badge--danger',
                        'operation' => 'ALTER_PRIMARY_KEY',
                        'currentDefinition' => "Existing PK: [{$actualPkStr}]",
                        'expectedDefinition' => "Expected PK: [{$expectedPkStr}]",
                        'sql' => "-- Manual DBA procedure required:\n-- ALTER TABLE `{$tableName}` DROP PRIMARY KEY, ADD PRIMARY KEY ({$pkColsSql});",
                        'description' => "Primary key conflict on `{$tableName}`: current PK [{$actualPkStr}] differs from expected [{$expectedPkStr}].",
                        'riskReason' => "Table already has a primary key [{$actualPkStr}]. Replacing primary keys can break AUTO_INCREMENT sequences and dependent foreign key relationships.",
                        'manualAction' => "Verify AUTO_INCREMENT properties and dependent foreign keys, then alter the primary key manually following full backup.",
                        'impactsData' => true,
                        'canAutoApply' => false,
                    ];
                } else {
                    // Table has no primary key: check duplicate and null values
                    $hasConflicts = false;
                    $conflictReason = '';
                    if ($tableRowCount > 0) {
                        foreach ($expectedPk as $pkCol) {
                            if ($this->hasNullValues($tableName, $pkCol)) {
                                $hasConflicts = true;
                                $conflictReason = "Column `{$pkCol}` contains NULL values in existing {$tableRowCount} row(s).";
                                break;
                            }
                        }
                        if (!$hasConflicts && $this->hasDuplicatePkValues($tableName, $expectedPk)) {
                            $hasConflicts = true;
                            $conflictReason = "Columns [{$expectedPkStr}] contain duplicate values in existing rows.";
                        }
                    }

                    if ($hasConflicts) {
                        $manualReviewRequired[] = [
                            'id' => "add_pk_conflict_{$tableName}",
                            'tableName' => $tableName,
                            'objectType' => 'Primary Key',
                            'objectName' => "{$tableName}.PRIMARY",
                            'category' => 'Manual Review Required',
                            'risk' => self::RISK_MANUAL_REVIEW,
                            'badgeClass' => 'rs-badge--danger',
                            'operation' => 'ADD_PRIMARY_KEY',
                            'currentDefinition' => 'No primary key',
                            'expectedDefinition' => "PRIMARY KEY ({$pkColsSql})",
                            'sql' => "ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$pkColsSql});",
                            'description' => "Cannot add primary key on `{$tableName}`: {$conflictReason}",
                            'riskReason' => $conflictReason,
                            'manualAction' => "Deduplicate and populate NULL values before applying PRIMARY KEY constraint.",
                            'impactsData' => true,
                            'canAutoApply' => false,
                        ];
                    } else {
                        $safeAdditions[] = [
                            'id' => "add_pk_{$tableName}",
                            'tableName' => $tableName,
                            'objectType' => 'Primary Key',
                            'objectName' => "{$tableName}.PRIMARY",
                            'category' => 'Safe Addition',
                            'risk' => self::RISK_SAFE_ADDITION,
                            'badgeClass' => 'rs-badge--success',
                            'operation' => 'ADD_PRIMARY_KEY',
                            'currentDefinition' => 'No primary key',
                            'expectedDefinition' => "PRIMARY KEY ({$pkColsSql})",
                            'sql' => "ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$pkColsSql});",
                            'description' => "Add primary key on `{$tableName}` ({$pkColsSql}).",
                            'impactsData' => false,
                            'canAutoApply' => true,
                        ];
                    }
                }
            }

            // E. Missing Indexes
            try {
                $actualIndexes = $this->db->getSchema()->getTableIndexes($tableName);
                foreach ($tableDef['indexes'] ?? [] as $idxName => $idxDef) {
                    if (!empty($idxDef['isPrimary'])) {
                        continue; // PK already checked
                    }
                    $idxCols = (array) ($idxDef['columns'] ?? []);
                    $isUnique = !empty($idxDef['isUnique']);

                    $found = false;
                    foreach ($actualIndexes as $actIdx) {
                        if ($this->normalizeColumns($actIdx->getColumnNames()) === $this->normalizeColumns($idxCols)) {
                            if (!$isUnique || $actIdx->isUnique()) {
                                $found = true;
                                break;
                            }
                        }
                    }

                    if (!$found) {
                        $idxColsSql = implode(', ', array_map(fn($c) => "`{$c}`", $idxCols));
                        $idxTypeSql = $isUnique ? 'UNIQUE INDEX' : 'INDEX';
                        $indexSql = "CREATE " . ($isUnique ? 'UNIQUE ' : '') . "INDEX `{$idxName}` ON `{$tableName}` ({$idxColsSql});";

                        // If unique index on table with rows, check for duplicates
                        $hasDuplicates = false;
                        if ($isUnique && $tableRowCount > 0 && $this->hasDuplicatePkValues($tableName, $idxCols)) {
                            $hasDuplicates = true;
                        }

                        if ($hasDuplicates) {
                            $manualReviewRequired[] = [
                                'id' => "add_unique_idx_conflict_{$tableName}_{$idxName}",
                                'tableName' => $tableName,
                                'objectType' => 'Index',
                                'objectName' => "{$tableName}.{$idxName}",
                                'category' => 'Manual Review Required',
                                'risk' => self::RISK_MANUAL_REVIEW,
                                'badgeClass' => 'rs-badge--danger',
                                'operation' => 'ADD_UNIQUE_INDEX',
                                'currentDefinition' => 'Index missing',
                                'expectedDefinition' => "{$idxTypeSql} `{$idxName}` ({$idxColsSql})",
                                'sql' => $indexSql,
                                'description' => "Cannot create UNIQUE index `{$idxName}` on table `{$tableName}`: duplicate values exist in table.",
                                'riskReason' => "Existing rows in `{$tableName}` contain duplicate values for columns [{$idxColsSql}]. Creating a UNIQUE index will fail with MySQL Error 1062.",
                                'manualAction' => "Resolve duplicate records in table `{$tableName}` before creating the unique index.",
                                'impactsData' => true,
                                'canAutoApply' => false,
                            ];
                        } else {
                            $safeAdditions[] = [
                                'id' => "add_idx_{$tableName}_{$idxName}",
                                'tableName' => $tableName,
                                'objectType' => 'Index',
                                'objectName' => "{$tableName}.{$idxName}",
                                'category' => 'Safe Addition',
                                'risk' => self::RISK_SAFE_ADDITION,
                                'badgeClass' => 'rs-badge--success',
                                'operation' => 'ADD_INDEX',
                                'currentDefinition' => 'Index missing',
                                'expectedDefinition' => "{$idxTypeSql} `{$idxName}` ({$idxColsSql})",
                                'sql' => $indexSql,
                                'description' => "Add " . ($isUnique ? 'unique ' : '') . "index `{$idxName}` on table `{$tableName}`.",
                                'impactsData' => false,
                                'canAutoApply' => true,
                            ];
                        }
                    }
                }
            } catch (Throwable) {
                // Skip if index inspection unsupported
            }

            // F. Foreign Keys & Referential Actions Inspection (RS-SYNC-FK)
            $actualTableFks = $liveFks[$tableName] ?? [];
            foreach ($tableDef['foreignKeys'] ?? [] as $fkName => $fkDef) {
                $refTable = (string) ($fkDef[0] ?? '');
                $colMap = array_slice($fkDef, 1);
                // Remove meta keys from column mapping
                unset($colMap['onDelete'], $colMap['onUpdate']);

                $expectedOnDelete = strtoupper((string) ($fkDef['onDelete'] ?? 'RESTRICT'));
                $expectedOnUpdate = strtoupper((string) ($fkDef['onUpdate'] ?? 'CASCADE'));

                // Look for existing matching constraint
                $matchingFkName = null;
                $matchingFk = null;

                foreach ($actualTableFks as $liveFkName => $liveFkInfo) {
                    if ($liveFkInfo['refTable'] === $refTable && $liveFkInfo['columns'] === $colMap) {
                        $matchingFkName = $liveFkName;
                        $matchingFk = $liveFkInfo;
                        break;
                    }
                }

                if ($matchingFk === null) {
                    // Foreign key missing entirely
                    $fkSql = $this->buildAddForeignKeySql($tableName, $fkName, $fkDef);

                    // Check for orphan records if table has rows
                    $hasOrphans = false;
                    if ($tableRowCount > 0 && $this->hasOrphanRows($tableName, $colMap, $refTable)) {
                        $hasOrphans = true;
                    }

                    if ($hasOrphans) {
                        $manualReviewRequired[] = [
                            'id' => "add_fk_orphan_{$tableName}_{$fkName}",
                            'tableName' => $tableName,
                            'objectType' => 'Foreign Key',
                            'objectName' => "{$tableName}.{$fkName}",
                            'category' => 'Manual Review Required',
                            'risk' => self::RISK_MANUAL_REVIEW,
                            'badgeClass' => 'rs-badge--danger',
                            'operation' => 'ADD_FOREIGN_KEY',
                            'currentDefinition' => 'Foreign key constraint missing',
                            'expectedDefinition' => "FK to `{$refTable}` (ON DELETE {$expectedOnDelete} ON UPDATE {$expectedOnUpdate})",
                            'sql' => $fkSql,
                            'description' => "Cannot add foreign key `{$fkName}`: table `{$tableName}` contains orphan records not present in parent table `{$refTable}`.",
                            'riskReason' => "Orphan rows exist in table `{$tableName}` referencing nonexistent keys in `{$refTable}`. Adding foreign key will fail with MySQL Error 1452.",
                            'manualAction' => "Clean or reassign orphan child records before adding foreign key constraint.",
                            'impactsData' => true,
                            'canAutoApply' => false,
                        ];
                    } else {
                        $safeAdditions[] = [
                            'id' => "add_fk_{$tableName}_{$fkName}",
                            'tableName' => $tableName,
                            'objectType' => 'Foreign Key',
                            'objectName' => "{$tableName}.{$fkName}",
                            'category' => 'Safe Addition',
                            'risk' => self::RISK_SAFE_ADDITION,
                            'badgeClass' => 'rs-badge--success',
                            'operation' => 'ADD_FOREIGN_KEY',
                            'currentDefinition' => 'Foreign key constraint missing',
                            'expectedDefinition' => "FK to `{$refTable}` (ON DELETE {$expectedOnDelete} ON UPDATE {$expectedOnUpdate})",
                            'sql' => $fkSql,
                            'description' => "Add foreign key constraint on `{$tableName}` referencing `{$refTable}`.",
                            'impactsData' => false,
                            'canAutoApply' => true,
                        ];
                    }
                } else {
                    // Foreign key exists: verify referential actions match
                    $actOnDelete = strtoupper($matchingFk['onDelete']);
                    $actOnUpdate = strtoupper($matchingFk['onUpdate']);

                    if ($actOnDelete !== $expectedOnDelete || $actOnUpdate !== $expectedOnUpdate) {
                        $manualReviewRequired[] = [
                            'id' => "alter_fk_actions_{$tableName}_{$matchingFkName}",
                            'tableName' => $tableName,
                            'objectType' => 'Foreign Key Referential Actions',
                            'objectName' => "{$tableName}.{$matchingFkName}",
                            'category' => 'Manual Review Required',
                            'risk' => self::RISK_MANUAL_REVIEW,
                            'badgeClass' => 'rs-badge--danger',
                            'operation' => 'ALTER_FOREIGN_KEY_ACTION',
                            'currentDefinition' => "ON DELETE {$actOnDelete} ON UPDATE {$actOnUpdate}",
                            'expectedDefinition' => "ON DELETE {$expectedOnDelete} ON UPDATE {$expectedOnUpdate}",
                            'sql' => "-- Manual DBA constraint modification:\n-- ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$matchingFkName}`;\n-- " . $this->buildAddForeignKeySql($tableName, $matchingFkName, $fkDef),
                            'description' => "Referential action mismatch on `{$tableName}.{$matchingFkName}`: expected ON DELETE {$expectedOnDelete} ON UPDATE {$expectedOnUpdate}, found ON DELETE {$actOnDelete} ON UPDATE {$actOnUpdate}.",
                            'riskReason' => "Altering referential action requires dropping and recreating foreign key constraint. Silent replacement could alter cascade behaviors.",
                            'manualAction' => "Review business requirements for cascading delete/update, then modify foreign key manually after backup.",
                            'impactsData' => true,
                            'canAutoApply' => false,
                        ];
                    }
                }
            }
        }

        $allOrderedItems = array_merge($safeAdditions, $modifications, $manualReviewRequired, $destructiveChanges);
        $totalCount = count($allOrderedItems);
        $isSynchronized = ($totalCount === 0);
        $canAutoSynchronize = count($safeAdditions) > 0 || count($modifications) > 0;
        $requiresManualReview = count($manualReviewRequired) > 0;
        $hasDestructiveChanges = count($destructiveChanges) > 0;

        return [
            'planId' => $planId,
            'timestamp' => time(),
            'formattedDate' => date('Y-m-d H:i:s'),
            'database' => $dbHealth['database'] ?? Environment::dbName(),
            'isSynchronized' => $isSynchronized,
            'canAutoSynchronize' => $canAutoSynchronize,
            'requiresManualReview' => $requiresManualReview,
            'hasDestructiveChanges' => $hasDestructiveChanges,
            'counts' => [
                'total' => $totalCount,
                'safeAdditions' => count($safeAdditions),
                'modifications' => count($modifications),
                'destructive' => count($destructiveChanges),
                'manualReview' => count($manualReviewRequired),
            ],
            'safeAdditions' => $safeAdditions,
            'modifications' => $modifications,
            'destructiveChanges' => $destructiveChanges,
            'manualReviewRequired' => $manualReviewRequired,
            'allItems' => $allOrderedItems,
            'dbHealth' => $dbHealth,
            'schemaReport' => $schemaReport,
        ];
    }

    /**
     * Executes approved safe operations from the synchronization plan.
     * Rechecks the database live immediately before applying changes.
     * Acquires server-side mutex lock to prevent concurrent executions.
     * Never executes destructive or manual-review operations automatically.
     *
     * @param list<string> $selectedOperationIds If empty, executes all auto-applicable operations
     * @param bool $includeModifications Whether to execute safe non-destructive column modifications
     * @return array{
     *     executionId: string,
     *     timestamp: int,
     *     formattedDate: string,
     *     database: string,
     *     totalPlanned: int,
     *     successCount: int,
     *     failedCount: int,
     *     skippedCount: int,
     *     manualReviewCount: int,
     *     destructiveCount: int,
     *     operations: list<array>,
     *     postSyncHealth: array,
     *     error: ?string
     * }
     */
    public function executePlan(array $selectedOperationIds = [], bool $includeModifications = true): array
    {
        // RS-SYNC-004: Acquire synchronization mutex lock
        if (!$this->acquireLock()) {
            return [
                'executionId' => 'sync_locked_' . date('Ymd_His'),
                'timestamp' => time(),
                'formattedDate' => date('Y-m-d H:i:s'),
                'database' => Environment::dbName(),
                'totalPlanned' => 0,
                'successCount' => 0,
                'failedCount' => 0,
                'skippedCount' => 0,
                'manualReviewCount' => 0,
                'destructiveCount' => 0,
                'operations' => [],
                'postSyncHealth' => [],
                'error' => 'Another database synchronization operation is currently in progress. Please wait a few moments and try again.',
            ];
        }

        try {
            // 1. Re-check database live and generate fresh plan
            $freshPlan = $this->generatePlan();
            $executionId = 'sync_' . date('Ymd_His') . '_' . substr(md5((string) microtime(true)), 0, 6);

            if (!$freshPlan['dbHealth']['connected']) {
                return [
                    'executionId' => $executionId,
                    'timestamp' => time(),
                    'formattedDate' => date('Y-m-d H:i:s'),
                    'database' => $freshPlan['database'],
                    'totalPlanned' => 0,
                    'successCount' => 0,
                    'failedCount' => 0,
                    'skippedCount' => 0,
                    'manualReviewCount' => 0,
                    'destructiveCount' => 0,
                    'operations' => [],
                    'postSyncHealth' => $freshPlan['schemaReport'],
                    'error' => 'Database connection failed: cannot execute synchronization.',
                ];
            }

            // 2. Select strictly safe, approved operations
            $candidateItems = [];
            foreach ($freshPlan['safeAdditions'] as $item) {
                $candidateItems[] = $item;
            }
            if ($includeModifications) {
                foreach ($freshPlan['modifications'] as $item) {
                    $candidateItems[] = $item;
                }
            }

            // If specific IDs selected, filter
            if (!empty($selectedOperationIds)) {
                $selectedMap = array_fill_keys($selectedOperationIds, true);
                $candidateItems = array_values(array_filter(
                    $candidateItems,
                    fn($item) => isset($selectedMap[$item['id']])
                ));
            }

            // 3. Sort candidates in strict dependency order:
            //    CREATE_TABLE -> ADD_COLUMN -> MODIFY_COLUMN -> ADD_PRIMARY_KEY -> ADD_INDEX -> ADD_FOREIGN_KEY
            usort($candidateItems, function ($a, $b) {
                $order = [
                    'CREATE_TABLE' => 1,
                    'ADD_COLUMN' => 2,
                    'MODIFY_COLUMN' => 3,
                    'ADD_PRIMARY_KEY' => 4,
                    'ADD_INDEX' => 5,
                    'ADD_FOREIGN_KEY' => 6,
                ];
                $oa = $order[$a['operation'] ?? ''] ?? 99;
                $ob = $order[$b['operation'] ?? ''] ?? 99;
                return $oa <=> $ob;
            });

            $executedLog = [];
            $failedTables = [];
            $successCount = 0;
            $failedCount = 0;
            $skippedCount = 0;

            foreach ($candidateItems as $item) {
                $tableName = $item['tableName'] ?? '';
                // If table creation failed, skip dependent column/index/FK operations on it
                if (isset($failedTables[$tableName]) && ($item['operation'] ?? '') !== 'CREATE_TABLE') {
                    $skippedCount++;
                    $executedLog[] = [
                        'id' => $item['id'],
                        'tableName' => $tableName,
                        'objectType' => $item['objectType'],
                        'objectName' => $item['objectName'],
                        'operation' => $item['operation'],
                        'sql' => $item['sql'],
                        'status' => 'SKIPPED',
                        'error' => "Skipped because earlier operation on table `{$tableName}` failed.",
                        'durationMs' => 0,
                    ];
                    continue;
                }

                $startTime = microtime(true);
                try {
                    // Execute DDL statement
                    $this->db->createCommand($item['sql'])->execute();
                    $duration = round((microtime(true) - $startTime) * 1000, 2);
                    $successCount++;

                    $executedLog[] = [
                        'id' => $item['id'],
                        'tableName' => $tableName,
                        'objectType' => $item['objectType'],
                        'objectName' => $item['objectName'],
                        'operation' => $item['operation'],
                        'sql' => $item['sql'],
                        'status' => 'SUCCESS',
                        'error' => null,
                        'durationMs' => $duration,
                    ];
                } catch (Throwable $e) {
                    $duration = round((microtime(true) - $startTime) * 1000, 2);
                    $failedCount++;
                    if (($item['operation'] ?? '') === 'CREATE_TABLE') {
                        $failedTables[$tableName] = true;
                    }

                    $executedLog[] = [
                        'id' => $item['id'],
                        'tableName' => $tableName,
                        'objectType' => $item['objectType'],
                        'objectName' => $item['objectName'],
                        'operation' => $item['operation'],
                        'sql' => $item['sql'],
                        'status' => 'FAILED',
                        'error' => $e->getMessage(),
                        'durationMs' => $duration,
                    ];
                }
            }

            // 4. Refresh post-sync health inspection
            $postSyncHealth = $this->healthService->inspectSchema();

            $report = [
                'executionId' => $executionId,
                'timestamp' => time(),
                'formattedDate' => date('Y-m-d H:i:s'),
                'database' => $freshPlan['database'],
                'totalPlanned' => count($candidateItems),
                'successCount' => $successCount,
                'failedCount' => $failedCount,
                'skippedCount' => $skippedCount,
                'manualReviewCount' => count($freshPlan['manualReviewRequired']),
                'destructiveCount' => count($freshPlan['destructiveChanges']),
                'operations' => $executedLog,
                'postSyncHealth' => $postSyncHealth,
                'error' => $failedCount > 0 ? "Synchronization partially completed: {$failedCount} operation(s) failed." : null,
            ];

            // 5. RS-SYNC-008: Save to execution history with circular buffer & file lock
            $this->saveExecutionReport($report);

            return $report;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Reads the most recent execution report from disk history.
     */
    public function getLastExecutionReport(): ?array
    {
        $history = $this->getExecutionHistory();
        return $history[0] ?? null;
    }

    /**
     * Reads the retained synchronization execution history (up to 10 reports).
     *
     * @return list<array>
     */
    public function getExecutionHistory(): array
    {
        if (!file_exists($this->historyFile)) {
            return [];
        }

        $fp = @fopen($this->historyFile, 'r');
        if (!$fp) {
            return [];
        }

        $content = '';
        if (@flock($fp, LOCK_SH)) {
            $content = (string) @stream_get_contents($fp);
            @flock($fp, LOCK_UN);
        }
        @fclose($fp);

        if ($content === '') {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                if (isset($data['history']) && is_array($data['history'])) {
                    return array_values($data['history']);
                }
                // Backwards compatibility with single report format
                if (isset($data['executionId'])) {
                    return [$data];
                }
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    /**
     * RS-SYNC-008: Saves execution report preserving the last 10 reports with file-level locking.
     */
    private function saveExecutionReport(array $report): void
    {
        $dir = dirname($this->historyFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $existingHistory = $this->getExecutionHistory();
        array_unshift($existingHistory, $report);
        $trimmedHistory = array_slice($existingHistory, 0, self::MAX_HISTORY_RECORDS);

        $payload = [
            'latest' => $report,
            'history' => $trimmedHistory,
            'lastUpdated' => date('Y-m-d H:i:s'),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $fp = @fopen($this->historyFile, 'c+');
        if ($fp && @flock($fp, LOCK_EX)) {
            @ftruncate($fp, 0);
            @fwrite($fp, $json);
            @fflush($fp);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        } elseif ($fp) {
            @fclose($fp);
        }
    }

    /**
     * RS-SYNC-001: Builds CREATE TABLE SQL statement using dynamic database charset and collation.
     */
    private function buildCreateTableSql(string $tableName, array $tableDef): string
    {
        $lines = [];
        foreach ($tableDef['columns'] ?? [] as $colName => $colDef) {
            $lines[] = '  ' . $this->buildColumnSqlDefinition($colName, $colDef);
        }

        if (!empty($tableDef['primaryKey'])) {
            $pkCols = array_map(fn($c) => "`{$c}`", (array) $tableDef['primaryKey']);
            $lines[] = '  PRIMARY KEY (' . implode(', ', $pkCols) . ')';
        }

        // Add indexes inside create table if standard
        foreach ($tableDef['indexes'] ?? [] as $idxName => $idxDef) {
            if (!empty($idxDef['isPrimary'])) {
                continue;
            }
            $idxCols = array_map(fn($c) => "`{$c}`", (array) ($idxDef['columns'] ?? []));
            $isUnique = !empty($idxDef['isUnique']);
            $lines[] = '  ' . ($isUnique ? 'UNIQUE KEY' : 'KEY') . " `{$idxName}` (" . implode(', ', $idxCols) . ')';
        }

        $charset = $this->getActiveCharset();
        $collation = $this->getActiveCollation();

        return "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n"
            . implode(",\n", $lines)
            . "\n) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};";
    }

    /**
     * Builds ALTER TABLE ... ADD COLUMN SQL statement.
     */
    private function buildAddColumnSql(string $tableName, string $colName, array $colDef): string
    {
        $colSql = $this->buildColumnSqlDefinition($colName, $colDef);
        return "ALTER TABLE `{$tableName}` ADD COLUMN {$colSql};";
    }

    /**
     * Builds ALTER TABLE ... MODIFY COLUMN SQL statement.
     */
    private function buildModifyColumnSql(string $tableName, string $colName, array $colDef): string
    {
        $colSql = $this->buildColumnSqlDefinition($colName, $colDef);
        return "ALTER TABLE `{$tableName}` MODIFY COLUMN {$colSql};";
    }

    /**
     * Builds ALTER TABLE ... ADD CONSTRAINT FOREIGN KEY SQL statement with dynamic referential actions.
     */
    private function buildAddForeignKeySql(string $tableName, string $fkName, array $fkDef): string
    {
        $refTable = (string) ($fkDef[0] ?? '');
        $colMap = array_slice($fkDef, 1);
        unset($colMap['onDelete'], $colMap['onUpdate']);

        $localCols = array_keys($colMap);
        $refCols = array_values($colMap);

        $localColsSql = implode(', ', array_map(fn($c) => "`{$c}`", $localCols));
        $refColsSql = implode(', ', array_map(fn($c) => "`{$c}`", $refCols));

        $onDelete = strtoupper((string) ($fkDef['onDelete'] ?? 'RESTRICT'));
        $onUpdate = strtoupper((string) ($fkDef['onUpdate'] ?? 'CASCADE'));

        return "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY ({$localColsSql}) REFERENCES `{$refTable}` ({$refColsSql}) ON DELETE {$onDelete} ON UPDATE {$onUpdate};";
    }

    /**
     * Constructs the column definition segment: `col` type(size) NULL/NOT NULL DEFAULT ...
     */
    private function buildColumnSqlDefinition(string $colName, array $colDef): string
    {
        $dbType = (string) ($colDef['dbType'] ?? 'varchar(255)');
        $sql = "`{$colName}` {$dbType}";

        if (!empty($colDef['unsigned']) && !str_contains(strtolower($dbType), 'unsigned')) {
            $sql .= ' UNSIGNED';
        }

        if (empty($colDef['allowNull'])) {
            $sql .= ' NOT NULL';
        } else {
            $sql .= ' NULL';
        }

        if (!empty($colDef['autoIncrement'])) {
            $sql .= ' AUTO_INCREMENT';
        } elseif (array_key_exists('defaultValue', $colDef) && $colDef['defaultValue'] !== null) {
            $val = $colDef['defaultValue'];
            if (is_numeric($val)) {
                $sql .= ' DEFAULT ' . $val;
            } elseif (is_string($val)) {
                if (in_array(strtoupper($val), ['CURRENT_TIMESTAMP', 'NOW()'], true)) {
                    $sql .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $sql .= " DEFAULT '" . addslashes($val) . "'";
                }
            } elseif (is_bool($val)) {
                $sql .= ' DEFAULT ' . ($val ? '1' : '0');
            }
        } elseif (!empty($colDef['allowNull'])) {
            $sql .= ' DEFAULT NULL';
        }

        return $sql;
    }

    /**
     * RS-SYNC-007: Compares live column against expected column manifest across all supported attributes.
     *
     * @return ?array{
     *     actualDefinition: string,
     *     expectedDefinition: string,
     *     reasons: list<string>,
     *     isDangerous: bool,
     *     riskReasons: list<string>,
     *     manualAction: string
     * }
     */
    private function compareColumnDefinition(
        ColumnSchemaInterface $actualCol,
        array $colDef,
        int $tableRowCount,
        string $tableName,
        string $colName
    ): ?array {
        $needsModify = false;
        $isDangerous = false;
        $reasons = [];
        $riskReasons = [];
        $manualAction = '';

        $actDbType = strtolower(trim((string) $actualCol->getDbType()));
        $expDbType = strtolower(trim((string) ($colDef['dbType'] ?? '')));
        $actType = strtolower(trim((string) $actualCol->getType()));
        $expType = strtolower(trim((string) ($colDef['type'] ?? '')));

        // 1. Data Type & DB Type Comparison
        if ($expDbType !== '' && $actDbType !== '' && $expDbType !== $actDbType) {
            // Check if normalized types are functionally equivalent (e.g. int vs int(11))
            if (!$this->isFunctionallyEquivalentDbType($actDbType, $expDbType)) {
                $needsModify = true;
                $reasons[] = "Data type: expected `{$expDbType}`, found `{$actDbType}`";
                if ($tableRowCount > 0 && !$this->isSafeTypePromotion($actType, $expType)) {
                    $isDangerous = true;
                    $riskReasons[] = "Converting column type from `{$actDbType}` to `{$expDbType}` on table with {$tableRowCount} row(s) may corrupt or lose data.";
                    $manualAction = "Backup table data and verify type cast compatibility before altering column.";
                }
            }
        }

        // 2. Column Length / Size Comparison
        $expSize = $colDef['size'] ?? null;
        $actSize = $actualCol->getSize();
        if ($expSize !== null && $actSize !== null && (int) $expSize !== (int) $actSize) {
            // Only report size mismatch if not already a full dbType difference
            if (!in_array($expType, ['integer', 'int', 'tinyint', 'smallint', 'bigint'], true)) {
                $needsModify = true;
                $reasons[] = "Length: expected {$expSize}, found {$actSize}";
                if ((int) $expSize < (int) $actSize && $tableRowCount > 0) {
                    $isDangerous = true;
                    $riskReasons[] = "Narrowing column length from {$actSize} to {$expSize} on {$tableRowCount} row(s) may truncate data.";
                    $manualAction = "Check max length of stored data before shrinking column size.";
                }
            }
        }

        // 3. Numeric Scale Comparison
        $expScale = $colDef['scale'] ?? null;
        $actScale = $actualCol->getScale();
        if ($expScale !== null && $actScale !== null && (int) $expScale !== (int) $actScale) {
            $needsModify = true;
            $reasons[] = "Scale: expected {$expScale}, found {$actScale}";
            if ((int) $expScale < (int) $actScale && $tableRowCount > 0) {
                $isDangerous = true;
                $riskReasons[] = "Reducing decimal scale from {$actScale} to {$expScale} will truncate decimal precision.";
                $manualAction = "Verify decimal precision requirements before modifying scale.";
            }
        }

        // 4. Nullability Comparison
        $expNull = (bool) ($colDef['allowNull'] ?? true);
        $actNull = (bool) $actualCol->isAllowNull();
        if ($expNull !== $actNull) {
            $needsModify = true;
            $reasons[] = 'Nullability: expected ' . ($expNull ? 'NULL' : 'NOT NULL') . ', found ' . ($actNull ? 'NULL' : 'NOT NULL');

            if (!$expNull && $tableRowCount > 0) {
                if ($this->hasNullValues($tableName, $colName)) {
                    $isDangerous = true;
                    $riskReasons[] = "Table `{$tableName}` has rows containing NULL in column `{$colName}`. Applying NOT NULL will fail without default values.";
                    $manualAction = "Populate default values in existing NULL records before applying NOT NULL constraint.";
                }
            }
        }

        // 5. Default Value Comparison
        $expDefault = $this->normalizeDefaultValue($colDef['defaultValue'] ?? null);
        $actDefault = $this->normalizeDefaultValue($actualCol->getDefaultValue());
        if ($expDefault !== $actDefault) {
            $needsModify = true;
            $reasons[] = "Default value: expected `{$expDefault}`, found `{$actDefault}`";
        }

        // 6. AUTO_INCREMENT Comparison
        $expAuto = !empty($colDef['autoIncrement']);
        $actAuto = (bool) $actualCol->isAutoIncrement();
        if ($expAuto !== $actAuto) {
            $needsModify = true;
            $reasons[] = 'AUTO_INCREMENT: expected ' . ($expAuto ? 'YES' : 'NO') . ', found ' . ($actAuto ? 'YES' : 'NO');
            $isDangerous = true;
            $riskReasons[] = "Modifying AUTO_INCREMENT sequence on active table can cause primary key conflicts.";
            $manualAction = "Review primary key sequencing manually.";
        }

        if (!$needsModify) {
            return null;
        }

        return [
            'actualDefinition' => "`{$colName}` {$actualCol->getDbType()}" . ($actNull ? ' NULL' : ' NOT NULL') . ($actDefault !== 'NULL' ? " DEFAULT {$actDefault}" : ''),
            'expectedDefinition' => "`{$colName}` {$colDef['dbType']}" . ($expNull ? ' NULL' : ' NOT NULL') . ($expDefault !== 'NULL' ? " DEFAULT {$expDefault}" : ''),
            'reasons' => $reasons,
            'isDangerous' => $isDangerous,
            'riskReasons' => $riskReasons,
            'manualAction' => $manualAction !== '' ? $manualAction : 'Review change in staging before applying.',
        ];
    }

    /**
     * Inspects active database for existing foreign keys and their referential constraints.
     *
     * @return array<string, array<string, array{refTable: string, columns: array<string, string>, onDelete: string, onUpdate: string}>>
     */
    private function inspectDatabaseForeignKeys(): array
    {
        if ($this->dbForeignKeysCache !== null) {
            return $this->dbForeignKeysCache;
        }

        $result = [];
        try {
            $rows = $this->db->createCommand("
                SELECT 
                    k.TABLE_NAME,
                    k.CONSTRAINT_NAME,
                    k.COLUMN_NAME,
                    k.REFERENCED_TABLE_NAME,
                    k.REFERENCED_COLUMN_NAME,
                    r.DELETE_RULE,
                    r.UPDATE_RULE
                FROM information_schema.KEY_COLUMN_USAGE k
                JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                  ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME
                 AND k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
                WHERE k.TABLE_SCHEMA = DATABASE()
                  AND k.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION
            ")->queryAll();

            foreach ($rows as $row) {
                $tbl = (string) $row['TABLE_NAME'];
                $cName = (string) $row['CONSTRAINT_NAME'];
                $col = (string) $row['COLUMN_NAME'];
                $rCol = (string) $row['REFERENCED_COLUMN_NAME'];
                $rTbl = (string) $row['REFERENCED_TABLE_NAME'];
                $del = (string) $row['DELETE_RULE'];
                $upd = (string) $row['UPDATE_RULE'];

                if (!isset($result[$tbl][$cName])) {
                    $result[$tbl][$cName] = [
                        'refTable' => $rTbl,
                        'columns' => [],
                        'onDelete' => $del,
                        'onUpdate' => $upd,
                    ];
                }
                $result[$tbl][$cName]['columns'][$col] = $rCol;
            }
        } catch (Throwable) {
            // Fallback to empty if information_schema query fails
        }

        $this->dbForeignKeysCache = $result;
        return $result;
    }

    private function getTableRowCount(string $tableName): int
    {
        try {
            return (int) $this->db->createCommand("SELECT COUNT(*) FROM `{$tableName}`")->queryScalar();
        } catch (Throwable) {
            return 0;
        }
    }

    private function hasNullValues(string $tableName, string $colName): bool
    {
        try {
            $count = (int) $this->db->createCommand("SELECT COUNT(*) FROM `{$tableName}` WHERE `{$colName}` IS NULL")->queryScalar();
            return $count > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function hasDuplicatePkValues(string $tableName, array $pkCols): bool
    {
        try {
            $colsSql = implode(', ', array_map(fn($c) => "`{$c}`", $pkCols));
            $sql = "SELECT COUNT(*) FROM (SELECT {$colsSql} FROM `{$tableName}` GROUP BY {$colsSql} HAVING COUNT(*) > 1) AS dup";
            $count = (int) $this->db->createCommand($sql)->queryScalar();
            return $count > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function hasOrphanRows(string $childTable, array $colMap, string $parentTable): bool
    {
        try {
            $joinClauses = [];
            $whereClauses = [];
            foreach ($colMap as $childCol => $parentCol) {
                $joinClauses[] = "c.`{$childCol}` = p.`{$parentCol}`";
                $whereClauses[] = "c.`{$childCol}` IS NOT NULL";
            }
            $joinSql = implode(' AND ', $joinClauses);
            $whereSql = implode(' AND ', $whereClauses);

            // First primary/key column in parent
            $firstParentCol = reset($colMap);
            $sql = "SELECT COUNT(*) FROM `{$childTable}` c LEFT JOIN `{$parentTable}` p ON {$joinSql} WHERE {$whereSql} AND p.`{$firstParentCol}` IS NULL";
            $count = (int) $this->db->createCommand($sql)->queryScalar();
            return $count > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function isFunctionallyEquivalentDbType(string $act, string $exp): bool
    {
        // MySQL 8+ displays 'int' instead of 'int(11)', 'tinyint' instead of 'tinyint(1)'
        $cleanAct = preg_replace('/\(\d+\)/', '', $act);
        $cleanExp = preg_replace('/\(\d+\)/', '', $exp);
        return $cleanAct === $cleanExp;
    }

    private function isSafeTypePromotion(string $currentType, string $expectedType): bool
    {
        $intRank = ['tinyint' => 1, 'smallint' => 2, 'mediumint' => 3, 'int' => 4, 'integer' => 4, 'bigint' => 5];
        if (isset($intRank[$currentType], $intRank[$expectedType])) {
            return $intRank[$expectedType] >= $intRank[$currentType];
        }
        if ($currentType === 'string' && in_array($expectedType, ['text', 'string'], true)) {
            return true;
        }
        return false;
    }

    private function normalizeDefaultValue(mixed $val): string
    {
        if ($val === null) {
            return 'NULL';
        }
        if (is_bool($val)) {
            return $val ? '1' : '0';
        }
        $str = trim((string) $val);
        if ($str === '' || $str === "''") {
            return "''";
        }
        return trim($str, "'\"");
    }

    /**
     * @param list<string> $cols
     * @return list<string>
     */
    private function normalizeColumns(array $cols): array
    {
        $normalized = array_map(static fn($c) => strtolower(trim((string) $c)), $cols);
        sort($normalized);
        return array_values($normalized);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Schema;

use App\Environment;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

/**
 * DatabaseHealthService.
 *
 * Provides production-safe, read-only inspection of active database connection health
 * and compares actual live schema objects against the canonical ExpectedSchemaManifest.
 *
 * Detects missing tables, missing/mismatched columns, primary keys, indexes, and foreign keys.
 * Never performs DDL/DML mutations or alters schema state.
 */
final class DatabaseHealthService
{
    public const STATUS_HEALTHY = 'HEALTHY';
    public const STATUS_WARNING = 'WARNING';
    public const STATUS_CRITICAL = 'CRITICAL';
    public const STATUS_DISCONNECTED = 'DISCONNECTED';

    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    /**
     * Inspects active database connection health.
     *
     * @return array{
     *     status: string,
     *     connected: bool,
     *     database: string,
     *     driver: string,
     *     host: string,
     *     port: string,
     *     serverVersion: string,
     *     charset: string,
     *     collation: string,
     *     tableCount: int,
     *     error: ?string
     * }
     */
    public function getConnectionHealth(): array
    {
        $health = [
            'status' => 'disconnected',
            'connected' => false,
            'database' => Environment::dbName(),
            'driver' => $this->db->getDriverName() ?: 'mysql',
            'host' => Environment::dbHost(),
            'port' => Environment::dbPort(),
            'serverVersion' => 'Unknown',
            'charset' => Environment::dbCharset(),
            'collation' => 'Unknown',
            'tableCount' => 0,
            'error' => null,
        ];

        try {
            $this->db->open();
            $health['connected'] = true;
            $health['status'] = 'connected';

            try {
                $dbName = (string) $this->db->createCommand('SELECT DATABASE()')->queryScalar();
                if ($dbName !== '') {
                    $health['database'] = $dbName;
                }
            } catch (Throwable) {
                // Keep Environment::dbName()
            }

            try {
                $health['serverVersion'] = (string) $this->db->getServerVersion();
            } catch (Throwable) {
                $health['serverVersion'] = 'Unknown';
            }

            try {
                $charset = (string) $this->db->createCommand('SELECT @@character_set_database')->queryScalar();
                if ($charset !== '') {
                    $health['charset'] = $charset;
                }
            } catch (Throwable) {
                // Keep default
            }

            try {
                $collation = (string) $this->db->createCommand('SELECT @@collation_database')->queryScalar();
                if ($collation !== '') {
                    $health['collation'] = $collation;
                }
            } catch (Throwable) {
                // Keep default
            }

            try {
                $health['tableCount'] = count($this->db->getSchema()->getTableNames());
            } catch (Throwable) {
                $health['tableCount'] = 0;
            }
        } catch (Throwable $e) {
            $health['status'] = 'error';
            $health['connected'] = false;
            $health['error'] = 'Database connection error: ' . $e->getMessage();
        }

        return $health;
    }

    /**
     * Inspects actual database schema and compares it with ExpectedSchemaManifest.
     *
     * @return array{
     *     status: string,
     *     statusMessage: string,
     *     totalExpectedTables: int,
     *     existingTableCount: int,
     *     healthyTableCount: int,
     *     missingTableCount: int,
     *     mismatchedTableCount: int,
     *     unexpectedTableCount: int,
     *     totalExpectedColumns: int,
     *     missingColumnCount: int,
     *     columnMismatchCount: int,
     *     missingIndexCount: int,
     *     missingForeignKeyCount: int,
     *     missingTables: list<string>,
     *     unexpectedTables: list<string>,
     *     tables: array<string, array>,
     *     differences: list<array>,
     *     error: ?string
     * }
     */
    public function inspectSchema(): array
    {
        $connHealth = $this->getConnectionHealth();
        if (!$connHealth['connected']) {
            return [
                'status' => self::STATUS_DISCONNECTED,
                'statusMessage' => 'Database connection failed: ' . ($connHealth['error'] ?? 'Unable to connect to database.'),
                'totalExpectedTables' => count(ExpectedSchemaManifest::getTableNames()),
                'existingTableCount' => 0,
                'healthyTableCount' => 0,
                'missingTableCount' => count(ExpectedSchemaManifest::getTableNames()),
                'mismatchedTableCount' => 0,
                'unexpectedTableCount' => 0,
                'totalExpectedColumns' => 352,
                'missingColumnCount' => 352,
                'columnMismatchCount' => 0,
                'missingIndexCount' => 0,
                'missingForeignKeyCount' => 0,
                'missingTables' => ExpectedSchemaManifest::getTableNames(),
                'unexpectedTables' => [],
                'tables' => [],
                'differences' => [[
                    'tableName' => 'Database Connection',
                    'objectType' => 'Connection',
                    'objectName' => $connHealth['database'],
                    'expected' => 'Active, reachable MySQL/MariaDB database',
                    'actual' => 'Connection failed',
                    'severity' => 'critical',
                    'description' => 'Could not connect to database: ' . ($connHealth['error'] ?? 'Unknown connection error'),
                    'recommendation' => 'Check database host, port, credentials, and service status in .env configuration.',
                ]],
                'error' => $connHealth['error'],
            ];
        }

        $expectedTables = ExpectedSchemaManifest::getTables();
        $dbSchema = $this->db->getSchema();
        $existingTables = $dbSchema->getTableNames();
        $existingMap = array_fill_keys($existingTables, true);

        $missingTables = [];
        $unexpectedTables = [];
        $tableReports = [];
        $differences = [];

        $totalExpectedColumns = 0;
        $missingColumnCount = 0;
        $columnMismatchCount = 0;
        $missingIndexCount = 0;
        $missingForeignKeyCount = 0;
        $healthyTableCount = 0;
        $mismatchedTableCount = 0;

        // Check for unexpected extra tables in database
        foreach ($existingTables as $tableName) {
            if ($tableName === 'migration') {
                continue; // System migration history table is expected and managed by MigrationSyncService
            }
            if (!isset($expectedTables[$tableName])) {
                $unexpectedTables[] = $tableName;
            }
        }

        // Compare expected tables against live database
        foreach ($expectedTables as $tableName => $tableDef) {
            $expectedCols = $tableDef['columns'] ?? [];
            $expectedIndexes = $tableDef['indexes'] ?? [];
            $expectedFks = $tableDef['foreignKeys'] ?? [];
            $totalExpectedColumns += count($expectedCols);

            $tableReport = [
                'tableName' => $tableName,
                'category' => $tableDef['category'] ?? 'core',
                'label' => $tableDef['label'] ?? ucfirst($tableName),
                'required' => !empty($tableDef['required']),
                'exists' => isset($existingMap[$tableName]),
                'status' => 'HEALTHY',
                'columnCountExpected' => count($expectedCols),
                'columnCountActual' => 0,
                'missingColumns' => [],
                'columnMismatches' => [],
                'missingIndexes' => [],
                'missingForeignKeys' => [],
                'differences' => [],
            ];

            if (!isset($existingMap[$tableName])) {
                // Table is completely missing
                $missingTables[] = $tableName;
                $tableReport['status'] = 'MISSING';
                $missingColumnCount += count($expectedCols);

                $diffItem = [
                    'tableName' => $tableName,
                    'objectType' => 'Table',
                    'objectName' => $tableName,
                    'expected' => "Table `{$tableName}` with " . count($expectedCols) . ' columns',
                    'actual' => 'Table does not exist in connected database',
                    'severity' => $tableReport['required'] ? 'critical' : 'warning',
                    'description' => "Required application table `{$tableName}` ({$tableReport['label']}) is missing from the database.",
                    'recommendation' => "Execute migration to create table `{$tableName}`.",
                ];
                $differences[] = $diffItem;
                $tableReport['differences'][] = $diffItem;

                $tableReports[$tableName] = $tableReport;
                continue;
            }

            // Table exists: inspect structure
            $tableSchema = $dbSchema->getTableSchema($tableName);
            if (!$tableSchema instanceof TableSchemaInterface) {
                $tableReport['status'] = 'UNABLE_TO_INSPECT';
                $tableReports[$tableName] = $tableReport;
                continue;
            }

            $actualCols = $tableSchema->getColumns();
            $tableReport['columnCountActual'] = count($actualCols);

            // 1. Column Inspection
            foreach ($expectedCols as $colName => $colDef) {
                if (!isset($actualCols[$colName])) {
                    $tableReport['missingColumns'][] = $colName;
                    $missingColumnCount++;

                    $diffItem = [
                        'tableName' => $tableName,
                        'objectType' => 'Column',
                        'objectName' => "{$tableName}.{$colName}",
                        'expected' => "Column `{$colName}` {$colDef['dbType']}",
                        'actual' => 'Column missing',
                        'severity' => 'critical',
                        'description' => "Table `{$tableName}` is missing required column `{$colName}`.",
                        'recommendation' => "Execute ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$colDef['dbType']}.",
                    ];
                    $differences[] = $diffItem;
                    $tableReport['differences'][] = $diffItem;
                    continue;
                }

                $actualCol = $actualCols[$colName];
                $typeMismatch = false;

                // Basic type family compatibility check
                $expectedType = (string) ($colDef['type'] ?? '');
                $actualType = (string) $actualCol->getType();
                if ($expectedType !== '' && $actualType !== '' && $expectedType !== $actualType) {
                    // Ignore benign type aliases (e.g. integer vs smallint/tinyint when both numeric)
                    if (!($this->isNumericType($expectedType) && $this->isNumericType($actualType))) {
                        $typeMismatch = true;
                        $columnMismatchCount++;

                        $diffItem = [
                            'tableName' => $tableName,
                            'objectType' => 'Column Type',
                            'objectName' => "{$tableName}.{$colName}",
                            'expected' => "Type `{$expectedType}` ({$colDef['dbType']})",
                            'actual' => "Type `{$actualType}` ({$actualCol->getDbType()})",
                            'severity' => 'warning',
                            'description' => "Column `{$tableName}.{$colName}` data type mismatch: expected `{$expectedType}`, found `{$actualType}`.",
                            'recommendation' => "Review schema compatibility for column `{$tableName}.{$colName}`.",
                        ];
                        $differences[] = $diffItem;
                        $tableReport['differences'][] = $diffItem;
                    }
                }

                // Nullability check
                $expectedNullable = (bool) ($colDef['allowNull'] ?? true);
                $actualNullable = (bool) $actualCol->isAllowNull();
                if ($expectedNullable !== $actualNullable) {
                    $columnMismatchCount++;
                    $diffItem = [
                        'tableName' => $tableName,
                        'objectType' => 'Column Nullability',
                        'objectName' => "{$tableName}.{$colName}",
                        'expected' => $expectedNullable ? 'NULL permitted' : 'NOT NULL required',
                        'actual' => $actualNullable ? 'NULL permitted' : 'NOT NULL enforced',
                        'severity' => 'warning',
                        'description' => "Column `{$tableName}.{$colName}` nullability mismatch: expected " . ($expectedNullable ? 'NULL' : 'NOT NULL') . ', found ' . ($actualNullable ? 'NULL' : 'NOT NULL') . '.',
                        'recommendation' => "Execute ALTER TABLE `{$tableName}` MODIFY `{$colName}` ... to correct nullability constraint.",
                    ];
                    $differences[] = $diffItem;
                    $tableReport['differences'][] = $diffItem;
                }
            }

            // 2. Primary Key Check
            $actualPk = $tableSchema->getPrimaryKey();
            $expectedPk = $tableDef['primaryKey'] ?? [];
            if ($this->normalizeColumns($actualPk) !== $this->normalizeColumns($expectedPk)) {
                $diffItem = [
                    'tableName' => $tableName,
                    'objectType' => 'Primary Key',
                    'objectName' => "{$tableName}.PRIMARY",
                    'expected' => 'PK columns: [' . implode(', ', $expectedPk) . ']',
                    'actual' => 'PK columns: [' . implode(', ', $actualPk) . ']',
                    'severity' => 'critical',
                    'description' => "Table `{$tableName}` primary key mismatch.",
                    'recommendation' => "Verify primary key definition for table `{$tableName}`.",
                ];
                $differences[] = $diffItem;
                $tableReport['differences'][] = $diffItem;
            }

            // 3. Indexes Check
            try {
                $actualIndexes = $dbSchema->getTableIndexes($tableName);
                foreach ($expectedIndexes as $idxName => $idxDef) {
                    $idxCols = (array) ($idxDef['columns'] ?? []);
                    $isUnique = !empty($idxDef['isUnique']);
                    $isPrimary = !empty($idxDef['isPrimary']);

                    if ($isPrimary) {
                        continue; // Already checked in PK
                    }

                    $foundIndex = false;
                    foreach ($actualIndexes as $actIdx) {
                        if ($this->normalizeColumns($actIdx->getColumnNames()) === $this->normalizeColumns($idxCols)) {
                            // Check uniqueness if expected to be unique
                            if ($isUnique && !$actIdx->isUnique()) {
                                continue;
                            }
                            $foundIndex = true;
                            break;
                        }
                    }

                    if (!$foundIndex) {
                        $missingIndexCount++;
                        $tableReport['missingIndexes'][] = $idxName;

                        $diffItem = [
                            'tableName' => $tableName,
                            'objectType' => 'Index',
                            'objectName' => "{$tableName}.{$idxName}",
                            'expected' => ($isUnique ? 'Unique ' : '') . 'Index on columns [' . implode(', ', $idxCols) . ']',
                            'actual' => 'Index missing',
                            'severity' => $isUnique ? 'critical' : 'warning',
                            'description' => "Table `{$tableName}` is missing " . ($isUnique ? 'unique ' : '') . "index on columns [" . implode(', ', $idxCols) . '].',
                            'recommendation' => "Execute CREATE " . ($isUnique ? 'UNIQUE ' : '') . "INDEX `{$idxName}` ON `{$tableName}` (" . implode(', ', $idxCols) . ').',
                        ];
                        $differences[] = $diffItem;
                        $tableReport['differences'][] = $diffItem;
                    }
                }
            } catch (Throwable) {
                // Skip if index inspection unsupported
            }

            // 4. Foreign Key Check
            $actualFks = $tableSchema->getForeignKeys();
            foreach ($expectedFks as $fkName => $fkDef) {
                $fkRefTable = (string) ($fkDef[0] ?? '');
                $fkColMap = array_slice($fkDef, 1);
                unset($fkColMap['onDelete'], $fkColMap['onUpdate']);

                $foundFk = false;
                foreach ($actualFks as $actFk) {
                    $actRefTable = (string) ($actFk[0] ?? '');
                    $actColMap = array_slice($actFk, 1);
                    if ($actRefTable === $fkRefTable && $actColMap === $fkColMap) {
                        $foundFk = true;
                        break;
                    }
                }

                if (!$foundFk) {
                    $missingForeignKeyCount++;
                    $tableReport['missingForeignKeys'][] = $fkName;

                    $diffItem = [
                        'tableName' => $tableName,
                        'objectType' => 'Foreign Key',
                        'objectName' => "{$tableName}.{$fkName}",
                        'expected' => "FK to `{$fkRefTable}` (" . json_encode($fkColMap) . ')',
                        'actual' => 'Foreign key constraint missing',
                        'severity' => 'warning',
                        'description' => "Table `{$tableName}` is missing foreign key constraint to `{$fkRefTable}`.",
                        'recommendation' => "Execute ALTER TABLE `{$tableName}` ADD CONSTRAINT ... FOREIGN KEY.",
                    ];
                    $differences[] = $diffItem;
                    $tableReport['differences'][] = $diffItem;
                }
            }

            // Assign table status
            if (!empty($tableReport['missingColumns'])) {
                $tableReport['status'] = 'INCOMPLETE';
                $mismatchedTableCount++;
            } elseif (!empty($tableReport['differences'])) {
                $tableReport['status'] = 'MISMATCH';
                $mismatchedTableCount++;
            } else {
                $tableReport['status'] = 'HEALTHY';
                $healthyTableCount++;
            }

            $tableReports[$tableName] = $tableReport;
        }

        // Determine overall status
        $missingTableCount = count($missingTables);
        $unexpectedTableCount = count($unexpectedTables);

        if ($missingTableCount > 0) {
            $overallStatus = self::STATUS_CRITICAL;
            $statusMessage = "Critical schema gap: {$missingTableCount} required table(s) and {$missingColumnCount} column(s) are missing from the database.";
        } elseif ($missingColumnCount > 0 || $columnMismatchCount > 0) {
            $overallStatus = self::STATUS_WARNING;
            $statusMessage = "Schema discrepancies detected: {$missingColumnCount} missing column(s), {$columnMismatchCount} type/nullability mismatch(es), and {$missingIndexCount} missing index(es).";
        } elseif ($missingIndexCount > 0 || $missingForeignKeyCount > 0) {
            $overallStatus = self::STATUS_WARNING;
            $statusMessage = "Schema mostly healthy: {$missingIndexCount} missing index(es) or {$missingForeignKeyCount} missing constraint(s) detected.";
        } else {
            $overallStatus = self::STATUS_HEALTHY;
            $statusMessage = 'Database schema is fully compatible and synchronized with the Reviews Sorted application requirements.';
        }

        return [
            'status' => $overallStatus,
            'statusMessage' => $statusMessage,
            'totalExpectedTables' => count($expectedTables),
            'existingTableCount' => count($existingTables),
            'healthyTableCount' => $healthyTableCount,
            'missingTableCount' => $missingTableCount,
            'mismatchedTableCount' => $mismatchedTableCount,
            'unexpectedTableCount' => $unexpectedTableCount,
            'totalExpectedColumns' => $totalExpectedColumns,
            'missingColumnCount' => $missingColumnCount,
            'columnMismatchCount' => $columnMismatchCount,
            'missingIndexCount' => $missingIndexCount,
            'missingForeignKeyCount' => $missingForeignKeyCount,
            'missingTables' => $missingTables,
            'unexpectedTables' => $unexpectedTables,
            'tables' => $tableReports,
            'differences' => $differences,
            'error' => null,
        ];
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

    private function isNumericType(string $type): bool
    {
        return in_array($type, ['integer', 'int', 'smallint', 'tinyint', 'bigint', 'mediumint'], true);
    }
}

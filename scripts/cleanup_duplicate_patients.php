<?php
// One-time cleanup tool for duplicate patients.
// Default mode is dry-run. Use --apply to perform updates/deletes.
//
// Usage:
//   php scripts/cleanup_duplicate_patients.php
//   php scripts/cleanup_duplicate_patients.php --apply
//   php scripts/cleanup_duplicate_patients.php --apply --verbose

declare(strict_types=1);

require_once __DIR__ . '/../backend/config/database.php';

final class DSU
{
    private array $parent = [];

    public function add(int $x): void
    {
        if (!isset($this->parent[$x])) {
            $this->parent[$x] = $x;
        }
    }

    public function find(int $x): int
    {
        $this->add($x);
        if ($this->parent[$x] !== $x) {
            $this->parent[$x] = $this->find($this->parent[$x]);
        }
        return $this->parent[$x];
    }

    public function union(int $a, int $b): void
    {
        $ra = $this->find($a);
        $rb = $this->find($b);
        if ($ra !== $rb) {
            $this->parent[$rb] = $ra;
        }
    }
}

function hasFlag(array $argv, string $flag): bool
{
    return in_array($flag, $argv, true);
}

function parseIdList(string $csv): array
{
    $ids = array_map(static fn($v) => (int)$v, explode(',', $csv));
    $ids = array_values(array_unique(array_filter($ids, static fn($v) => $v > 0)));
    sort($ids, SORT_NUMERIC);
    return $ids;
}

function fetchRows(PDO $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchPatientNames(PDO $db, array $ids): array
{
    if (empty($ids)) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = fetchRows(
        $db,
        "SELECT id, full_name, national_id, dob, phone
         FROM patients
         WHERE id IN ($placeholders)
         ORDER BY id ASC",
        $ids
    );
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['id']] = $r;
    }
    return $map;
}

function listPatientIdTables(PDO $db): array
{
    $rows = fetchRows(
        $db,
        "SELECT c.TABLE_NAME
         FROM INFORMATION_SCHEMA.COLUMNS c
         JOIN INFORMATION_SCHEMA.TABLES t
           ON t.TABLE_SCHEMA = c.TABLE_SCHEMA
          AND t.TABLE_NAME = c.TABLE_NAME
         WHERE c.TABLE_SCHEMA = DATABASE()
           AND c.COLUMN_NAME = 'patient_id'
           AND c.TABLE_NAME <> 'patients'
           AND t.TABLE_TYPE = 'BASE TABLE'
         ORDER BY c.TABLE_NAME ASC"
    );
    return array_map(static fn($r) => (string)$r['TABLE_NAME'], $rows);
}

function buildDuplicateClusters(PDO $db): array
{
    $dsu = new DSU();
    $memberIds = [];

    $dupByNationalId = fetchRows(
        $db,
        "SELECT UPPER(TRIM(national_id)) AS dedupe_key,
                GROUP_CONCAT(id ORDER BY id ASC) AS ids_csv
         FROM patients
         WHERE COALESCE(TRIM(national_id), '') <> ''
         GROUP BY UPPER(TRIM(national_id))
         HAVING COUNT(*) > 1"
    );
    foreach ($dupByNationalId as $row) {
        $ids = parseIdList((string)$row['ids_csv']);
        if (count($ids) < 2) continue;
        $first = $ids[0];
        foreach ($ids as $id) {
            $dsu->add($id);
            $memberIds[$id] = true;
        }
        for ($i = 1; $i < count($ids); $i++) {
            $dsu->union($first, $ids[$i]);
        }
    }

    $dupByIdentity = fetchRows(
        $db,
        "SELECT UPPER(TRIM(full_name)) AS name_key,
                dob,
                REPLACE(TRIM(phone), ' ', '') AS phone_key,
                GROUP_CONCAT(id ORDER BY id ASC) AS ids_csv
         FROM patients
         WHERE COALESCE(TRIM(full_name), '') <> ''
           AND dob IS NOT NULL
           AND COALESCE(TRIM(phone), '') <> ''
         GROUP BY UPPER(TRIM(full_name)), dob, REPLACE(TRIM(phone), ' ', '')
         HAVING COUNT(*) > 1"
    );
    foreach ($dupByIdentity as $row) {
        $ids = parseIdList((string)$row['ids_csv']);
        if (count($ids) < 2) continue;
        $first = $ids[0];
        foreach ($ids as $id) {
            $dsu->add($id);
            $memberIds[$id] = true;
        }
        for ($i = 1; $i < count($ids); $i++) {
            $dsu->union($first, $ids[$i]);
        }
    }

    $groups = [];
    foreach (array_keys($memberIds) as $id) {
        $root = $dsu->find((int)$id);
        if (!isset($groups[$root])) {
            $groups[$root] = [];
        }
        $groups[$root][] = (int)$id;
    }

    $clusters = [];
    foreach ($groups as $ids) {
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);
        if (count($ids) < 2) continue;
        $canonical = $ids[0];
        $duplicates = array_slice($ids, 1);
        $clusters[] = [
            'canonical' => $canonical,
            'duplicates' => $duplicates,
            'all' => $ids
        ];
    }

    usort($clusters, static fn($a, $b) => $a['canonical'] <=> $b['canonical']);
    return $clusters;
}

function run(): int
{
    global $argv;

    $apply = hasFlag($argv, '--apply');
    $verbose = hasFlag($argv, '--verbose');

    $db = (new Database())->getConnection();
    if (!$db) {
        fwrite(STDERR, "Failed to connect to database.\n");
        return 2;
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $patientTables = listPatientIdTables($db);
    $clusters = buildDuplicateClusters($db);

    echo "Duplicate patient cleanup\n";
    echo "Mode: " . ($apply ? "APPLY" : "DRY-RUN") . "\n";
    echo "Tables with patient_id: " . count($patientTables) . "\n";
    echo "Duplicate clusters found: " . count($clusters) . "\n\n";

    if (empty($clusters)) {
        echo "No duplicate clusters detected.\n";
        return 0;
    }

    $allIds = [];
    foreach ($clusters as $c) {
        foreach ($c['all'] as $id) $allIds[$id] = true;
    }
    $patientMap = fetchPatientNames($db, array_map('intval', array_keys($allIds)));

    $totalRepointed = 0;
    $totalDeleted = 0;
    $errors = 0;

    foreach ($clusters as $idx => $cluster) {
        $canonical = (int)$cluster['canonical'];
        $duplicates = $cluster['duplicates'];
        $canonInfo = $patientMap[$canonical] ?? ['full_name' => 'Unknown', 'national_id' => null, 'dob' => null, 'phone' => null];

        echo "Cluster " . ($idx + 1) . ": keep #{$canonical} ({$canonInfo['full_name']})\n";
        echo "  Merge duplicates: " . implode(', ', array_map(static fn($id) => "#{$id}", $duplicates)) . "\n";

        foreach ($duplicates as $dupId) {
            $dupInfo = $patientMap[$dupId] ?? ['full_name' => 'Unknown'];
            if ($verbose) {
                echo "    Processing duplicate #{$dupId} ({$dupInfo['full_name']})\n";
            }

            if (!$apply) {
                foreach ($patientTables as $table) {
                    $countRow = fetchRows($db, "SELECT COUNT(*) AS c FROM `{$table}` WHERE patient_id = ?", [$dupId]);
                    $count = (int)($countRow[0]['c'] ?? 0);
                    if ($count > 0) {
                        echo "      [DRY] {$table}: {$count} row(s) -> patient_id {$canonical}\n";
                    }
                }
                echo "      [DRY] patients: delete id {$dupId}\n";
                continue;
            }

            try {
                $db->beginTransaction();
                $repointedForDup = 0;

                foreach ($patientTables as $table) {
                    $stmt = $db->prepare("UPDATE `{$table}` SET patient_id = ? WHERE patient_id = ?");
                    $stmt->execute([$canonical, $dupId]);
                    $affected = $stmt->rowCount();
                    $repointedForDup += $affected;
                    if ($verbose && $affected > 0) {
                        echo "      {$table}: repointed {$affected}\n";
                    }
                }

                $del = $db->prepare("DELETE FROM patients WHERE id = ?");
                $del->execute([$dupId]);
                $deleted = $del->rowCount();
                if ($deleted !== 1) {
                    throw new RuntimeException("Expected to delete patient {$dupId}, deleted {$deleted} rows.");
                }

                $db->commit();
                $totalRepointed += $repointedForDup;
                $totalDeleted += 1;
                echo "      Applied: repointed {$repointedForDup}, deleted #{$dupId}\n";
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                $errors++;
                echo "      ERROR on #{$dupId}: {$e->getMessage()}\n";
            }
        }
        echo "\n";
    }

    echo "Summary\n";
    echo "  Total repointed references: {$totalRepointed}\n";
    echo "  Total duplicate patients deleted: {$totalDeleted}\n";
    echo "  Errors: {$errors}\n";

    return $errors > 0 ? 1 : 0;
}

exit(run());


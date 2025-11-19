<?php
// Generates a CSV of ambiguous/conflicting rows for carwash_profiles -> carwashes migration.
// Run this on the server where the app and DB are available (non-destructive SELECTs only).

require_once __DIR__ . '/../../backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();

$outDir = __DIR__ . '/../../tools/reports';
@mkdir($outDir, 0755, true);
$outFile = $outDir . '/carwash_migration_ambiguous_rows.csv';
$fp = fopen($outFile, 'w');
if (!$fp) {
    echo "Failed to open $outFile for writing\n";
    exit(1);
}

function writeSummary($fp, $text) {
    fputcsv($fp, ['---', $text]);
}

writeSummary($fp, 'Ambiguous rows export for carwash_profiles -> carwashes migration');
writeSummary($fp, 'Generated: ' . date('Y-m-d H:i:s'));
writeSummary($fp, 'Non-destructive: This script runs SELECT queries only. No changes are made to the database.');

// Header row for data
$header = [
    'category', 'source_id', 'source_user_id', 'business_name', 'address', 'contact_phone', 'social_media', 'extracted_mobile_phone', 'conflict_target_id', 'conflict_target_user_id', 'conflict_target_name', 'conflict_target_address', 'notes'
];
fputcsv($fp, $header);

$totalCounts = ['duplicate_name_address'=>0, 'conflicts_with_carwashes'=>0, 'mobile_found'=>0, 'mobile_missing'=>0, 'same_phone_duplicates'=>0];

// 1) Duplicate business_name + address in source
try {
    $sql = "SELECT business_name, address, COUNT(*) AS cnt, GROUP_CONCAT(id) AS ids, GROUP_CONCAT(user_id) AS user_ids, GROUP_CONCAT(contact_phone) AS phones FROM carwash_profiles GROUP BY business_name, address HAVING cnt > 1";
    $dups = $db->fetchAll($sql);
    foreach ($dups as $r) {
        $ids = explode(',', $r['ids']);
        $userIds = explode(',', $r['user_ids']);
        $phones = explode(',', $r['phones']);
        $totalCounts['duplicate_name_address'] += count($ids);
        // For each member of the duplicate group, emit a row with category duplicate_name_address
        foreach ($ids as $i => $sid) {
            $row = [
                'duplicate_name_address',
                $sid,
                $userIds[$i] ?? '',
                $r['business_name'],
                $r['address'],
                $phones[$i] ?? '',
                '', // social_media unknown in GROUP_CONCAT
                '',
                '', '', '', '',
                'Duplicate business_name+address group (ids: ' . $r['ids'] . ')'
            ];
            fputcsv($fp, $row);
        }
    }
} catch (Exception $e) {
    fputcsv($fp, ['error', '', '', '', '', '', '', '', '', '', '', '', 'Failed duplicate_name_address query: ' . $e->getMessage()]);
}

// 2) Potential conflicts: rows in carwash_profiles that match existing carwashes (by user_id OR exact name)
try {
    // This query assumes `carwashes` table exists. If not, it will throw; we catch and note it.
    $sql = "SELECT cp.id AS source_id, cp.user_id AS source_user_id, cp.business_name AS source_name, cp.address AS source_address, cp.contact_phone, cp.social_media, cw.id AS target_id, cw.user_id AS target_user_id, cw.name AS target_name, cw.address AS target_address
            FROM carwash_profiles cp
            LEFT JOIN carwashes cw ON (cw.user_id IS NOT NULL AND cw.user_id = cp.user_id) OR (cw.name = cp.business_name)
            WHERE cw.id IS NOT NULL LIMIT 500";
    $conflicts = $db->fetchAll($sql);
    foreach ($conflicts as $r) {
        $totalCounts['conflicts_with_carwashes']++;
        // try to extract mobile_phone from social_media JSON
        $extracted = '';
        if (!empty($r['social_media'])) {
            $sm = json_decode($r['social_media'], true);
            if (is_array($sm)) {
                if (!empty($sm['mobile_phone'])) $extracted = $sm['mobile_phone'];
                elseif (!empty($sm['mobile'])) $extracted = $sm['mobile'];
            }
        }
        $row = [
            'conflict_with_carwashes',
            $r['source_id'],
            $r['source_user_id'],
            $r['source_name'],
            $r['source_address'],
            $r['contact_phone'] ?? '',
            $r['social_media'] ?? '',
            $extracted,
            $r['target_id'] ?? '',
            $r['target_user_id'] ?? '',
            $r['target_name'] ?? '',
            $r['target_address'] ?? '',
            'Matches existing carwashes by user_id or exact name'
        ];
        fputcsv($fp, $row);
    }
} catch (Exception $e) {
    fputcsv($fp, ['error', '', '', '', '', '', '', '', '', '', '', '', 'Failed conflicts_with_carwashes query: ' . $e->getMessage()]);
}

// 3) Extract mobile_phone from social_media JSON where present
try {
    $sql = "SELECT id, user_id, business_name, address, contact_phone, social_media FROM carwash_profiles WHERE social_media IS NOT NULL LIMIT 2000";
    $rows = $db->fetchAll($sql);
    foreach ($rows as $r) {
        $sm = json_decode($r['social_media'], true);
        $extracted = '';
        if (is_array($sm)) {
            if (!empty($sm['mobile_phone'])) $extracted = $sm['mobile_phone'];
            elseif (!empty($sm['mobile'])) $extracted = $sm['mobile'];
            elseif (!empty($sm['phone'])) $extracted = $sm['phone'];
        }
        if ($extracted !== '') {
            $totalCounts['mobile_found']++;
            fputcsv($fp, ['mobile_found', $r['id'], $r['user_id'], $r['business_name'], $r['address'], $r['contact_phone'] ?? '', $r['social_media'], $extracted, '', '', '', '', 'mobile_phone extracted from social_media']);
        } else {
            $totalCounts['mobile_missing']++;
            fputcsv($fp, ['mobile_missing', $r['id'], $r['user_id'], $r['business_name'], $r['address'], $r['contact_phone'] ?? '', $r['social_media'], '', '', '', '', '', 'social_media JSON present but no recognizable mobile key']);
        }
    }
} catch (Exception $e) {
    fputcsv($fp, ['error', '', '', '', '', '', '', '', '', '', '', '', 'Failed social_media extraction query: ' . $e->getMessage()]);
}

// 4) Potential duplicates by same contact_phone across different IDs
try {
    $sql = "SELECT a.id AS id_a, b.id AS id_b, a.business_name AS name_a, b.business_name AS name_b, a.address AS address_a, b.address AS address_b, a.contact_phone
            FROM carwash_profiles a
            JOIN carwash_profiles b ON a.id < b.id
            AND a.contact_phone IS NOT NULL AND a.contact_phone = b.contact_phone
            LIMIT 500";
    $samePhone = $db->fetchAll($sql);
    foreach ($samePhone as $r) {
        $totalCounts['same_phone_duplicates']++;
        fputcsv($fp, ['same_phone_duplicate', $r['id_a'], '', $r['name_a'], $r['address_a'], $r['contact_phone'], '', '', $r['id_b'], '', $r['name_b'], $r['address_b'], 'Same contact_phone across two source rows']);
    }
} catch (Exception $e) {
    fputcsv($fp, ['error', '', '', '', '', '', '', '', '', '', '', '', 'Failed same_phone_duplicates query: ' . $e->getMessage()]);
}

// Final summary rows
fputcsv($fp, []);
writeSummary($fp, 'SUMMARY:');
writeSummary($fp, 'duplicate_name_address rows (approx): ' . $totalCounts['duplicate_name_address']);
writeSummary($fp, 'conflicts_with_carwashes rows: ' . $totalCounts['conflicts_with_carwashes']);
writeSummary($fp, 'mobile_found rows: ' . $totalCounts['mobile_found']);
writeSummary($fp, 'mobile_missing rows: ' . $totalCounts['mobile_missing']);
writeSummary($fp, 'same_phone_duplicates rows: ' . $totalCounts['same_phone_duplicates']);

fclose($fp);

echo "Wrote ambiguous rows CSV to: $outFile\n";
echo "Open the CSV and review entries. If carwashes table is missing, some queries will be skipped and noted in the CSV.\n";

?>
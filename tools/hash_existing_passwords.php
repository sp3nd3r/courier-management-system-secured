<?php
/**
 * ONE-OFF MIGRATION — run once, from the command line, then delete this file.
 *
 *   php tools/hash_existing_passwords.php
 *
 * The original database stored passwords as plaintext ('12345', 'emp1', ...).
 * Now that login uses password_verify(), those rows can never match. This
 * rewrites each one as a bcrypt hash of its current value so existing accounts
 * keep working. Rows that already hold a bcrypt hash are skipped, so running it
 * twice is harmless.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This migration may only be run from the command line.\n");
}

require_once __DIR__ . '/../server/inc/connection.php';
require_once __DIR__ . '/../server/inc/add.php';

$pdo = db();

foreach (array('customer' => 'customer_id', 'employee' => 'emp_id') as $table => $key) {

    $rows = $pdo->query("SELECT `$key`, `password` FROM `$table`")->fetchAll();
    $changed = 0;

    foreach ($rows as $row) {
        $current = $row['password'];

        // Already hashed by this application: leave it alone.
        if (password_get_info($current)['algo']) {
            continue;
        }

        $stmt = $pdo->prepare("UPDATE `$table` SET `password` = ? WHERE `$key` = ?");
        $stmt->execute(array(hash_password($current), $row[$key]));
        $changed++;
    }

    echo str_pad($table, 12) . ": " . $changed . " of " . count($rows) . " row(s) hashed\n";
}

echo "Done. Delete this file now that the migration has run.\n";

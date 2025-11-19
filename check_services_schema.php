<?php
require __DIR__ . '/backend/includes/bootstrap.php';
$db = App\Classes\Database::getInstance();
$cols = $db->fetchAll('DESCRIBE services');

echo "Services table columns:\n";
echo str_repeat('-',50) . "\n";
foreach($cols as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ') ' . ($c['Null']==='YES' ? 'NULL' : 'NOT NULL') . "\n";
}

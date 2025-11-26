<?php
$d = 'c:\\xampp\\htdocs\\carwash_project\\backend\\auth\\uploads\\profiles\\';
echo "Checking: $d\n";
echo (is_writable($d) ? 'writable' : 'not writable') . PHP_EOL;

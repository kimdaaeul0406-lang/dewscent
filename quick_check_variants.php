<?php
require 'includes/db.php';
require 'includes/db_setup.php';

try {
    $tables = db()->fetchAll("SHOW TABLES LIKE 'product_variants'");
    if (empty($tables)) {
        echo "NOT_EXISTS\n";
        echo "Creating table...\n";
        ensure_tables_exist();
        $tables = db()->fetchAll("SHOW TABLES LIKE 'product_variants'");
        if (empty($tables)) {
            echo "FAILED\n";
        } else {
            echo "CREATED\n";
        }
    } else {
        echo "EXISTS\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>

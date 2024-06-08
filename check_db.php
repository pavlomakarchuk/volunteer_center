<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Використовуйте абсолютний шлях до файлу бази даних
    $db = new SQLite3(__DIR__ . '/../volunteers.db');
    $result = $db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='VPO'");

    if ($result) {
        echo "Table VPO exists.";
    } else {
        echo "Table VPO does not exist.";
    }
} catch (Exception $e) {
    echo "Failed to connect to the database: " . $e->getMessage();
}
?>
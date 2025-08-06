<?php
// config.php
// Ganti sesuai kredensial database Anda
$DB_HOST = 'localhost';
$DB_NAME = 'bukutamu';
$DB_USER = 'root';
$DB_PASS = '';

// Buat koneksi PDO
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

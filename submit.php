<?php
// submit.php
require 'config.php';  // pastikan file ini ada dan koneksi PDO-nya benar

// Hanya proses POST, jika bukan POST redirect ke form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Ambil data form
$tanggal   = $_POST['tanggal']   ?? '';
$nama      = trim($_POST['nama'] ?? '');
$instansi  = trim($_POST['instansi'] ?? '');
$tujuan    = trim($_POST['tujuan'] ?? '');
$signature = $_POST['signature'] ?? '';

// Validasi sederhana
$errors = [];
if (empty($tanggal))  $errors[] = 'Tanggal wajib diisi.';
if (empty($nama))     $errors[] = 'Nama wajib diisi.';
if (empty($instansi)) $errors[] = 'Instansi wajib diisi.';
if (empty($tujuan))   $errors[] = 'Tujuan wajib diisi.';
if (empty($signature)) $errors[] = 'Tanda tangan wajib diisi.';
if (!isset($_FILES['TTD']) || $_FILES['TTD']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Upload TTD gagal atau tidak ditemukan.';
}

if (!empty($errors)) {
    // Tampilkan error dan link kembali
    foreach ($errors as $e) {
        echo "<p style='color:red;'>– {$e}</p>";
    }
    echo '<p><a href="index.html">← Kembali ke Form</a></p>';
    exit;
}

// Siapkan folder upload
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
// Siapkan folder csv
$csvDir = __DIR__ . '/data/';
if (!is_dir($csvDir)) {
    mkdir($csvDir, 0755, true);
}


// Proses upload file TTD
$ttdPath = null;
$tmpName = $_FILES['TTD']['tmp_name'];
$ext     = pathinfo($_FILES['TTD']['name'], PATHINFO_EXTENSION);
$newName = 'ttd_' . uniqid() . '.' . $ext;
if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
    $ttdPath = 'uploads/' . $newName;
} else {
    die('Gagal menyimpan file TTD.');
}

// // Proses signature (base64 → PNG)
// $sigPath = null;
// if (preg_match('/^data:image\/png;base64,(.+)$/', $signature, $m)) {
//     $data = base64_decode($m[1]);
//     $sigName = 'sig_' . uniqid() . '.png';
//     file_put_contents($uploadDir . $sigName, $data);
//     $sigPath = 'uploads/' . $sigName;
// } else {
//     die('Format tanda tangan tidak valid.');
// }

// // Simpan ke database
// $sql = "INSERT INTO guest 
//             (tanggal, nama, instansi, tujuan, foto, signature)
//         VALUES 
//             (:tanggal, :nama, :instansi, :tujuan, :foto, :signature)";
// $stmt = $pdo->prepare($sql);
// $stmt->execute([
//     ':tanggal'   => $tanggal,
//     ':nama'      => $nama,
//     ':instansi'  => $instansi,
//     ':tujuan'    => $tujuan,
//     ':foto'       => $ttdPath,
//     ':signature' => $sigPath,
// ]);

// ====== Simpan Signature (base64 PNG) ======
$sigPath = '';
if (preg_match('/^data:image\/png;base64,/', $signature)) {
    $signature = str_replace('data:image/png;base64,', '', $signature);
    $signature = str_replace(' ', '+', $signature);
    $sigData   = base64_decode($signature);
    $sigName   = 'signature_' . uniqid() . '.png';
    $sigPath   = 'uploads/' . $sigName;

    file_put_contents(__DIR__ . '/' . $sigPath, $sigData);
} else {
    die('Tanda tangan tidak valid.');
}

// ====== Generate ID secara manual dari jumlah baris CSV ======
$csvFile = $csvDir . '/guestbook.csv';
$isNew = !file_exists($csvFile);
$id = 1;

if (!$isNew) {
    $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $id = count($lines); // anggap baris pertama adalah header
}

// ====== Simpan ke CSV ======
$fp = fopen($csvFile, 'a');
if ($isNew) {
    fputcsv($fp, ['ID', 'Tanggal', 'Nama', 'Instansi', 'Tujuan', 'Foto', 'Signature', 'Waktu Submit']);
}
fputcsv($fp, [
    $id,
    $tanggal,
    $nama,
    $instansi,
    $tujuan,
    $fotoPath,
    $sigPath,
    date('c')
]);
fclose($fp);

// Redirect ke halaman terima kasih
header('Location: thankyou.html');
exit;

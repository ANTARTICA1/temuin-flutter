<?php
session_start();
include_once '../api/config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$type = $_GET['type'];
$id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

try {
    if ($type === 'user') {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $_SESSION['msg'] = "Pengguna berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Gagal menghapus pengguna atau pengguna adalah admin.";
        }
        header("Location: users.php");
        exit;
    } 
    elseif ($type === 'item') {
        $db->beginTransaction();
        
        // Hapus notifikasi terkait item
        $stmt = $db->prepare("DELETE FROM notifications WHERE item_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Hapus reports terkait item
        $stmt = $db->prepare("DELETE FROM reports WHERE item_id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Hapus item itu sendiri
        $stmt = $db->prepare("DELETE FROM items WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $db->commit();
        $_SESSION['msg'] = "Barang beserta notifikasi & laporan terkait berhasil dihapus.";
        header("Location: items.php");
        exit;
    } 
    elseif ($type === 'report') {
        $stmt = $db->prepare("DELETE FROM reports WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['msg'] = "Laporan berhasil dihapus.";
        header("Location: reports.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Terjadi kesalahan saat menghapus data: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
?>

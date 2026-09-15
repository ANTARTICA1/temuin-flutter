<?php
session_start();

header("Content-Type: text/html; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Temuin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background-color: #2D3142; color: white; }
        .sidebar a { color: #9094A6; text-decoration: none; padding: 15px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: rgba(255,255,255,0.05); border-left: 4px solid #00C9B1; }
        .navbar-top { background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        body { background-color: #F8F9FA; }
    </style>
</head>
<body>
    <div class="d-flex">

        <div class="sidebar flex-shrink-0" style="width: 250px;">
            <div class="p-4">
                <h4 class="mb-0 text-white">Temuin Admin</h4>
                <small style="color: #00C9B1;">Control Panel</small>
            </div>
            <div class="mt-3">
                <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="bi bi-people me-2"></i> Pengguna</a>
                <a href="items.php" class="<?= $current_page == 'items.php' ? 'active' : '' ?>"><i class="bi bi-box-seam me-2"></i> Data Barang</a>
                <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>"><i class="bi bi-flag me-2"></i> Laporan</a>
                <a href="logout.php" class="text-danger mt-5"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
            </div>
        </div>

        <div class="flex-grow-1">
            <nav class="navbar navbar-top px-4 py-3">
                <div class="container-fluid justify-content-end">
                    <span class="navbar-text fw-bold">
                        Halo, <?= htmlspecialchars($_SESSION['admin_name']) ?>
                    </span>
                </div>
            </nav>
            
            <div class="p-4">
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['msg'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['msg']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

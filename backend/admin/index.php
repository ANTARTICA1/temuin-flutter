<?php
include_once '../api/config/database.php';
include_once 'layout/header.php';

$database = new Database();
$db = $database->getConnection();

$userCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$itemCount = $db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$reportCount = $db->query("SELECT COUNT(*) FROM reports")->fetchColumn();
?>

<h2 class="mb-4">Dashboard Overview</h2>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #FF9A9E 0%, #FECFEF 99%, #FECFEF 100%); color: #fff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 text-white-50 fw-bold">Total Pengguna</p>
                        <h2 class="mb-0 fw-bold"><?= $userCount ?></h2>
                    </div>
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); color: #fff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 text-white-50 fw-bold">Total Barang Terdaftar</p>
                        <h2 class="mb-0 fw-bold"><?= $itemCount ?></h2>
                    </div>
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="bi bi-box-seam fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); color: #fff;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 text-black-50 fw-bold">Total Laporan (Reports)</p>
                        <h2 class="mb-0 fw-bold text-dark"><?= $reportCount ?></h2>
                    </div>
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="bi bi-flag fs-2 text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 text-center">
        <h4 class="text-secondary">Selamat Datang di Panel Kontrol Temuin!</h4>
        <p class="text-muted mb-0">Gunakan menu di sebelah kiri untuk mengelola data aplikasi.</p>
        <img src="https://cdni.iconscout.com/illustration/premium/thumb/web-development-3454628-2918517.png" alt="Illustration" style="max-height: 250px; opacity: 0.8; margin-top: 20px;">
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>

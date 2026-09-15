<?php
include_once '../api/config/database.php';
include_once 'layout/header.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Manajemen Pengguna</h2>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td class="ps-4">#<?= $user['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if($user['role'] == 'admin'): ?>
                                <span class="badge bg-danger rounded-pill px-3">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <?php if($user['role'] != 'admin'): ?>
                            <a href="delete.php?type=user&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Semua data terkait (barang, laporan) juga akan terhapus.');">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>

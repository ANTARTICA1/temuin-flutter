<?php
include_once '../api/config/database.php';
include_once 'layout/header.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT i.id, i.name, i.status, i.is_premium, u.name as owner_name 
          FROM items i 
          LEFT JOIN users u ON i.user_id = u.id 
          ORDER BY i.created_at DESC";
$stmt = $db->query($query);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Daftar Barang Terdaftar</h2>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">QR Code ID</th>
                        <th>Nama Barang</th>
                        <th>Pemilik</th>
                        <th>Status</th>
                        <th>Tipe</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td class="ps-4 text-muted font-monospace"><?= htmlspecialchars($item['id']) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['owner_name']) ?></td>
                        <td>
                            <?php 
                                $statusClass = 'bg-secondary';
                                if($item['status'] == 'safe') $statusClass = 'bg-success';
                                if($item['status'] == 'lost') $statusClass = 'bg-danger';
                                if($item['status'] == 'found') $statusClass = 'bg-warning text-dark';
                            ?>
                            <span class="badge <?= $statusClass ?> rounded-pill px-3 text-uppercase">
                                <?= $item['status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if($item['is_premium'] == 1): ?>
                                <span class="badge bg-info text-dark rounded-pill px-3"><i class="bi bi-star-fill text-warning"></i> PRO</span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border rounded-pill px-3">FREE</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="delete.php?type=item&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?');">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'layout/footer.php'; ?>

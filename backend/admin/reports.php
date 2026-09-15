<?php
include_once '../api/config/database.php';
include_once 'layout/header.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT r.id, r.type, r.latitude, r.longitude, r.notes, r.created_at, 
                 i.name as item_name, u.name as reporter_name 
          FROM reports r 
          LEFT JOIN items i ON r.item_id = i.id 
          LEFT JOIN users u ON r.reporter_id = u.id 
          ORDER BY r.created_at DESC";
$stmt = $db->query($query);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Riwayat Laporan</h2>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Waktu Laporan</th>
                        <th>Tipe Laporan</th>
                        <th>Barang Terkait</th>
                        <th>Dilaporkan Oleh</th>
                        <th>Lokasi (GPS)</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reports as $report): ?>
                    <tr>
                        <td class="ps-4 text-muted"><?= date('d M Y, H:i', strtotime($report['created_at'])) ?></td>
                        <td>
                            <?php if($report['type'] == 'found'): ?>
                                <span class="badge bg-info text-dark rounded-pill px-3">Penemuan (Found)</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill px-3">Kehilangan (Lost)</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($report['item_name']) ?></td>
                        <td><?= htmlspecialchars($report['reporter_name']) ?></td>
                        <td>
                            <?php if($report['latitude'] && $report['longitude']): ?>
                                <a href="https://maps.google.com/?q=<?= $report['latitude'] ?>,<?= $report['longitude'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                    <i class="bi bi-geo-alt"></i> Buka Peta
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($report['notes'] ?? '-') ?></td>
                        <td>
                            <a href="delete.php?type=report&id=<?= $report['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
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

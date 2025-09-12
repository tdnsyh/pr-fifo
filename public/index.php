<?php
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../db.php';
include __DIR__ . '/../includes/header.php';

$items = (int) (db()->query("SELECT COUNT(*) c FROM items")->fetch()['c'] ?? 0);
$batches = (int) (db()->query("SELECT COALESCE(SUM(qty_remaining),0) s FROM batches")->fetch()['s'] ?? 0);

$items_fmt = number_format($items, 0, ',', '.');
$batches_fmt = number_format($batches, 0, ',', '.');
?>
<div class="container py-4">
  <div class="d-flex align-items-center mb-3">
    <h2 class="mb-0">Dashboard</h2>
  </div>

  <div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Jumlah Item</div>
          <div class="display-6 fw-semibold"><?= $items_fmt ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small">Total Stok (unit)</div>
          <div class="display-6 fw-semibold"><?= $batches_fmt ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
      <p class="mb-0">
        Gunakan menu di atas untuk mengelola item, pemasok, barang masuk (pembelian), dan barang keluar
        (penjualan/pengeluaran).
      </p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
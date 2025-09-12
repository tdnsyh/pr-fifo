<?php
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../lib/fifo.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && post('action')==='purchase') {
    try {
        record_purchase(
            (int)post('item_id'),
            (int)post('qty'),
            (float)post('cost'),
            post('expiry') ?: null,
            (int)(post('supplier_id') ?: 0)
        );
        flash('success','Barang masuk dicatat.');
    } catch (Throwable $e) {
        flash('error','Gagal mencatat: ' . $e->getMessage());
    }
}

$items = db()->query("SELECT id, name FROM items ORDER BY name")->fetchAll();
$suppliers = db()->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
$batches = db()->query("
  SELECT b.*, i.name AS item_name
  FROM batches b
  JOIN items i ON i.id=b.item_id
  ORDER BY b.id DESC
  LIMIT 50
")->fetchAll();

include __DIR__ . '/../includes/header.php';

function expiry_badge(?string $date): string {
    if (!$date) return '<span class="badge bg-secondary">-</span>';
    $today = new DateTimeImmutable('today');
    $d = new DateTimeImmutable($date);
    $diff = (int)$today->diff($d)->format('%r%a'); // hari
    if ($diff < 0) return '<span class="badge bg-danger">Kadaluarsa</span>';
    if ($diff <= 14) return '<span class="badge bg-warning text-dark">Akan Kadaluarsa</span>';
    return '<span class="badge bg-success">OK</span>';
}
?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Barang Masuk</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPurchase">
      Catat Barang Masuk
    </button>
  </div>

  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= h($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= h($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="h5 mb-0">Batch Terbaru</h3>
      <small class="text-muted">Menampilkan 50 batch terakhir</small>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table">
          <thead class="table-light">
            <tr>
              <th style="width:72px">ID</th>
              <th>Item</th>
              <th class="text-end">Qty Awal</th>
              <th class="text-end">Sisa</th>
              <th class="text-end">Cost/Unit</th>
              <th>Diterima</th>
              <th>Kadaluarsa</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($batches as $b): ?>
              <tr>
                <td><?= (int)$b['id'] ?></td>
                <td><?= h($b['item_name']) ?></td>
                <td class="text-end"><?= (int)$b['qty_initial'] ?></td>
                <td class="text-end"><?= (int)$b['qty_remaining'] ?></td>
                <td class="text-end"><?= number_format((float)$b['cost_per_unit'], 2, ',', '.') ?></td>
                <td><?= h($b['received_at']) ?></td>
                <td><?= $b['expiry_date'] ? h($b['expiry_date']) : '-' ?></td>
                <td><?= expiry_badge($b['expiry_date'] ?? null) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($batches)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Belum ada data batch.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPurchase" tabindex="-1" aria-labelledby="modalPurchaseLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="action" value="purchase">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPurchaseLabel">Catat Barang Masuk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Item</label>
            <select name="item_id" class="form-select" required>
              <option value="">-- pilih --</option>
              <?php foreach ($items as $i): ?>
                <option value="<?= (int)$i['id'] ?>"><?= h($i['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Pilih item terlebih dahulu.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Supplier (opsional)</label>
            <select name="supplier_id" class="form-select">
              <option value="">-- pilih --</option>
              <?php foreach ($suppliers as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Qty</label>
            <input type="number" name="qty" min="1" class="form-control" placeholder="1" required>
            <div class="invalid-feedback">Qty minimal 1.</div>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Cost/Unit</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" step="0.01" name="cost" min="0" class="form-control" placeholder="0.00" required>
            </div>
            <div class="invalid-feedback">Isi harga per unit (≥ 0).</div>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Expiry (opsional)</label>
            <input type="date" name="expiry" class="form-control">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Catat</button>
      </div>
    </form>
  </div>
</div>

<script>
// Validasi Bootstrap 5
(function () {
  'use strict';
  var forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault(); event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();

// Autofocus saat modal dibuka
var modal = document.getElementById('modalPurchase');
modal?.addEventListener('shown.bs.modal', function () {
  modal.querySelector('select[name="item_id"]')?.focus();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

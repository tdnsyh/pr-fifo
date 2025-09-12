<?php
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../lib/fifo.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && post('action')==='issue') {
    $item_id    = (int)post('item_id');
    $qty        = (int)post('qty');
    $sell_price = post('sell_price') !== '' ? (float)post('sell_price') : null;

    try {
        db()->prepare("INSERT INTO issues (created_at) VALUES (NOW())")->execute();
        $issue_id = (int)db()->lastInsertId();

        $res = fifo_issue($issue_id, $item_id, $qty, $sell_price);

        flash('success','Barang keluar: ' . (int)$res['issued'] . ' unit. HPP total: ' . number_format((float)$res['cost_total'], 2, ',', '.'));
    } catch (Throwable $e) {
        flash('error','Gagal mengeluarkan barang: ' . $e->getMessage());
    }
}

$items = db()->query("SELECT id, name FROM items ORDER BY name")->fetchAll();
$recent = db()->query("
    SELECT il.id, i.name AS item_name, il.qty, il.cost_per_unit, il.sell_price_per_unit, il.created_at
    FROM issue_lines il
    JOIN items i ON i.id=il.item_id
    ORDER BY il.id DESC LIMIT 50
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Barang Keluar (FIFO)</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalIssue">
      Keluarkan Barang
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
      <h3 class="h5 mb-0">Transaksi Keluar Terbaru</h3>
      <small class="text-muted">Menampilkan 50 transaksi terakhir</small>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table ">
          <thead class="table-light">
            <tr>
              <th style="width:72px">ID</th>
              <th>Item</th>
              <th class="text-end">Qty</th>
              <th class="text-end">HPP/Unit</th>
              <th class="text-end">Harga Jual/Unit</th>
              <th>Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= h($r['item_name']) ?></td>
                <td class="text-end"><?= (int)$r['qty'] ?></td>
                <td class="text-end"><?= number_format((float)$r['cost_per_unit'], 2, ',', '.') ?></td>
                <td class="text-end"><?= $r['sell_price_per_unit']!==null ? number_format((float)$r['sell_price_per_unit'], 2, ',', '.') : '-' ?></td>
                <td><?= h($r['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi keluar.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Keluarkan Barang -->
<div class="modal fade" id="modalIssue" tabindex="-1" aria-labelledby="modalIssueLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="action" value="issue">
      <div class="modal-header">
        <h5 class="modal-title" id="modalIssueLabel">Keluarkan Barang</h5>
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
          <div class="col-12 col-md-3">
            <label class="form-label">Qty</label>
            <input type="number" name="qty" min="1" class="form-control" placeholder="1" required>
            <div class="invalid-feedback">Qty minimal 1.</div>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Harga Jual/Unit (opsional)</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" step="0.01" name="sell_price" min="0" class="form-control" placeholder="0.00">
            </div>
          </div>
        </div>
        <div class="form-text mt-2">
          * Jika harga jual diisi, akan tersimpan di detail transaksi sebagai <em>sell_price_per_unit</em>.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Keluarkan</button>
      </div>
    </form>
  </div>
</div>

<script>
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

  var modal = document.getElementById('modalIssue');
  modal?.addEventListener('shown.bs.modal', function () {
    modal.querySelector('select[name="item_id"]')?.focus();
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

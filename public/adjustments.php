<?php
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/functions.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && post('action')==='adjust') {
    $batch_id = (int)post('batch_id');
    $delta    = (int)post('delta');

    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Pakai prepared statement + FOR UPDATE agar aman & mengunci baris
        $stmt = $pdo->prepare("SELECT * FROM batches WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $batch_id]);
        $row = $stmt->fetch();
        if (!$row) throw new Exception('Batch tidak ditemukan');

        $new = (int)$row['qty_remaining'] + $delta;
        if ($new < 0) throw new Exception('Penyesuaian menyebabkan stok negatif');

        $pdo->prepare("UPDATE batches SET qty_remaining = :q WHERE id = :id")
            ->execute([':q'=>$new, ':id'=>$batch_id]);

        $pdo->prepare("
            INSERT INTO stock_movements
              (item_id, movement_type, reference_table, reference_id, qty_change, unit_cost, occurred_at, meta)
            VALUES
              (:item_id, 'ADJ', 'batches', :ref, :qty, :cpu, NOW(), JSON_OBJECT('note','manual adjustment'))
        ")->execute([
            ':item_id' => $row['item_id'],
            ':ref'     => $batch_id,
            ':qty'     => $delta,
            ':cpu'     => $row['cost_per_unit']
        ]);

        $pdo->commit();
        flash('success','Stok disesuaikan. (Batch #'.$batch_id.')');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error','Gagal: ' . $e->getMessage());
    }
}

// data untuk select & tabel
$batches = db()->query("
  SELECT b.*, i.name AS item_name
  FROM batches b
  JOIN items i ON i.id=b.item_id
  ORDER BY b.id DESC
  LIMIT 50
")->fetchAll();

include __DIR__ . '/../includes/header.php';

// helper UI status expiry
function expiry_badge(?string $date): string {
    if (!$date) return '<span class="badge bg-secondary">-</span>';
    $today = new DateTimeImmutable('today');
    $d = new DateTimeImmutable($date);
    $diff = (int)$today->diff($d)->format('%r%a');
    if ($diff < 0)  return '<span class="badge bg-danger">Kadaluarsa</span>';
    if ($diff <= 14) return '<span class="badge bg-warning text-dark">Akan Kadaluarsa</span>';
    return '<span class="badge bg-success">OK</span>';
}
?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Penyesuaian Stok (Per Batch)</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdjust">
      + Penyesuaian
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
              <td>#<?= (int)$b['id'] ?></td>
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
              <td colspan="8" class="text-center text-muted py-4">Belum ada batch.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Penyesuaian -->
<div class="modal fade" id="modalAdjust" tabindex="-1" aria-labelledby="modalAdjustLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="action" value="adjust">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAdjustLabel">Penyesuaian Stok (Per Batch)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih Batch</label>
          <select name="batch_id" class="form-select" required>
            <option value="">-- pilih --</option>
            <?php foreach ($batches as $b): ?>
              <option
                value="<?= (int)$b['id'] ?>"
                data-remaining="<?= (int)$b['qty_remaining'] ?>"
              >
                #<?= (int)$b['id'] ?> — <?= h($b['item_name']) ?> (sisa: <?= (int)$b['qty_remaining'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Pilih batch terlebih dahulu.</div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Perubahan Qty (contoh: 5 atau -3)</label>
            <input type="number" name="delta" class="form-control" required>
            <div class="invalid-feedback">Masukkan angka (boleh negatif untuk pengurangan).</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Pratinjau Sisa Baru</label>
            <input type="text" class="form-control" id="previewNew" value="—" readonly>
            <div class="form-text">Tidak boleh menjadi negatif.</div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
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
      // cegah submit jika delta kosong atau new < 0
      var select = form.querySelector('select[name="batch_id"]');
      var delta  = form.querySelector('input[name="delta"]');
      var remain = select?.selectedOptions[0]?.getAttribute('data-remaining');
      var newVal = (remain ? parseInt(remain, 10) : 0) + (delta.value ? parseInt(delta.value, 10) : 0);

      if (!form.checkValidity() || isNaN(newVal) || newVal < 0) {
        event.preventDefault(); event.stopPropagation();
        if (!isNaN(newVal) && newVal < 0) {
          alert('Penyesuaian menyebabkan stok negatif. Silakan periksa kembali.');
        }
      }
      form.classList.add('was-validated');
    }, false);
  });
})();

// Pratinjau sisa baru
(function () {
  const select = document.querySelector('select[name="batch_id"]');
  const delta  = document.querySelector('input[name="delta"]');
  const preview= document.getElementById('previewNew');

  function updatePreview() {
    const remain = parseInt(select?.selectedOptions[0]?.getAttribute('data-remaining') || '0', 10);
    const d = parseInt(delta?.value || '0', 10);
    if (!select?.value) { preview.value = '—'; return; }
    const calc = remain + (isNaN(d) ? 0 : d);
    preview.value = isNaN(calc) ? '—' : calc.toString();
  }

  select?.addEventListener('change', updatePreview);
  delta?.addEventListener('input', updatePreview);

  // Autofocus saat modal dibuka
  var modal = document.getElementById('modalAdjust');
  modal?.addEventListener('shown.bs.modal', function () {
    select?.focus();
    updatePreview();
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

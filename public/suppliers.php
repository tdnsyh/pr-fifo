<?php
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post('action') === 'create') {
    $stmt = db()->prepare("INSERT INTO suppliers (name, phone) VALUES (:name,:phone)");
    $stmt->execute([':name' => post('name'), ':phone' => post('phone')]);
    flash('success', 'Supplier ditambahkan.');
  } elseif (post('action') === 'delete') {
    $stmt = db()->prepare("DELETE FROM suppliers WHERE id=:id");
    $stmt->execute([':id' => post('id')]);
    flash('success', 'Supplier dihapus.');
  }
}

$rows = db()->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Suppliers</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateSupplier">
      + Tambah Supplier
    </button>
  </div>

  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= h($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card border shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table">
          <thead class="table-light">
            <tr>
              <th style="width:72px">ID</th>
              <th>Nama</th>
              <th>Telepon</th>
              <th style="width:120px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int) $r['id'] ?></td>
                <td><?= h($r['name']) ?></td>
                <td><?= h($r['phone']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Hapus supplier ini?')" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Belum ada supplier.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Tambah Supplier -->
<div class="modal fade" id="modalCreateSupplier" tabindex="-1" aria-labelledby="modalCreateSupplierLabel"
  aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCreateSupplierLabel">Tambah Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Supplier</label>
          <input name="name" class="form-control" placeholder="Nama Supplier" required>
          <div class="invalid-feedback">Nama wajib diisi.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Telepon</label>
          <input name="phone" type="tel" class="form-control" placeholder="08xxxxxxxxxx">
          <div class="form-text">Opsional. Contoh: 081234567890</div>
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
  // Bootstrap 5 validation
  (function () {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Autofocus nama saat modal dibuka
  var modal = document.getElementById('modalCreateSupplier');
  modal?.addEventListener('shown.bs.modal', function () {
    modal.querySelector('input[name="name"]')?.focus();
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
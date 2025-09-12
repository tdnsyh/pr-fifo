<?php
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (post('action') === 'create') {
    $stmt = db()->prepare("INSERT INTO items (sku, name, unit) VALUES (:sku,:name,:unit)");
    $stmt->execute([':sku' => post('sku'), ':name' => post('name'), ':unit' => post('unit')]);
    flash('success', 'Item ditambahkan.');
  } elseif (post('action') === 'delete') {
    $stmt = db()->prepare("DELETE FROM items WHERE id=:id");
    $stmt->execute([':id' => post('id')]);
    flash('success', 'Item dihapus.');
  }
}

$items = db()->query("SELECT * FROM items ORDER BY id DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Items</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateItem">
      Tambah Item
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
          <thead class="table-dark">
            <tr class="border-0">
              <th style="width:72px" class="rounded-start">ID</th>
              <th>SKU</th>
              <th>Nama</th>
              <th>Satuan</th>
              <th style="width:120px" class="rounded-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $i): ?>
              <tr>
                <td><?= (int) $i['id'] ?></td>
                <td><?= h($i['sku']) ?></td>
                <td><?= h($i['name']) ?></td>
                <td><?= h($i['unit']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Hapus item ini?')" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">
                      Hapus
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Belum ada item.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCreateItem" tabindex="-1" aria-labelledby="modalCreateItemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content needs-validation" novalidate>
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCreateItemLabel">Tambah Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">SKU</label>
          <input name="sku" class="form-control" placeholder="SKU" required>
          <div class="invalid-feedback">SKU wajib diisi.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Nama Item</label>
          <input name="name" class="form-control" placeholder="Nama Item" required>
          <div class="invalid-feedback">Nama item wajib diisi.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Satuan</label>
          <input name="unit" class="form-control" placeholder="pcs, box, dll" required>
          <div class="invalid-feedback">Satuan wajib diisi.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
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
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
  var modal = document.getElementById('modalCreateItem');
  modal?.addEventListener('shown.bs.modal', function () {
    modal.querySelector('input[name="sku"]')?.focus();
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../lib/functions.php';

$rows = db()->query("
    SELECT sm.*, i.name AS item_name
    FROM stock_movements sm
    JOIN items i ON i.id=sm.item_id
    ORDER BY sm.id DESC
    LIMIT 200
")->fetchAll();

include __DIR__ . '/../../includes/header.php';

function nf2($n){ return number_format((float)$n, 2, ',', '.'); }
function nf0($n){ return number_format((float)$n, 0, ',', '.'); }

function type_badge(string $t): string {
  $map = [
    'PUR' => 'bg-success',  
    'ISS' => 'bg-danger', 
    'ADJ' => 'bg-warning text-dark',
  ];
  $cls = $map[$t] ?? 'bg-secondary';
  return '<span class="badge '.$cls.'">'.htmlspecialchars($t).'</span>';
}
?>
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Riwayat Pergerakan Stok</h2>
    <div class="d-flex gap-2">
      <button id="btnExportCsv" class="btn btn-outline-secondary btn-sm">Export CSV</button>
      <button onclick="window.print()" class="btn btn-outline-primary btn-sm">Cetak</button>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle mb-0" id="movesTable">
          <thead class="table-light">
            <tr>
              <th style="width:80px">ID</th>
              <th style="width:180px">Waktu</th>
              <th>Item</th>
              <th style="width:110px">Tipe</th>
              <th class="text-end" style="width:120px">Qty</th>
              <th class="text-end" style="width:160px">Biaya/Unit</th>
              <th style="width:170px">Ref</th>
              <th>Meta</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $qty = (int)$r['qty_change'];
                $qtyClass = $qty < 0 ? 'text-danger' : ($qty > 0 ? 'text-success' : 'text-muted');
                $meta = $r['meta'];
                $pretty = $meta;
                if (is_string($meta)) {
                  $decoded = json_decode($meta, true);
                  if (json_last_error() === JSON_ERROR_NONE) {
                    $pretty = json_encode($decoded, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
                  }
                }
              ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= h($r['occurred_at']) ?></td>
                <td><?= h($r['item_name']) ?></td>
                <td><?= type_badge((string)$r['movement_type']) ?></td>
                <td class="text-end <?= $qtyClass ?>"><?= nf0($qty) ?></td>
                <td class="text-end">Rp <?= nf2($r['unit_cost']) ?></td>
                <td><?= h($r['reference_table']) ?>#<?= (int)$r['reference_id'] ?></td>
                <td><pre class="mb-0" style="white-space:pre-wrap"><?= h($pretty) ?></pre></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Belum ada pergerakan stok.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('btnExportCsv')?.addEventListener('click', function () {
    const table = document.getElementById('movesTable');
    if (!table) return;

    const rows = [];
    rows.push(Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim()));

    table.querySelectorAll('tbody tr').forEach(tr => {
      const cells = Array.from(tr.querySelectorAll('td')).map(td => {
        return td.textContent.replace(/\s+\n/g, ' ').trim();
      });
      if (cells.length) rows.push(cells);
    });

    const csv = rows.map(r => r.map(v => {
      const needsQuote = /[",\n]/.test(v);
      const s = v.replace(/"/g, '""');
      return needsQuote ? `"${s}"` : s;
    }).join(',')).join('\n');

    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const today = new Date().toISOString().slice(0,10);
    a.href = url;
    a.download = `riwayat_stok_${today}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

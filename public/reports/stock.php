<?php
require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../lib/functions.php';

$rows = db()->query("
    SELECT i.id, i.sku, i.name, i.unit,
           COALESCE(SUM(b.qty_remaining), 0) AS qty_on_hand,
           COALESCE(SUM(b.qty_remaining * b.cost_per_unit), 0) AS inventory_value
    FROM items i
    LEFT JOIN batches b ON b.item_id = i.id
    GROUP BY i.id, i.sku, i.name, i.unit
    ORDER BY i.name
")->fetchAll();

include __DIR__ . '/../../includes/header.php';

function nf0($n){ return number_format((float)$n, 0, ',', '.'); }
function nf2($n){ return number_format((float)$n, 2, ',', '.'); }

$tot_qty = 0;
$tot_value = 0.0;
foreach ($rows as $r) {
  $tot_qty   += (int)$r['qty_on_hand'];
  $tot_value += (float)$r['inventory_value'];
}
?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h2 class="mb-0">Laporan Stok (Ringkas)</h2>
      <small class="text-muted">Metode valuasi: FIFO (berdasarkan nilai batch)</small>
    </div>
    <div class="d-flex gap-2">
      <button id="btnExportCsv" class="btn btn-outline-secondary btn-sm">Export CSV</button>
      <button onclick="window.print()" class="btn btn-outline-primary btn-sm">Cetak</button>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table" id="stockTable">
          <thead class="table-light">
            <tr>
              <th style="width:140px">SKU</th>
              <th>Nama</th>
              <th class="text-end" style="width:160px">On Hand</th>
              <th class="text-end" style="width:220px">Nilai Persediaan (FIFO)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= h($r['sku']) ?></td>
                <td><?= h($r['name']) ?></td>
                <td class="text-end">
                  <?= nf0($r['qty_on_hand']) . ' ' . h($r['unit']) ?>
                </td>
                <td class="text-end">Rp <?= nf2($r['inventory_value']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Tidak ada data.</td>
              </tr>
            <?php endif; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <th colspan="2" class="text-end">Total:</th>
              <th class="text-end"><?= nf0($tot_qty) ?></th>
              <th class="text-end">Rp <?= nf2($tot_value) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('btnExportCsv')?.addEventListener('click', function () {
    const table = document.getElementById('stockTable');
    if (!table) return;

    const rows = [];
    const ths = table.querySelectorAll('thead th');
    rows.push(Array.from(ths).map(th => th.textContent.trim()));

    table.querySelectorAll('tbody tr').forEach(tr => {
      const cols = Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim());
      if (cols.length) rows.push(cols);
    });
    
    const tds = table.querySelectorAll('tfoot th');
    if (tds.length) rows.push(Array.from(tds).map(td => td.textContent.trim()));

    const csv = rows.map(r =>
      r.map(v => {
        
        const needsQuote = v.includes(',') || v.includes('"') || v.includes('\n');
        let s = v.replace(/"/g, '""');
        return needsQuote ? `"${s}"` : s;
      }).join(',')
    ).join('\n');

    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    const today = new Date().toISOString().slice(0,10);
    a.href = url;
    a.download = `laporan_stok_${today}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

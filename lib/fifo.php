<?php
// lib/fifo.php - core FIFO logic for issuing stock
require_once __DIR__ . '/../db.php';

/**
 * Deplete stock for an item using FIFO across batches.
 * Creates issue_lines and stock_movements rows for audit trail.
 *
 * @param int $issue_id  The issue header ID
 * @param int $item_id   The item to issue
 * @param int $qty       Quantity to issue (units)
 * @param float|null $sell_price Optional selling price per unit (for reporting)
 * @return array [ 'issued' => int, 'cost_total' => float, 'lines' => array ]
 * @throws Exception if insufficient stock
 */
function fifo_issue(int $issue_id, int $item_id, int $qty, ?float $sell_price = null): array {
    if ($qty <= 0) throw new InvalidArgumentException('Qty must be positive');

    $pdo = db();
    // Get available batches oldest first
    $stmt = $pdo->prepare("
        SELECT * FROM batches 
        WHERE item_id = :item_id AND qty_remaining > 0
        ORDER BY received_at ASC, id ASC
        FOR UPDATE
    ");
    $pdo->beginTransaction();
    try {
        $stmt->execute([':item_id' => $item_id]);
        $need = $qty;
        $issued = 0;
        $cost_total = 0.0;
        $lines = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($need <= 0) break;
            $take = min($need, (int)$row['qty_remaining']);
            if ($take <= 0) continue;

            // Update batch remaining
            $upd = $pdo->prepare("UPDATE batches SET qty_remaining = qty_remaining - :take WHERE id = :id");
            $upd->execute([':take' => $take, ':id' => $row['id']]);

            // Insert issue line with batch cost captured
            $il = $pdo->prepare("
                INSERT INTO issue_lines (issue_id, item_id, batch_id, qty, cost_per_unit, sell_price_per_unit)
                VALUES (:issue_id, :item_id, :batch_id, :qty, :cpu, :spu)
            ");
            $il->execute([
                ':issue_id' => $issue_id,
                ':item_id' => $item_id,
                ':batch_id' => $row['id'],
                ':qty' => $take,
                ':cpu' => $row['cost_per_unit'],
                ':spu' => $sell_price
            ]);

            // Stock movement (out)
            $sm = $pdo->prepare("
                INSERT INTO stock_movements
                (item_id, movement_type, reference_table, reference_id, qty_change, unit_cost, occurred_at, meta)
                VALUES (:item_id, 'OUT', 'issues', :ref, :qty, :cpu, NOW(), JSON_OBJECT('batch_id', :batch_id))
            ");
            $sm->execute([
                ':item_id' => $item_id,
                ':ref' => $issue_id,
                ':qty' => -$take,
                ':cpu' => $row['cost_per_unit'],
                ':batch_id' => $row['id']
            ]);

            $need -= $take;
            $issued += $take;
            $cost_total += $take * (float)$row['cost_per_unit'];
            $lines[] = ['batch_id' => (int)$row['id'], 'qty' => $take, 'cost_per_unit' => (float)$row['cost_per_unit']];
        }

        if ($need > 0) {
            $pdo->rollBack();
            throw new Exception('Stok tidak mencukupi untuk item ID ' . $item_id . ' (butuh ' . $qty . ')');
        }

        $pdo->commit();
        return ['issued' => $issued, 'cost_total' => $cost_total, 'lines' => $lines];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/**
 * Record a purchase and its batch in one call.
 * @return int purchase_id
 */
function record_purchase(int $item_id, int $qty, float $cost_per_unit, ?string $expiry_date = null, ?int $supplier_id = null): int {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Insert purchase header
        $pdo->prepare("INSERT INTO purchases (supplier_id, created_at) VALUES (:sid, NOW())")
            ->execute([':sid' => $supplier_id]);

        $purchase_id = (int)$pdo->lastInsertId();

        // Create batch
        $pdo->prepare("
            INSERT INTO batches (item_id, qty_initial, qty_remaining, cost_per_unit, received_at, expiry_date, purchase_id)
            VALUES (:item_id, :qi, :qr, :cpu, NOW(), :exp, :pid)
        ")->execute([
            ':item_id' => $item_id,
            ':qi' => $qty,
            ':qr' => $qty,
            ':cpu' => $cost_per_unit,
            ':exp' => $expiry_date,
            ':pid' => $purchase_id
        ]);

        $batch_id = (int) $pdo->lastInsertId();

        // Link purchase line
        $pdo->prepare("
            INSERT INTO purchase_lines (purchase_id, item_id, batch_id, qty, cost_per_unit)
            VALUES (:pid, :item_id, :batch_id, :qty, :cpu)
        ")->execute([
            ':pid' => $purchase_id,
            ':item_id' => $item_id,
            ':batch_id' => $batch_id,
            ':qty' => $qty,
            ':cpu' => $cost_per_unit
        ]);

        // Stock movement (in)
        $pdo->prepare("
            INSERT INTO stock_movements 
            (item_id, movement_type, reference_table, reference_id, qty_change, unit_cost, occurred_at, meta)
            VALUES (:item_id, 'IN', 'purchases', :pid, :qty, :cpu, NOW(), JSON_OBJECT('batch_id', :batch_id))
        ")->execute([
            ':item_id' => $item_id,
            ':pid' => $purchase_id,
            ':qty' => $qty,
            ':cpu' => $cost_per_unit,
            ':batch_id' => $batch_id
        ]);

        $pdo->commit();
        return $purchase_id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

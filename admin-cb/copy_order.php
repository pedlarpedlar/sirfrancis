<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login?redirect=manage_orders');
    exit();
}

include 'dbh.inc.php';

function cbCopyOrderColumns($conn, $table) {
    $columns = [];
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $result = $conn->query("SHOW COLUMNS FROM `$safeTable`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function cbCopyOrderTableExists($conn, $table) {
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
if ($orderId <= 0 || !cbCopyOrderTableExists($conn, 'orders')) {
    header('Location: manage_orders?copy=invalid');
    exit();
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    $orderColumns = array_values(array_filter(cbCopyOrderColumns($conn, 'orders'), static function($column) {
        return $column !== 'id';
    }));
    $insertColumns = [];
    $placeholders = [];
    $values = [];
    $types = '';
    foreach ($orderColumns as $column) {
        $value = $order[$column] ?? null;
        if ($column === 'order_status') {
            $value = 'Pending';
        } elseif ($column === 'payment_status') {
            $value = 0;
        } elseif ($column === 'order_date') {
            $value = date('Y-m-d H:i:s');
        } elseif ($column === 'payfast_payment_id') {
            $value = null;
        }
        $insertColumns[] = "`$column`";
        $placeholders[] = '?';
        $values[] = $value;
        $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
    }

    $insertSql = 'INSERT INTO orders (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $insert = $conn->prepare($insertSql);
    if (!$insert) {
        throw new RuntimeException($conn->error);
    }
    $insert->bind_param($types, ...$values);
    $insert->execute();
    $newOrderId = $insert->insert_id;
    $insert->close();

    if (cbCopyOrderTableExists($conn, 'order_items')) {
        $itemColumns = cbCopyOrderColumns($conn, 'order_items');
        $copyColumns = array_values(array_filter($itemColumns, static function($column) {
            return $column !== 'id';
        }));
        if (in_array('order_id', $copyColumns, true)) {
            $selectParts = [];
            foreach ($copyColumns as $column) {
                $selectParts[] = $column === 'order_id' ? '? AS `order_id`' : "`$column`";
            }
            $sql = 'INSERT INTO order_items (`' . implode('`, `', $copyColumns) . '`) SELECT ' . implode(', ', $selectParts) . ' FROM order_items WHERE order_id = ?';
            $copyItems = $conn->prepare($sql);
            if (!$copyItems) {
                throw new RuntimeException($conn->error);
            }
            $copyItems->bind_param('ii', $newOrderId, $orderId);
            $copyItems->execute();
            $copyItems->close();
        }
    }

    $conn->commit();
    header('Location: manage_order?order_id=' . urlencode($newOrderId) . '&copied_from=' . urlencode($orderId));
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('CandyBird copy order failed: ' . $e->getMessage());
    header('Location: manage_orders?copy=failed');
    exit();
}

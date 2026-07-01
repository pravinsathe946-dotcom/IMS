<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// --- 1. HANDLE STOCK ADJUSTMENTS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    $product_id = intval($_POST['product_id']);
    $type = $_POST['type']; // 'in' or 'out'
    $quantity = intval($_POST['quantity']);
    $reason = trim($_POST['reason']);
    $user_id = $_SESSION['user_id'];

    if ($product_id > 0 && $quantity > 0 && in_array($type, ['in', 'out'])) {
        try {
            // Start a PDO Transaction to guarantee data integrity
            $pdo->beginTransaction();

            // 1. Fetch current stock to verify availability for outbound transactions
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if ($product) {
                $current_qty = $product['quantity'];
                $new_qty = ($type === 'in') ? ($current_qty + $quantity) : ($current_qty - $quantity);

                if ($new_qty < 0) {
                    throw new Exception("Transaction rejected: Insufficient stock available. Current balance is " . $current_qty);
                }

                // 2. Update Product table quantity
                $update_stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $update_stmt->execute([$new_qty, $product_id]);

                // 3. Write record into audit history log table
                $log_stmt = $pdo->prepare("INSERT INTO stock_log (product_id, user_id, type, quantity, reason) VALUES (?, ?, ?, ?, ?)");
                $log_stmt->execute([$product_id, $user_id, $type, $quantity, $reason]);

                // Commit the transaction operations
                $pdo->commit();
                $success_msg = "Stock successfully adjusted!";
            } else {
                throw new Exception("Selected product does not exist.");
            }
        } catch (Exception $e) {
            // Roll back changes if any step fails
            $pdo->rollBack();
            $error_msg = $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in all mandatory adjustment fields correctly.";
    }
}

// --- 2. FETCH DROP-DOWN & DATA FEED REQUISITIONS ---
$products_list = $pdo->query("SELECT id, name, sku, quantity FROM products ORDER BY name ASC")->fetchAll();

// Fetch historical audit tracking feed rows
$log_query = "SELECT l.*, p.name AS product_name, p.sku AS product_sku, u.username 
              FROM stock_log l 
              JOIN products p ON l.product_id = p.id 
              LEFT JOIN users u ON l.user_id = u.id 
              ORDER BY l.created_at DESC";
$logs = $pdo->query($log_query)->fetchAll();
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Stock Adjustments</h1>
        <p class="text-sm text-gray-500">Record inventory arrivals and dispatches with mandatory tracking audits.</p>
    </div>

    <!-- Feedbacks -->
    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 text-emerald-700 text-sm p-4 rounded-xl border border-emerald-100 font-medium"><?= $success_msg; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 text-rose-700 text-sm p-4 rounded-xl border border-rose-100 font-medium"><?= $error_msg; ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        
        <!-- LEFT PANEL: Adjust Stock Action Card Form -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
            <h3 class="text-lg font-bold text-gray-900">New Movement Action</h3>
            
            <form action="stock_logs.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="adjust_stock">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Select Item *</label>
                    <select name="product_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-indigo-500">
                        <option value="">-- Choose Target Product --</option>
                        <?php foreach ($products_list as $p): ?>
                            <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['name']); ?> (SKU: <?= htmlspecialchars($p['sku']); ?>) [In Stock: <?= $p['quantity']; ?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Movement Type *</label>
                    <div class="mt-2 flex gap-4">
                        <label class="inline-flex items-center text-sm font-semibold text-gray-700 cursor-pointer">
                            <input type="radio" name="type" value="in" checked class="text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <span class="ml-2 text-emerald-600">Stock In (+)</span>
                        </label>
                        <label class="inline-flex items-center text-sm font-semibold text-gray-700 cursor-pointer">
                            <input type="radio" name="type" value="out" class="text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <span class="ml-2 text-rose-600">Stock Out (-)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                    <input type="number" name="quantity" min="1" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason / Reference Note</label>
                    <input type="text" name="reason" placeholder="e.g., Supplier order #491, Damaged, Sale" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm pt-2">
                    Execute Adjustment
                </button>
            </form>
        </div>

        <!-- RIGHT PANEL: Chronological Audit Trail Logs History Table -->
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">System Audit History Trail</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Info</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Direction</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Log Note / Reason</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-mono">
                                        <?= date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($log['product_name']); ?></div>
                                        <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($log['product_sku']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($log['type'] === 'in'): ?>
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">IN</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-rose-50 text-rose-700 border border-rose-100">OUT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
                                        <?= $log['quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?= htmlspecialchars($log['reason'] ?: '—'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-gray-500">
                                        <?= htmlspecialchars($log['username'] ?: 'System'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 font-medium">No activity entries registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
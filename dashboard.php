<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- 1. FETCH METRICS ---

// Total unique products
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Total stock value ($ total value of items on hand)
$total_value = $pdo->query("SELECT SUM(price * quantity) FROM products")->fetchColumn() ?: 0;

// Total items out of stock (quantity = 0)
$out_of_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity = 0")->fetchColumn();

// Total items currently at low stock thresholds
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= min_stock_level AND quantity > 0")->fetchColumn();


// --- 2. FETCH CONDITIONAL DATA LISTS ---

// Get top 5 low stock products needing urgent reordering
$low_stock_query = "SELECT p.*, c.name AS category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.quantity <= p.min_stock_level 
                    ORDER BY p.quantity ASC LIMIT 5";
$low_stock_items = $pdo->query($low_stock_query)->fetchAll();

// Get the 5 most recent activity entries for the activity feed
$recent_logs_query = "SELECT l.*, p.name AS product_name, u.username 
                      FROM stock_log l 
                      JOIN products p ON l.product_id = p.id 
                      LEFT JOIN users u ON l.user_id = u.id 
                      ORDER BY l.created_at DESC LIMIT 5";
$recent_logs = $pdo->query($recent_logs_query)->fetchAll();
?>

<div class="space-y-8">
    <!-- Top Greeting Section -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard Overview</h1>
        <p class="text-sm text-gray-500">Real-time status metrics of your active inventory catalog operations.</p>
    </div>

    <!-- --- STATS CARDS --- -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Total Products Card -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Products</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $total_products; ?></h3>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
        </div>

        <!-- Total Capital Value Card -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Inventory Value</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">$<?= number_format($total_value, 2); ?></h3>
            </div>
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 14a2 2 0 110-4h4"></path></svg>
            </div>
        </div>

        <!-- Low Stock Warning Card -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Low Stock Items</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $low_stock; ?></h3>
            </div>
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>

        <!-- Out of Stock Alert Card -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Out of Stock</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $out_of_stock; ?></h3>
            </div>
            <div class="p-3 bg-rose-50 text-rose-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

    </div>

    <!-- --- SPLIT VIEW DETAILS --- -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        <!-- LEFT PANELS: Low Stock Threshold Indicators -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Critical Reorder Alerts</h3>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Action Required</span>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (count($low_stock_items) > 0): ?>
                    <?php foreach ($low_stock_items as $item): ?>
                        <div class="p-4 px-6 flex items-center justify-between hover:bg-gray-50/50 transition">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($item['name']); ?></h4>
                                <p class="text-xs text-gray-400 font-mono mt-0.5">SKU: <?= htmlspecialchars($item['sku']); ?> | Category: <?= htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-rose-600"><?= $item['quantity']; ?> remaining</span>
                                <p class="text-xs text-gray-400">Min level: <?= $item['min_stock_level']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-sm text-gray-400 font-medium">All item levels are completely healthy. No alert logs detected.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT PANELS: Live Activity Feed Log -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Recent Stock Transactions Feed</h3>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (count($recent_logs) > 0): ?>
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="p-4 px-6 flex items-center justify-between hover:bg-gray-50/50 transition">
                            <div class="flex items-center gap-3">
                                <?php if ($log['type'] === 'in'): ?>
                                    <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 text-xs font-bold flex items-center justify-center">+</span>
                                <?php else: ?>
                                    <span class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 text-xs font-bold flex items-center justify-center">-</span>
                                <?php endif; ?>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($log['product_name']); ?></h4>
                                    <p class="text-xs text-gray-500 italic"><?= htmlspecialchars($log['reason'] ?: 'No adjustment reason stated.'); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold <?= $log['type'] === 'in' ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?= $log['type'] === 'in' ? '+' : '-'; ?><?= $log['quantity']; ?>
                                </span>
                                <p class="text-[10px] text-gray-400 font-mono"><?= date('h:i A', strtotime($log['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-sm text-gray-400 font-medium">No system activity events recorded yet.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
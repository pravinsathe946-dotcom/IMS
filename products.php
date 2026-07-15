<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// Handle Product Deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Product successfully removed from inventory.";
    } catch (PDOException $e) {
        $error_msg = "Error: Could not delete product. It may be referenced in transactions.";
    }
}

// Fetch all products with their category names
$query = "SELECT p.*, c.name AS category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.name ASC";
$products = $pdo->query($query)->fetchAll();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Products Catalog</h1>
            <p class="text-sm text-gray-500">Track item counts, pricing, and stock health thresholds.</p>
        </div>
        <div>
            <a href="product_add.php" class="inline-flex justify-center items-center py-2 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                + Add Product
            </a>
        </div>
    </div>

    <!-- Notifications -->
    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 text-emerald-700 text-sm p-4 rounded-xl border border-emerald-100 font-medium"><?= $success_msg; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 text-rose-700 text-sm p-4 rounded-xl border border-rose-100 font-medium"><?= $error_msg; ?></div>
    <?php endif; ?>

    <!-- Product Grid/Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock Qty</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $prod): 
                            $is_low = $prod['quantity'] <= $prod['min_stock_level'];
                            $is_out = $prod['quantity'] == 0;
                        ?>
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                    <?= htmlspecialchars($prod['sku']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($prod['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= htmlspecialchars($prod['category_name'] ?: 'Uncategorized'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ₹<?= number_format($prod['price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-semibold">
                                    <?= $prod['quantity']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($is_out): ?>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-rose-50 text-rose-600 border border-rose-100">Out of Stock</span>
                                    <?php elseif ($is_low): ?>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-amber-50 text-amber-600 border border-amber-100">Low Stock</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <a href="product_edit.php?id=<?= $prod['id']; ?>" class="text-indigo-600 hover:text-indigo-900 transition">Edit</a>
                                    <a href="products.php?delete=<?= $prod['id']; ?>" onclick="return confirm('Delete this product permanently from the logs?');" class="text-rose-600 hover:text-rose-900 transition">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 font-medium">No products found. Start by adding one above!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
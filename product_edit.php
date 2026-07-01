<?php
require_once 'config.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error_msg = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch current product profile details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = trim($_POST['sku']);
    $name = trim($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $min_stock_level = intval($_POST['min_stock_level']);

    if (!empty($sku) && !empty($name)) {
        try {
            // Note: We don't update quantity directly here. That's reserved for Module 4 (Stock Adjustments Log).
            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, sku = ?, name = ?, description = ?, price = ?, min_stock_level = ? WHERE id = ?");
            $stmt->execute([$category_id, $sku, $name, $description, $price, $min_stock_level, $id]);

            header("Location: products.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Error updating database entry. SKU values must remain completely unique.";
        }
    } else {
        $error_msg = "Please verify your input fields.";
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Product Profile</h1>
        <p class="text-sm text-gray-500">Modify properties for product: <b><?= htmlspecialchars($product['name']); ?></b></p>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 text-rose-700 text-sm p-4 rounded-xl border border-rose-100 font-medium"><?= $error_msg; ?></div>
    <?php endif; ?>

    <form action="product_edit.php?id=<?= $id; ?>" method="POST" class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">SKU Code *</label>
                <input type="text" name="sku" value="<?= htmlspecialchars($product['sku']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Category Selection</label>
            <select name="category_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 bg-white">
                <option value="">-- No Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id']; ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500"><?= htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Price Value ($) *</label>
                <input type="number" step="0.01" name="price" value="<?= $product['price']; ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Min Alert Threshold Level</label>
                <input type="number" name="min_stock_level" value="<?= $product['min_stock_level']; ?>" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="py-2 px-4 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">Save Updates</button>
            <a href="products.php" class="py-2 px-4 text-sm font-semibold rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
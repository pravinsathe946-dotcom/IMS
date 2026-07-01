<?php
require_once 'config.php';
include 'includes/header.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// --- 1. HANDLE ACTIONS (POST/GET) ---

// CREATE Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $success_msg = "Category successfully created!";
        } catch (PDOException $e) {
            $error_msg = "Error: Category name might already exist.";
        }
    } else {
        $error_msg = "Category name is required.";
    }
}

// UPDATE Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $success_msg = "Category successfully updated!";
        } catch (PDOException $e) {
            $error_msg = "Error updating category.";
        }
    } else {
        $error_msg = "Category name is required.";
    }
}

// DELETE Category
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Category deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Cannot delete category. It may be linked to existing products.";
    }
}

// --- 2. FETCH DATA ---

// If editing, fetch specific category details
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

// Fetch all categories for the data table
$stmt = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC");
$categories = $stmt->fetchAll();
?>

<!-- --- 3. UI DISPLAY --- -->

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Categories</h1>
            <p class="text-sm text-gray-500">Organize and segment your product lines.</p>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 text-emerald-700 text-sm p-4 rounded-xl border border-emerald-100 font-medium"><?= $success_msg; ?></div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 text-rose-700 text-sm p-4 rounded-xl border border-rose-100 font-medium"><?= $error_msg; ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- LEFT: Form Dynamic Column (Add or Edit) -->
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
            <?php if ($edit_category): ?>
                <h3 class="text-lg font-bold text-gray-900">Edit Category</h3>
                <form action="categories.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= $edit_category['id']; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($edit_category['name']); ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm"><?= htmlspecialchars($edit_category['description']); ?></textarea>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 py-2 px-4 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">Save Changes</button>
                        <a href="categories.php" class="py-2 px-4 text-sm font-semibold rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition text-center">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <h3 class="text-lg font-bold text-gray-900">Add New Category</h3>
                <form action="categories.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category Name</label>
                        <input type="text" name="name" placeholder="e.g., Electronics, Apparel" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" placeholder="Optional category overview" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition pt-2">Add Category</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Data Table Column -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($cat['name']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?= htmlspecialchars($cat['description'] ?: '—'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <a href="categories.php?edit=<?= $cat['id']; ?>" class="text-indigo-600 hover:text-indigo-900 transition">Edit</a>
                                    <a href="categories.php?delete=<?= $cat['id']; ?>" onclick="return confirm('Are you sure you want to delete this category?');" class="text-rose-600 hover:text-rose-900 transition">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500 font-medium">No categories found. Create one on the left panel!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
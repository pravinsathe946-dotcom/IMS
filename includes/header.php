<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Navigation Bar for logged-in users -->
    <nav class="bg-white border-b border-gray-200 fixed z-30 w-full">
        <div class="px-6 py-3 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-xl font-bold text-indigo-600 tracking-tight">StockMaster</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600 font-medium">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<span class="capitalize text-xs px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full"><?php echo $_SESSION['role']; ?></span>)</span>
                <a href="logout.php" class="text-sm font-semibold text-red-600 hover:text-red-700 transition">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Wrapper -->
    <div class="flex pt-14 overflow-hidden bg-gray-50">
        <aside class="w-64 fixed left-0 top-14 h-full bg-white border-r border-gray-200 pt-4 px-4 z-20">
            <ul class="space-y-1">
                <li><a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Dashboard</a></li>
                <li><a href="products.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Products</a></li>
                <li><a href="categories.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Categories</a></li>
                <li><a href="stock_logs.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Stock Logs</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="users.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg border-t border-gray-100 pt-2">Manage Users</a></li>
                <?php endif; ?>
            </ul>
        </aside>
        <main class="w-full h-full bg-gray-50 min-h-screen pl-64 p-6">
    <?php endif; ?>
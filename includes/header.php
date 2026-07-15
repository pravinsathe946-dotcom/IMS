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
<body class="bg-gray-50 font-sans antialiased overflow-x-hidden">
    <?php if (isset($_SESSION['user_id'])): ?>
    
    <!-- 1. MOBILE BACKDROP OVERLAY -->
    <div id="sidebar-backdrop" 
         onclick="toggleSidebar()" 
         class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 md:hidden transition-opacity duration-300">
    </div>

    <!-- Navigation Bar for logged-in users -->
    <nav class="bg-white border-b border-gray-200 fixed z-30 w-full top-0 left-0">
        <div class="px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <!-- HAMBURGER BUTTON (Visible only on mobile) -->
                <button onclick="toggleSidebar()" 
                        class="md:hidden p-1.5 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                        aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <span class="text-xl font-bold text-indigo-600 tracking-tight">StockMaster</span>

            </div>
            
            <div class="hidden flex items-center gap-4 md:inline-block">
                <span class="text-sm text-gray-600 font-medium sm:inline">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<span class="capitalize text-xs px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full"><?php echo $_SESSION['role']; ?></span>)</span>
                <a href="logout.php" class="text-sm font-semibold text-red-600 hover:text-red-700 transition">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Wrapper -->
    <div class="flex pt-14 bg-gray-50">
        <!-- 2. RESPONSIVE SIDEBAR -->
        <!-- Hidden off-screen left by default via -translate-x-full. Instantly positioned normally using md:translate-x-0 on desktop -->
        <aside id="sidebar" 
               class="w-64 fixed left-0 top-0 md:top-14 h-full bg-white border-r border-gray-200 pt-4 px-4 z-50 md:z-20 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0 md:flex md:flex-col">
            
            <!-- CLOSE BUTTON LAYOUT BLOCK (Visible only on mobile header row) -->
            <div class="flex items-center justify-between pb-4 mb-2 border-b border-gray-100 md:hidden">
               <span class="text-xl font-bold text-indigo-600 tracking-tight">StockMaster</span>
                <button onclick="toggleSidebar()" 
                        class="p-1.5 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                        aria-label="Close menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Only User Welcome Details Header -->
            <div class="p-3 bg-gray-50 rounded-xl mb-4 md:hidden">
                <!-- <p class="text-xs text-gray-500 font-medium">Logged in as:</p>
                <p class="text-sm font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <span class="inline-block mt-1 capitalize text-[10px] font-bold px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full"><?php echo $_SESSION['role']; ?></span> -->

                <span class="text-sm text-gray-600 font-medium sm:inline">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<span class="capitalize text-xs px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full"><?php echo $_SESSION['role']; ?></span>)</span>
                <a href="logout.php" class="text-sm font-semibold text-red-600 hover:text-red-700 transition">Logout</a>
            </div>

            <ul class="space-y-1 w-full">
                <li><a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Dashboard</a></li>
                <li><a href="products.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Products</a></li>
                <li><a href="categories.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Categories</a></li>
                <li><a href="stock_logs.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg">Stock Logs</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="users.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg border-t border-gray-100 pt-2 mt-2">Manage Users</a></li>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Main Workspace Area Wrapper -->
        <main class="w-full h-full bg-gray-50 min-h-screen p-6 md:pl-70 md:ml-64 transition-all duration-300">
    <?php endif; ?>

    <!-- Toggle Controller Javascript Core Scripts -->
    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        
        // Dynamic Class List States Switching Matrix
        sidebar.classList.toggle('-translate-x-full');
        backdrop.classList.toggle('hidden');
        document.body.classList.toggle('overflow-hidden');
    }
    </script>
</body>
</html>

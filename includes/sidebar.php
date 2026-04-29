<?php
/**
 * GameTopUp Pro - Sidebar Navigation
 * Fixed left sidebar with collapsible mobile support
 */

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentModule = basename(dirname($_SERVER['PHP_SELF']));

// Menu items configuration
$menuItems = [
    ['name' => 'Dashboard', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'url' => '../dashboard/index.php', 'module' => 'dashboard', 'adminOnly' => false],
    ['name' => 'Transactions', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'url' => '../transactions/index.php', 'module' => 'transactions', 'adminOnly' => false],
    ['name' => 'Games', 'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'url' => '../games/index.php', 'module' => 'games', 'adminOnly' => false],
    ['name' => 'Game Items', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'url' => '../game_items/index.php', 'module' => 'game_items', 'adminOnly' => false],
    ['name' => 'Categories', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'url' => '../categories/index.php', 'module' => 'categories', 'adminOnly' => false],
    ['name' => 'Users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'url' => '../users/index.php', 'module' => 'users', 'adminOnly' => true],
];
?>
<!-- Sidebar -->
<aside 
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed left-0 top-0 bottom-0 w-64 bg-gray-900 z-50 transform transition-transform duration-200 ease-in-out lg:translate-x-0 flex flex-col"
>
    <!-- Logo -->
    <div class="flex items-center gap-3 h-16 px-6 border-b border-gray-800">
        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
        </div>
        <span class="text-lg font-bold text-white">Eyok Store V2</span>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3">
        <ul class="space-y-1">
            <?php foreach ($menuItems as $item): ?>
                <?php 
                // Skip admin-only items for operators
                if ($item['adminOnly'] && !isAdmin()) continue;
                
                $isActive = $currentModule === $item['module'];
                ?>
                <li>
                    <a 
                        href="<?php echo $item['url']; ?>" 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $isActive ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?>"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item['icon']; ?>"></path>
                        </svg>
                        <?php echo $item['name']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    
    <!-- User Section -->
    <div class="border-t border-gray-800 p-4">
        <div class="flex items-center gap-3 mb-3">
            <?php 
            $avatarPath = '../../uploads/avatars/' . ($_SESSION['avatar'] ?? '');
            if (!empty($_SESSION['avatar']) && file_exists($avatarPath)): 
            ?>
                <img src="<?php echo $avatarPath; ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover border border-gray-700">
            <?php else: ?>
                <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center">
                    <span class="text-sm font-medium text-white"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?></span>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></p>
            </div>
        </div>
        <a 
            href="../../auth/logout.php" 
            class="flex items-center justify-center gap-2 w-full px-3 py-2 text-sm font-medium text-gray-300 bg-gray-800 rounded-lg hover:bg-gray-700 hover:text-white transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Logout
        </a>
    </div>
</aside>

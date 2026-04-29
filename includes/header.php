<?php
/**
 * GameTopUp Pro - Header Include
 * Top navbar with mobile toggle, page title, user info
 */
?>
<!DOCTYPE html>
<html lang="en" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'GameTopUp Pro'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Sidebar Overlay -->
    <div 
        x-show="sidebarOpen" 
        x-cloak
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
    ></div>

    <!-- Top Navbar -->
    <nav class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-30 lg:pl-64">
        <div class="flex items-center justify-between h-16 px-4 lg:px-8">
            <!-- Left: Mobile Toggle + Breadcrumb -->
            <div class="flex items-center gap-4">
                <button 
                    @click="sidebarOpen = !sidebarOpen"
                    class="lg:hidden p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                <div class="hidden sm:flex items-center text-sm text-gray-500">
                    <span class="hover:text-gray-700 cursor-pointer">Home</span>
                    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></span>
                </div>
            </div>
            
            <!-- Right: User Info -->
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $_SESSION['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                    <?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'user')); ?>
                </span>
                
                <div class="flex items-center gap-3 pl-3 border-l border-gray-200">
                    <?php 
                    $avatarPath = '../../uploads/avatars/' . ($_SESSION['avatar'] ?? '');
                    if (!empty($_SESSION['avatar']) && file_exists($avatarPath)): 
                    ?>
                        <img src="<?php echo $avatarPath; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-gray-100">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-medium text-indigo-600"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hidden md:block">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </nav>

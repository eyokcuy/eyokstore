<?php
/**
 * Eyok Store V2 - Premium Game Top-Up
 * High-performance, clean blue/white aesthetic
 */
require_once 'config/database.php';

$db = getDBConnection();

// Fetch categories
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Fetch games
$games = $db->query("SELECT g.*, c.name as category_name FROM games g JOIN categories c ON g.category_id = c.id WHERE g.status = 'active' ORDER BY g.name ASC")->fetchAll();

$categoryId = intval($_GET['category'] ?? 0);
$search = $_GET['search'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eyok Store V2 - Premium Game Top-Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .hero-bg {
            background-color: #ffffff;
            background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px);
            background-size: 24px 24px;
        }
        .glass-nav { 
            background: rgba(255, 255, 255, 0.8); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .game-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .game-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
        .game-img { transition: transform 0.6s scale; }
        .game-card:hover .game-img { transform: scale(1.05); }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="min-h-screen selection:bg-blue-500 selection:text-white flex flex-col" x-data="{ search: '<?php echo addslashes($search); ?>', category: <?php echo $categoryId; ?> }">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-600/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <span class="text-2xl font-black tracking-tight text-slate-800">Eyok Store V2</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="auth/login.php" class="px-5 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">Admin Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 hero-bg relative">
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-100/50 text-blue-700 text-sm font-semibold mb-8 border border-blue-200">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                24/7 Instant Delivery Active
            </div>
            <h1 class="text-5xl md:text-7xl font-black mb-6 text-slate-900 tracking-tight leading-tight">
                Top Up Your Game <br>
                <span class="text-blue-600">Fast & Secure</span>
            </h1>
            <p class="text-slate-600 text-lg md:text-xl max-w-2xl mx-auto mb-10">
                The most trusted and reliable platform to fuel your gaming journey. Enter ID, pay, and play!
            </p>
            
            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto relative group">
                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input 
                    type="text" 
                    x-model="search"
                    placeholder="Find your favorite game..." 
                    class="w-full pl-14 pr-12 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow shadow-sm focus:shadow-md text-slate-800 text-lg placeholder-slate-400"
                >
                <button 
                    x-show="search.length > 0" 
                    @click="search = ''" 
                    class="absolute inset-y-0 right-0 pr-6 flex items-center text-slate-400 hover:text-blue-600 transition-colors"
                    x-cloak
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- Announcements / Alerts -->
    <section class="max-w-7xl mx-auto px-4 -mt-8 relative z-20">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Promo Alert -->
            <div 
                @click="Swal.fire({
                    title: 'Detail Promo Weekend',
                    text: 'Dapatkan potongan harga langsung untuk Valorant & Mobile Legends khusus hari Sabtu & Minggu. Tanpa kode promo!',
                    icon: 'info',
                    confirmButtonColor: '#2563eb'
                })"
                class="bg-blue-600 rounded-3xl p-5 flex items-center gap-4 shadow-lg shadow-blue-200 group cursor-pointer hover:bg-blue-700 transition-all"
            >
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-white font-bold">Promo Weekend!</h3>
                    <p class="text-blue-100 text-sm">Diskon up to 20% untuk semua item Valorant & MLBB.</p>
                </div>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </div>

            <!-- Status Alert -->
            <div class="bg-white rounded-3xl p-5 flex items-center gap-4 shadow-sm border border-slate-200 hover:border-blue-200 transition-all">
                <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-slate-800 font-bold">Informasi Sistem</h3>
                    <p class="text-slate-500 text-sm">Proses pengiriman item saat ini sangat lancar (0-5 menit).</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories & Game Grid -->
    <section class="max-w-7xl mx-auto px-4 py-20 flex-grow w-full">
        <!-- Category Tabs -->
        <div class="flex flex-wrap items-center justify-center gap-3 mb-12">
            <button 
                @click="category = 0; Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, icon: 'success', title: 'Menampilkan Semua Game' })"
                class="px-6 py-2.5 rounded-2xl text-sm font-bold transition-all"
                :class="category === 0 ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-100'"
            >
                Semua Game
            </button>
            <?php foreach ($categories as $cat): ?>
                <button 
                    @click="category = <?php echo $cat['id']; ?>; Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, icon: 'success', title: 'Kategori: <?php echo $cat['name']; ?>' })"
                    class="px-6 py-2.5 rounded-2xl text-sm font-bold transition-all"
                    :class="category === <?php echo $cat['id']; ?> ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-100'"
                >
                    <?php echo htmlspecialchars($cat['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Games Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            <?php foreach ($games as $game): ?>
                <a 
                    href="order.php?game_id=<?php echo $game['id']; ?>" 
                    class="group block game-card-wrapper"
                    @click="Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1000, icon: 'info', title: 'Membuka <?php echo addslashes($game['name']); ?>...' })"
                    x-show="(category === 0 || category === <?php echo $game['category_id']; ?>) && ('<?php echo strtolower(addslashes($game['name'])); ?>'.includes(search.toLowerCase()))"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="game-card relative aspect-[3/4] rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-sm">
                        <!-- Thumbnail -->
                        <?php if ($game['thumbnail'] && file_exists('uploads/thumbnails/' . $game['thumbnail'])): ?>
                            <img src="uploads/thumbnails/<?php echo htmlspecialchars($game['thumbnail']); ?>" class="game-img w-full h-full object-cover transition-transform duration-500" alt="<?php echo htmlspecialchars($game['name']); ?>">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center bg-slate-50 p-6 text-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest"><?php echo htmlspecialchars($game['publisher']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Info Overlay -->
                        <div class="absolute inset-x-0 bottom-0 bg-white p-4 pt-6 bg-gradient-to-t from-white via-white to-transparent">
                            <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors truncate"><?php echo htmlspecialchars($game['name']); ?></h3>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs font-medium text-slate-500"><?php echo htmlspecialchars($game['publisher']); ?></span>
                                <div class="w-7 h-7 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Empty State -->
        <div 
            x-show="search.length > 0 && !document.querySelectorAll('.game-card-wrapper:not([style*=\'display: none\'])').length" 
            class="text-center py-20"
            x-cloak
        >
            <div class="w-20 h-20 bg-slate-100 rounded-3xl flex items-center justify-center mx-auto mb-6 text-slate-400">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Game tidak ditemukan</h3>
            <p class="text-slate-500">Maaf, kami tidak dapat menemukan game dengan kata kunci "<span x-text="search" class="font-bold text-blue-600"></span>"</p>
            <button @click="search = ''" class="mt-6 text-blue-600 font-bold hover:underline italic">Tampilkan semua game</button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 border-b border-slate-800 pb-12 mb-8">
                <!-- Brand Info -->
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-black tracking-tight">Eyok Store V2</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">
                        Eyok Store V2 provides the fastest and most secure way to purchase in-game credits, diamonds, and passes for your favorite games.
                    </p>
                </div>
                
                <!-- Quick Links -->
                <div class="col-span-1">
                    <h4 class="text-lg font-bold mb-6 text-slate-100">Quick Links</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-blue-400 transition-colors">Home</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">All Games</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors">How to Order</a></li>
                        <li><a href="auth/login.php" class="hover:text-blue-400 transition-colors">Admin Hub</a></li>
                    </ul>
                </div>

                <!-- Contact & Support -->
                <div class="col-span-1">
                    <h4 class="text-lg font-bold mb-6 text-slate-100">Customer Support</h4>
                    <ul class="space-y-4 text-sm text-slate-400">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>support@eyokstore.id</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <span>+62 812-3456-7890 (WhatsApp)</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
                <p>&copy; <?php echo date('Y'); ?> Eyok Store V2. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-slate-300 transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-slate-300 transition-colors">Refund Policy</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>

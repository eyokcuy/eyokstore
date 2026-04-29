<?php
/**
 * GameTopUp Pro - Public Order Page
 */
require_once 'config/database.php';

$db = getDBConnection();

$gameId = intval($_GET['game_id'] ?? 0);
if ($gameId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch game
$stmt = $db->prepare("SELECT g.*, c.name as category_name FROM games g JOIN categories c ON g.category_id = c.id WHERE g.id = :id AND g.status = 'active'");
$stmt->execute([':id' => $gameId]);
$game = $stmt->fetch();

if (!$game) {
    header('Location: index.php');
    exit;
}

// Fetch game items
$itemsStmt = $db->prepare("SELECT * FROM game_items WHERE game_id = :game_id AND status = 'active' ORDER BY price ASC");
$itemsStmt->execute([':game_id' => $gameId]);
$gameItems = $itemsStmt->fetchAll();

$currency = $game['currency'] ?? 'Diamond';
$success = false;
$invoiceCode = '';
$quantity = 1;
$price = 0;
$total = 0;
$gameUid = '';
$customerName = '';
$contact = '';
$itemName = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $gameUid = trim($_POST['game_uid'] ?? '');
    $itemName = trim($_POST['item_name'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    
    // Map item prices from database for server-side validation
    $itemPrices = [];
    foreach ($gameItems as $gi) {
        $itemPrices[$gi['name'] . ' ' . $currency] = $gi['price'];
    }

    if ($customerName === '') $errors['customer_name'] = 'Name is required';
    if ($contact === '') $errors['contact'] = 'Contact number is required';
    if ($gameUid === '') $errors['game_uid'] = 'Game UID is required';
    if (!isset($itemPrices[$itemName])) {
        $errors['item_name'] = 'Invalid item selected';
    } else {
        $price = $itemPrices[$itemName];
    }

    if (empty($errors)) {
        $invoiceCode = 'INV-' . date('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5));
        $total = $quantity * $price;

        $ins = $db->prepare("INSERT INTO transactions (invoice_code, user_id, game_id, customer_name, contact, game_uid, item_name, quantity, price, total, status, created_at) 
                             VALUES (:invoice_code, NULL, :game_id, :customer_name, :contact, :game_uid, :item_name, :quantity, :price, :total, 'pending', NOW())");
        $ins->execute([
            ':invoice_code' => $invoiceCode,
            ':game_id' => $gameId,
            ':customer_name' => $customerName,
            ':contact' => $contact,
            ':game_uid' => $gameUid,
            ':item_name' => $itemName,
            ':quantity' => $quantity,
            ':price' => $price,
            ':total' => $total
        ]);
        $success = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($game['name']); ?> - Eyok Store V2</title>
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
        .glass-nav { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px); 
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="min-h-screen selection:bg-blue-500 selection:text-white flex flex-col">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2 text-slate-800 hover:text-blue-600 transition-colors group">
                <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="font-bold">Back to Store</span>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-600/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <span class="text-xl font-black tracking-tight text-slate-800">Eyok Store V2</span>
            </div>
        </div>
    </nav>

    <main class="flex-grow pt-32 pb-20 px-4">
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            <!-- Left: Game Info Card -->
            <div class="lg:col-span-1">
                <div class="sticky top-32">
                    <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-200">
                        <div class="aspect-[4/3] relative">
                            <?php if ($game['thumbnail'] && file_exists('uploads/thumbnails/' . $game['thumbnail'])): ?>
                                <img src="uploads/thumbnails/<?php echo htmlspecialchars($game['thumbnail']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($game['name']); ?>">
                            <?php else: ?>
                                <div class="w-full h-full bg-slate-100 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            <div class="absolute bottom-6 left-6 right-6">
                                <span class="px-3 py-1 bg-blue-600 text-white text-[10px] font-black uppercase tracking-widest rounded-full mb-2 inline-block">Official Store</span>
                                <h1 class="text-2xl font-black text-white"><?php echo htmlspecialchars($game['name']); ?></h1>
                                <p class="text-blue-100 text-sm font-medium"><?php echo htmlspecialchars($game['category_name']); ?></p>
                            </div>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="flex items-center gap-4 text-sm font-medium text-slate-700">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                Instant Fulfillment
                            </div>
                            <div class="flex items-center gap-4 text-sm font-medium text-slate-700">
                                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                100% Secure Payment
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Order Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200">
                    <h2 class="text-xl font-bold mb-8 flex items-center gap-3 text-slate-800">
                        <span class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-sm font-bold text-white">1</span>
                        Account Details
                    </h2>
                    
                    <form action="" method="POST" class="space-y-10" x-data="{ quantity: 1, price: <?php echo !empty($gameItems) ? $gameItems[0]['price'] : 0; ?> }">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Customer Name</label>
                                <input type="text" name="customer_name" value="<?php echo htmlspecialchars($customerName); ?>" required class="w-full bg-slate-50 border <?php echo isset($errors['customer_name']) ? 'border-red-500 ring-1 ring-red-500' : 'border-slate-200'; ?> rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 placeholder-slate-400" placeholder="Enter your name">
                                <?php if (isset($errors['customer_name'])): ?>
                                    <p class="mt-1.5 text-xs font-medium text-red-500"><?php echo $errors['customer_name']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">WhatsApp / Contact Number</label>
                                <input type="text" name="contact" value="<?php echo htmlspecialchars($contact); ?>" required class="w-full bg-slate-50 border <?php echo isset($errors['contact']) ? 'border-red-500 ring-1 ring-red-500' : 'border-slate-200'; ?> rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 placeholder-slate-400" placeholder="e.g. 081234567890">
                                <?php if (isset($errors['contact'])): ?>
                                    <p class="mt-1.5 text-xs font-medium text-red-500"><?php echo $errors['contact']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Game User ID (UID)</label>
                                <input type="text" name="game_uid" value="<?php echo htmlspecialchars($gameUid); ?>" required class="w-full bg-slate-50 border <?php echo isset($errors['game_uid']) ? 'border-red-500 ring-1 ring-red-500' : 'border-slate-200'; ?> rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-slate-800 placeholder-slate-400" placeholder="e.g. 123456789">
                                <?php if (isset($errors['game_uid'])): ?>
                                    <p class="mt-1.5 text-xs font-medium text-red-500"><?php echo $errors['game_uid']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-10">
                            <h2 class="text-xl font-bold mb-8 flex items-center gap-3 text-slate-800">
                                <span class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-sm font-bold text-white">2</span>
                                Select Top-Up Item
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach ($gameItems as $index => $item): ?>
                                <label class="relative group cursor-pointer">
                                    <input type="radio" name="item_name" value="<?php echo htmlspecialchars($item['name'] . ' ' . $currency); ?>" @click="price = <?php echo $item['price']; ?>" class="peer absolute opacity-0" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <div class="bg-white border-2 border-slate-200 rounded-2xl p-5 transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:border-blue-300">
                                        <div class="flex justify-between items-center">
                                            <span class="font-bold text-slate-800 peer-checked:text-blue-900"><?php echo htmlspecialchars($item['name'] . ' ' . $currency); ?></span>
                                            <span class="text-blue-600 font-bold">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                                
                                <?php if (empty($gameItems)): ?>
                                <div class="sm:col-span-2 p-8 text-center bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 text-slate-400">
                                    No items available for this game.
                                </div>
                                <?php endif; ?>

                                <div class="sm:col-span-2 mt-2">
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex items-center justify-between">
                                        <label class="text-sm text-slate-500 font-bold uppercase tracking-widest">Quantity</label>
                                        <div class="flex items-center gap-4">
                                            <button type="button" @click="if(quantity > 1) quantity--" class="w-8 h-8 rounded-full bg-white border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors">-</button>
                                            <input type="number" name="quantity" x-model.number="quantity" min="1" class="w-16 bg-transparent border-none p-0 focus:ring-0 text-xl font-bold text-center text-slate-800 outline-none" value="1">
                                            <button type="button" @click="quantity++" class="w-8 h-8 rounded-full bg-white border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-600 transition-colors">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="price" :value="price">

                        <div class="border-t border-slate-100 pt-10">
                            <div class="bg-slate-50 rounded-2xl p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 border border-slate-200">
                                <div>
                                    <p class="text-slate-500 text-sm font-bold tracking-widest uppercase mb-1">Total Payment</p>
                                    <p class="text-3xl font-black text-blue-600" x-text="'Rp ' + (price * quantity).toLocaleString('id-ID')"></p>
                                </div>
                                <button 
                                    type="submit" 
                                    @click="if(document.querySelector('form').checkValidity()) { Swal.fire({ title: 'Processing Order...', text: 'Please wait a moment', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } }) }"
                                    class="w-full md:w-auto px-10 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors active:scale-95 shadow-md flex items-center justify-center gap-2 group"
                                >
                                    Process Order
                                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

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
                        <li><a href="index.php" class="hover:text-blue-400 transition-colors">Home</a></li>
                        <li><a href="index.php" class="hover:text-blue-400 transition-colors">All Games</a></li>
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

    <?php if ($success): ?>
    <script>
        function showReceipt() {
            Swal.fire({
                title: 'BUKTI TRANSAKSI',
                html: `
                    <div class="text-left bg-white p-6 rounded-2xl border-2 border-slate-100 mt-4 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-2 bg-blue-600"></div>
                        <div class="text-center mb-6">
                            <h3 class="font-black text-xl text-slate-800">Eyok Store V2</h3>
                            <p class="text-[10px] text-slate-400 tracking-widest uppercase">Official Transaction Receipt</p>
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">Tanggal</span>
                                <span class="font-medium text-slate-700"><?php echo date('d M Y, H:i'); ?></span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">No. Invoice</span>
                                <span class="font-bold text-blue-600"><?php echo $invoiceCode; ?></span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">Game</span>
                                <span class="font-medium text-slate-700"><?php echo htmlspecialchars($game['name']); ?></span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">User ID / UID</span>
                                <span class="font-bold text-slate-700"><?php echo htmlspecialchars($gameUid); ?></span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">Item</span>
                                <span class="font-medium text-slate-700"><?php echo htmlspecialchars($itemName); ?></span>
                            </div>
                            <div class="flex justify-between pt-2">
                                <span class="text-slate-400">Total Bayar</span>
                                <span class="font-black text-lg text-slate-900">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <div class="mt-8 pt-4 border-t-2 border-slate-50 border-dashed text-center">
                            <div class="inline-block px-3 py-1 bg-green-50 text-green-600 rounded-full text-[10px] font-black uppercase tracking-widest mb-2">
                                Status: Pending / Menunggu
                            </div>
                            <p class="text-[10px] text-slate-400 leading-tight">
                                Harap simpan bukti ini. Pesanan Anda sedang diproses secara otomatis oleh sistem kami.
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 text-[10px] text-slate-400 text-center uppercase tracking-tighter">Terima kasih telah berbelanja di Eyok Store</p>
                `,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Kembali ke Beranda',
                allowOutsideClick: false,
                width: '400px',
                showClass: { popup: 'animate__animated animate__zoomIn animate__faster' }
            }).then(() => {
                window.location.href = 'index.php';
            });
        }

        Swal.fire({
            title: 'Pembayaran',
            html: `
                <div class="text-center bg-slate-50 p-6 rounded-2xl border border-slate-200 mt-4">
                    <p class="text-xs text-slate-500 mb-4">Scan QR DANA untuk menyelesaikan pembayaran:</p>
                    
                    <div class="bg-white p-4 rounded-xl border border-slate-200 flex justify-center mb-4">
                        <img src="uploads/shareqr.png" class="w-48 h-auto object-contain" alt="DANA QR">
                    </div>
                    
                    <div class="bg-blue-600 p-4 rounded-xl text-white text-sm font-bold flex justify-between items-center mb-4">
                        <span>Total Tagihan:</span>
                        <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>

                    <div class="text-[10px] text-slate-400 text-left leading-relaxed">
                        <p class="font-bold mb-1">Cara Bayar:</p>
                        <ol class="list-decimal pl-4 space-y-0.5">
                            <li>Scan QR dengan aplikasi DANA/E-Wallet</li>
                            <li>Masukkan nominal sesuai tagihan</li>
                            <li>Klik konfirmasi jika sudah membayar</li>
                        </ol>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Saya Sudah Bayar',
            allowOutsideClick: false,
            showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memverifikasi...',
                    timer: 1500,
                    timerProgressBar: true,
                    didOpen: () => { Swal.showLoading() }
                }).then(() => {
                    showReceipt();
                });
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>

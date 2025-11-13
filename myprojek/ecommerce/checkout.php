<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get cart items
$query = "
    SELECT c.*, p.product_name, p.price, p.image, p.stock,
           (p.price * c.quantity) as subtotal
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = $user_id
";
$cart_items = mysqli_query($conn, $query);

// Check if cart is empty
if (mysqli_num_rows($cart_items) == 0) {
    setAlert('warning', 'Keranjang belanja Anda kosong!');
    redirect('index.php');
}

// Calculate total
$total = 0;
$items_array = [];
while ($item = mysqli_fetch_assoc($cart_items)) {
    $total += $item['subtotal'];
    $items_array[] = $item;
}

// Get user data
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$user_data = mysqli_fetch_assoc($user_query);

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Validation
    $error = '';
    if (empty($full_name) || empty($phone) || empty($address)) {
        $error = 'Nama, telepon, dan alamat harus diisi!';
    }
    
    // Validate stock
    foreach ($items_array as $item) {
        if ($item['quantity'] > $item['stock']) {
            $error = 'Stok ' . $item['product_name'] . ' tidak mencukupi!';
            break;
        }
    }
    
    if (!$error) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert order
            $insert_order = "INSERT INTO orders (user_id, total_amount, status) 
                            VALUES ($user_id, $total, 'pending')";
            mysqli_query($conn, $insert_order);
            $order_id = mysqli_insert_id($conn);
            
            // Insert order items and update stock
            foreach ($items_array as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $subtotal = $item['subtotal'];
                
                // Insert order item
                mysqli_query($conn, "
                    INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                    VALUES ($order_id, $product_id, $quantity, $price, $subtotal)
                ");
                
                // Update stock
                mysqli_query($conn, "
                    UPDATE products 
                    SET stock = stock - $quantity 
                    WHERE product_id = $product_id
                ");
            }
            
            // Update user data if changed
            if ($phone != $user_data['phone'] || $address != $user_data['address']) {
                mysqli_query($conn, "
                    UPDATE users 
                    SET phone = '$phone', address = '$address' 
                    WHERE user_id = $user_id
                ");
            }
            
            // Clear cart
            mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
            
            // Commit transaction
            mysqli_commit($conn);
            
            setAlert('success', 'Pesanan berhasil dibuat! ID Pesanan: #' . $order_id);
            redirect('history.php');
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'Terjadi kesalahan saat memproses pesanan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FoodKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dynamic-colors.php">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shop"></i> FoodKu
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3"></i> Keranjang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">
                            <i class="bi bi-clock-history"></i> Riwayat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="bi bi-credit-card"></i> Checkout Pesanan
                </h2>
                
                <!-- Progress Steps -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between">
                        <div class="text-center">
                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="bi bi-cart-check"></i>
                            </div>
                            <p class="small mt-2 mb-0">Keranjang</p>
                        </div>
                        <div class="text-center">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <p class="small mt-2 mb-0 fw-bold">Checkout</p>
                        </div>
                        <div class="text-center">
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <p class="small mt-2 mb-0">Selesai</p>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="">
            <div class="row">
                <!-- Form Pengiriman -->
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-truck"></i> Informasi Pengiriman</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Lengkap *</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user_data['full_name']) ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nomor Telepon *</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" 
                                               placeholder="08xx xxxx xxxx" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" class="form-control" 
                                               value="<?= htmlspecialchars($user_data['email']) ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Alamat Lengkap *</label>
                                <textarea name="address" class="form-control" rows="3" 
                                          placeholder="Masukkan alamat lengkap untuk pengiriman" required><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Catatan Pesanan (Opsional)</label>
                                <textarea name="notes" class="form-control" rows="2" 
                                          placeholder="Contoh: Jangan pakai cabai, level pedas sedang, dll"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-wallet2"></i> Metode Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="cod" value="COD" checked>
                                <label class="form-check-label" for="cod">
                                    <strong>Cash on Delivery (COD)</strong>
                                    <p class="text-muted small mb-0">Bayar tunai saat pesanan tiba</p>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="transfer" value="Transfer Bank">
                                <label class="form-check-label" for="transfer">
                                    <strong>Transfer Bank</strong>
                                    <p class="text-muted small mb-0">BCA, BRI, Mandiri, BNI</p>
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="ewallet" value="E-Wallet">
                                <label class="form-check-label" for="ewallet">
                                    <strong>E-Wallet</strong>
                                    <p class="text-muted small mb-0">GoPay, OVO, Dana, ShopeePay</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Pesanan -->
                <div class="col-md-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-receipt"></i> Ringkasan Pesanan</h5>
                        </div>
                        <div class="card-body">
                            <!-- Items List -->
                            <div class="mb-3" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($items_array as $item): ?>
                                    <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                                        <div class="d-flex">
                                            <img src="<?= file_exists('uploads/' . $item['image']) ? 'uploads/' . $item['image'] : 'assets/img/no-image.png' ?>" 
                                                 width="50" height="50" class="rounded me-2" style="object-fit: cover;">
                                            <div>
                                                <p class="mb-0 small fw-bold"><?= htmlspecialchars($item['product_name']) ?></p>
                                                <small class="text-muted"><?= $item['quantity'] ?>x <?= formatRupiah($item['price']) ?></small>
                                            </div>
                                        </div>
                                        <p class="mb-0 fw-bold"><?= formatRupiah($item['subtotal']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Summary -->
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span><?= formatRupiah($total) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Biaya Pengiriman</span>
                                    <span class="text-success fw-bold">GRATIS</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Biaya Admin</span>
                                    <span>Rp 0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong class="fs-5">Total Bayar</strong>
                                    <strong class="fs-5 text-primary"><?= formatRupiah($total) ?></strong>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <button type="submit" class="btn btn-primary w-100 py-2 mb-2">
                                <i class="bi bi-check-circle"></i> Buat Pesanan
                            </button>
                            <a href="cart.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
                            </a>

                            <!-- Info -->
                            <div class="alert alert-info mt-3 small mb-0">
                                <i class="bi bi-info-circle"></i> 
                                Pesanan Anda akan diproses setelah pembayaran dikonfirmasi
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2025 FoodKu E-Commerce Makanan. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
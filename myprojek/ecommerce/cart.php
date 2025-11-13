<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $product_id = (int)$_POST['product_id'];
        
        // Cek apakah produk sudah ada di cart
        $check = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id");
        
        if (mysqli_num_rows($check) > 0) {
            // Update quantity
            mysqli_query($conn, "UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND product_id = $product_id");
        } else {
            // Insert new
            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, 1)");
        }
        
        setAlert('success', 'Produk ditambahkan ke keranjang!');
        redirect('cart.php');
    }
    
    if ($action == 'update') {
        $cart_id = (int)$_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            mysqli_query($conn, "UPDATE cart SET quantity = $quantity WHERE cart_id = $cart_id AND user_id = $user_id");
            setAlert('success', 'Keranjang diupdate!');
        }
        redirect('cart.php');
    }
    
    if ($action == 'delete') {
        $cart_id = (int)$_POST['cart_id'];
        mysqli_query($conn, "DELETE FROM cart WHERE cart_id = $cart_id AND user_id = $user_id");
        setAlert('success', 'Produk dihapus dari keranjang!');
        redirect('cart.php');
    }
    
    if ($action == 'checkout') {
        // Get cart items
        $cart_items = mysqli_query($conn, "
            SELECT c.*, p.product_name, p.price, p.stock 
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.user_id = $user_id
        ");
        
        if (mysqli_num_rows($cart_items) == 0) {
            setAlert('danger', 'Keranjang belanja kosong!');
            redirect('cart.php');
        }
        
        // Calculate total
        $total = 0;
        $valid = true;
        
        while ($item = mysqli_fetch_assoc($cart_items)) {
            if ($item['quantity'] > $item['stock']) {
                setAlert('danger', 'Stok ' . $item['product_name'] . ' tidak mencukupi!');
                $valid = false;
                break;
            }
            $total += $item['price'] * $item['quantity'];
        }
        
        if ($valid) {
            // Begin transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Insert order
                $insert_order = "INSERT INTO orders (user_id, total_amount, status) VALUES ($user_id, $total, 'pending')";
                mysqli_query($conn, $insert_order);
                $order_id = mysqli_insert_id($conn);
                
                // Reset cart items query
                mysqli_data_seek($cart_items, 0);
                
                // Insert order items and update stock
                while ($item = mysqli_fetch_assoc($cart_items)) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];
                    $subtotal = $price * $quantity;
                    
                    mysqli_query($conn, "
                        INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                        VALUES ($order_id, $product_id, $quantity, $price, $subtotal)
                    ");
                    
                    mysqli_query($conn, "UPDATE products SET stock = stock - $quantity WHERE product_id = $product_id");
                }
                
                // Clear cart
                mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
                
                mysqli_commit($conn);
                setAlert('success', 'Pesanan berhasil dibuat! ID Pesanan: #' . $order_id);
                redirect('history.php');
            } catch (Exception $e) {
                mysqli_rollback($conn);
                setAlert('danger', 'Terjadi kesalahan saat checkout!');
                redirect('cart.php');
            }
        } else {
            redirect('cart.php');
        }
    }
}

// Get cart items
$query = "
    SELECT c.*, p.product_name, p.price, p.image, p.stock,
           (p.price * c.quantity) as subtotal
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = $user_id
";
$cart_items = mysqli_query($conn, $query);

// Calculate total
$total = 0;
while ($item = mysqli_fetch_assoc($cart_items)) {
    $total += $item['subtotal'];
}
mysqli_data_seek($cart_items, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Kedai Punasa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shop"></i> Kedai Punasa
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
                        <a class="nav-link active" href="cart.php">
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
        <h2 class="mb-4"><i class="bi bi-cart3"></i> Keranjang Belanja</h2>
        
        <?php showAlert(); ?>

        <?php if (mysqli_num_rows($cart_items) > 0): ?>
            <div class="row">
                <div class="col-md-8">
                    <?php while ($item = mysqli_fetch_assoc($cart_items)): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?= file_exists('uploads/' . $item['image']) ? 'uploads/' . $item['image'] : 'assets/img/no-image.png' ?>" 
                                             class="img-fluid rounded" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                        <p class="text-primary fw-bold"><?= formatRupiah($item['price']) ?></p>
                                        <small class="text-muted">Stok: <?= $item['stock'] ?></small>
                                    </div>
                                    <div class="col-md-3">
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <input type="hidden" name="action" value="update">
                                            <div class="input-group">
                                                <input type="number" name="quantity" class="form-control" 
                                                       value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="fw-bold"><?= formatRupiah($item['subtotal']) ?></p>
                                    </div>
                                    <div class="col-md-1">
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Hapus produk ini?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Ringkasan Pesanan</h5>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total</span>
                                <span class="fw-bold text-primary fs-5"><?= formatRupiah($total) ?></span>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="checkout">
                                <button type="submit" class="btn btn-primary w-100 py-2" 
                                        onclick="return confirm('Lanjutkan checkout?')">
                                    <i class="bi bi-credit-card"></i> Checkout
                                </button>
                            </form>
                            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-arrow-left"></i> Lanjut Belanja
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-cart-x fs-1"></i>
                <p class="mt-3">Keranjang belanja Anda kosong</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-shop"></i> Mulai Belanja
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
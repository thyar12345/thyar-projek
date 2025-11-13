<?php
require_once 'config.php';

// Get filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Query produk dengan filter
$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE 1=1";

if ($search) {
    $query .= " AND (p.product_name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

if ($category > 0) {
    $query .= " AND p.category_id = $category";
}

$query .= " ORDER BY p.created_at DESC";
$products = mysqli_query($conn, $query);

// Query categories untuk filter
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kedai Punasa - E-Commerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
   <!-- Navbar - Modern Design -->
<!-- Navbar - Modern Design -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-lg">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php" style="font-size: 1.5rem;">
            <span style="background: white; color: #0d6efd; padding: 8px 12px; border-radius: 50%; margin-right: 10px;">
                <i class="bi bi-shop"></i>
            </span>
            Kedai Punasa
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-2">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link active rounded-pill px-3" href="index.php" style="background: rgba(255,255,255,0.2);">
                            <i class="bi bi-house-door-fill"></i> Home
                        </a>
                    </li>
                 <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill px-4 py-2" href="admin/index.php" style="font-weight: 500; transition: all 0.3s;">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill px-4 py-2" href="cart.php" style="font-weight: 500; transition: all 0.3s;">
                                <i class="bi bi-cart3"></i> Keranjang
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill px-4 py-2" href="history.php" style="font-weight: 500; transition: all 0.3s;">
                                <i class="bi bi-clock-history"></i> Riwayat
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-4 py-2" href="logout.php" style="color: #ffc107; font-weight: 600; transition: all 0.3s;">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-4 py-2" href="index.php" style="background:white; color: #0d6efd; font-weight: 600; transition: all 0.3s;">
                            <i class="bi bi-house-door-fill"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-4 py-2" href="login.php" style="background: white; color: #0d6efd; font-weight: 600; transition: all 0.3s;">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link rounded-pill px-4 py-2" href="register.php" style="background: white; color: #0d6efd; font-weight: 600; transition: all 0.3s;">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.nav-link:hover {
    background: rgba(255,255,255,0.5) !important;
    transform: translateY(-2px);
}
</style>

<style>
.nav-link:hover {
    background: rgba(255,255,255,0.3) !important;
    transform: translateY(-2px);
}
</style>

    <!-- Hero Section -->
<div class="hero-section bg-gradient text-white py-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3" style="color: #ff6600; text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);">
            Selamat Datang di Kedai Punasa
        </h1>
        <p class="lead" style="color: #ff8c00; text-shadow: 1px 1px 6px rgba(0, 0, 0, 0.6);">
            Tempat cocok jajanan kekinian
        </p>
    </div>
</div>

    <div class="container my-5">
        <?php showAlert(); ?>

        <!-- Filter Section -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="0">Semua Kategori</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row g-4">
            <?php if (mysqli_num_rows($products) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($products)): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card product-card h-100 shadow-sm">
                            <img src="<?= file_exists('uploads/' . $product['image']) ? 'uploads/' . $product['image'] : 'assets/img/no-image.png' ?>" 
                                 class="card-img-top" alt="<?= htmlspecialchars($product['product_name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                                <p class="card-text text-muted small"><?= substr(htmlspecialchars($product['description']), 0, 60) ?>...</p>
                                <div class="mt-auto">
                                    <p class="text-primary fw-bold fs-5 mb-2"><?= formatRupiah($product['price']) ?></p>
                                    <p class="text-muted small mb-3">Stok: <?= $product['stock'] ?></p>
                                    <?php if (isLoggedIn() && !isAdmin()): ?>
                                        <form method="POST" action="cart.php">
                                            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                            <input type="hidden" name="action" value="add">
                                            <button type="submit" class="btn btn-primary w-100" <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
                                                <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary w-100">Login untuk Beli</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> Produk tidak ditemukan
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; Kedai Punasa. All rights reserved.</p>
            <p>Contact: 085733404927</p>
            <p> Instagram: @thyar_rdh</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
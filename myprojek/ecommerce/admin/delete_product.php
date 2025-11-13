<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$product_id = (int)$_GET['id'];

// Get product
$result = mysqli_query($conn, "SELECT * FROM products WHERE product_id = $product_id");
$product = mysqli_fetch_assoc($result);

if ($product) {
    // Delete image file
    if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])) {
        unlink(UPLOAD_DIR . $product['image']);
    }
    
    // Delete product
    mysqli_query($conn, "DELETE FROM products WHERE product_id = $product_id");
    setAlert('success', 'Produk berhasil dihapus!');
} else {
    setAlert('danger', 'Produk tidak ditemukan!');
}

redirect('admin/products.php');
?>
<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Create settings table if not exists
$create_settings = "CREATE TABLE IF NOT EXISTS shop_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_settings);

// Insert default settings if not exists
$defaults = [
    'shop_name' => 'Kedai Punasa',
    'shop_tagline' => 'Temukan produk jajanan viral kekinian',
    'shop_description' => 'Platform e-commerce Viral Kekinian',
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'contact_email' => 'admin@tokoku.com',
    'contact_phone' => '081234567890',
    'contact_address' => 'Jl. Raya No. 123, Jakarta',
    'currency' => 'Rp',
    'products_per_page' => '12',
    'enable_registration' => '1',
    'maintenance_mode' => '0'
];

foreach ($defaults as $key => $value) {
    $check = mysqli_query($conn, "SELECT * FROM shop_settings WHERE setting_key = '$key'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO shop_settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST as $key => $value) {
        if ($key != 'update_settings') {
            $value = mysqli_real_escape_string($conn, $value);
            mysqli_query($conn, "UPDATE shop_settings SET setting_value = '$value' WHERE setting_key = '$key'");
        }
    }
    setAlert('success', 'Pengaturan berhasil diupdate!');
    redirect('admin/settings.php');
}

// Handle bulk delete images
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_selected'])) {
    $selected = $_POST['selected_images'] ?? [];
    $deleted = 0;
    
    foreach ($selected as $image) {
        $image = mysqli_real_escape_string($conn, $image);
        $file_path = UPLOAD_DIR . $image;
        
        if (file_exists($file_path)) {
            unlink($file_path);
            $deleted++;
            
            // Update products yang pakai gambar ini
            mysqli_query($conn, "UPDATE products SET image = NULL WHERE image = '$image'");
        }
    }
    
    setAlert('success', "$deleted gambar berhasil dihapus!");
    redirect('admin/settings.php');
}

// Handle single delete image
if (isset($_GET['delete_image'])) {
    $image = mysqli_real_escape_string($conn, $_GET['delete_image']);
    $file_path = UPLOAD_DIR . $image;
    
    if (file_exists($file_path)) {
        unlink($file_path);
        mysqli_query($conn, "UPDATE products SET image = NULL WHERE image = '$image'");
        setAlert('success', 'Gambar berhasil dihapus!');
    } else {
        setAlert('danger', 'Gambar tidak ditemukan!');
    }
    
    redirect('admin/settings.php');
}

// Get all settings
$settings = [];
$result = mysqli_query($conn, "SELECT * FROM shop_settings");
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get all uploaded images
$upload_dir = UPLOAD_DIR;
$images = [];
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            $file_path = $upload_dir . $file;
            $file_size = filesize($file_path);
            $file_date = date('Y-m-d H:i:s', filemtime($file_path));
            
            // Check if image is used
            $check_usage = mysqli_query($conn, "SELECT product_name FROM products WHERE image = '$file'");
            $used_by = [];
            while ($product = mysqli_fetch_assoc($check_usage)) {
                $used_by[] = $product['product_name'];
            }
            
            $images[] = [
                'filename' => $file,
                'size' => $file_size,
                'date' => $file_date,
                'used_by' => $used_by
            ];
        }
    }
}

// Sort by date (newest first)
usort($images, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Calculate total size
$total_size = array_sum(array_column($images, 'size'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Toko - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .image-card {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .image-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .image-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 25px;
            height: 25px;
            cursor: pointer;
        }
        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 5px;
            border: 2px solid #ddd;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">Pengaturan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Lihat Toko</a>
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

    <div class="container-fluid py-4">
        <?php showAlert(); ?>

        <h2 class="mb-4"><i class="bi bi-gear"></i> Pengaturan Toko</h2>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                    <i class="bi bi-shop"></i> Umum
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button">
                    <i class="bi bi-palette"></i> Tampilan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images" type="button">
                    <i class="bi bi-images"></i> Kelola Gambar (<?= count($images) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-shop"></i> Pengaturan Umum</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Toko</label>
                                        <input type="text" name="shop_name" class="form-control" 
                                               value="<?= htmlspecialchars($settings['shop_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tagline/Slogan</label>
                                        <input type="text" name="shop_tagline" class="form-control" 
                                               value="<?= htmlspecialchars($settings['shop_tagline']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi Toko</label>
                                <textarea name="shop_description" class="form-control" rows="3"><?= htmlspecialchars($settings['shop_description']) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Email Kontak</label>
                                        <input type="email" name="contact_email" class="form-control" 
                                               value="<?= htmlspecialchars($settings['contact_email']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Telepon</label>
                                        <input type="text" name="contact_phone" class="form-control" 
                                               value="<?= htmlspecialchars($settings['contact_phone']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Mata Uang</label>
                                        <input type="text" name="currency" class="form-control" 
                                               value="<?= htmlspecialchars($settings['currency']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="contact_address" class="form-control" rows="2"><?= htmlspecialchars($settings['contact_address']) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Produk per Halaman</label>
                                        <input type="number" name="products_per_page" class="form-control" 
                                               value="<?= htmlspecialchars($settings['products_per_page']) ?>" min="4" max="50">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Aktifkan Registrasi</label>
                                        <select name="enable_registration" class="form-select">
                                            <option value="1" <?= $settings['enable_registration'] == '1' ? 'selected' : '' ?>>Ya</option>
                                            <option value="0" <?= $settings['enable_registration'] == '0' ? 'selected' : '' ?>>Tidak</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Mode Maintenance</label>
                                        <select name="maintenance_mode" class="form-select">
                                            <option value="0" <?= $settings['maintenance_mode'] == '0' ? 'selected' : '' ?>>Tidak Aktif</option>
                                            <option value="1" <?= $settings['maintenance_mode'] == '1' ? 'selected' : '' ?>>Aktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Pengaturan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Appearance Settings -->
            <div class="tab-pane fade" id="appearance" role="tabpanel">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-palette"></i> Pengaturan Tampilan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Ubah warna tema website Anda. Refresh halaman setelah menyimpan untuk melihat perubahan.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Warna Utama (Primary)</label>
                                        <div class="input-group">
                                            <input type="color" name="primary_color" class="form-control form-control-color" 
                                                   value="<?= htmlspecialchars($settings['primary_color']) ?>" 
                                                   title="Pilih warna utama">
                                            <input type="text" class="form-control" 
                                                   value="<?= htmlspecialchars($settings['primary_color']) ?>" 
                                                   readonly>
                                            <span class="color-preview" style="background-color: <?= htmlspecialchars($settings['primary_color']) ?>"></span>
                                        </div>
                                        <small class="text-muted">Warna untuk navbar, button, dan elemen utama</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Warna Sekunder (Secondary)</label>
                                        <div class="input-group">
                                            <input type="color" name="secondary_color" class="form-control form-control-color" 
                                                   value="<?= htmlspecialchars($settings['secondary_color']) ?>" 
                                                   title="Pilih warna sekunder">
                                            <input type="text" class="form-control" 
                                                   value="<?= htmlspecialchars($settings['secondary_color']) ?>" 
                                                   readonly>
                                            <span class="color-preview" style="background-color: <?= htmlspecialchars($settings['secondary_color']) ?>"></span>
                                        </div>
                                        <small class="text-muted">Warna untuk gradient dan accent</small>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-header">
                                    <strong>Preset Tema Warna</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-secondary w-100" 
                                                    onclick="setColors('#667eea', '#764ba2')">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <div style="width:20px;height:20px;background:#667eea;border-radius:3px"></div>
                                                    <div style="width:20px;height:20px;background:#764ba2;border-radius:3px"></div>
                                                </div>
                                                Ungu (Default)
                                            </button>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-primary w-100" 
                                                    onclick="setColors('#1e90ff', '#0066cc')">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <div style="width:20px;height:20px;background:#1e90ff;border-radius:3px"></div>
                                                    <div style="width:20px;height:20px;background:#0066cc;border-radius:3px"></div>
                                                </div>
                                                Biru
                                            </button>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger w-100" 
                                                    onclick="setColors('#ff6b6b', '#ee5a6f')">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <div style="width:20px;height:20px;background:#ff6b6b;border-radius:3px"></div>
                                                    <div style="width:20px;height:20px;background:#ee5a6f;border-radius:3px"></div>
                                                </div>
                                                Merah
                                            </button>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-success w-100" 
                                                    onclick="setColors('#06a77d', '#4caf50')">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <div style="width:20px;height:20px;background:#06a77d;border-radius:3px"></div>
                                                    <div style="width:20px;height:20px;background:#4caf50;border-radius:3px"></div>
                                                </div>
                                                Hijau
                                            </button>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-warning w-100" 
                                                    onclick="setColors('#ff6b35', '#f7931e')">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <div style="width:20px;height:20px;background:#ff6b35;border-radius:3px"></div>
                                                    <div style="width:20px;height:20px;background:#f7931e;border-radius:3px"></div>
                                                </div>
                                                Orange
                                            </button>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-info w-100" 
                                                    onclick="setColors('#00d2ff', '#3a7bd5')">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <div style="width:20px;height:20px;background:#00d2ff;border-radius:3px"></div>
                                                    <div style="width:20px;height:20px;background:#3a7bd5;border-radius:3px"></div>
                                                </div>
                                                Cyan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="update_settings" class="btn btn-info text-white">
                                <i class="bi bi-save"></i> Simpan Tampilan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Image Management -->
            <div class="tab-pane fade" id="images" role="tabpanel">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-images"></i> Kelola Gambar Produk</h5>
                            <div>
                                <span class="badge bg-light text-dark">
                                    Total: <?= count($images) ?> gambar | 
                                    <?= number_format($total_size / 1024 / 1024, 2) ?> MB
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($images) > 0): ?>
                            <form method="POST" action="" id="deleteImagesForm">
                                <div class="d-flex justify-content-between mb-3">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAll()">
                                            <i class="bi bi-check-all"></i> Pilih Semua
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAll()">
                                            <i class="bi bi-x"></i> Batal Pilih
                                        </button>
                                    </div>
                                    <button type="submit" name="delete_selected" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Hapus gambar yang dipilih? Produk yang menggunakan gambar ini akan kehilangan gambarnya!')">
                                        <i class="bi bi-trash"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <?php foreach ($images as $image): ?>
                                        <div class="col-md-3">
                                            <div class="image-card">
                                                <input type="checkbox" name="selected_images[]" 
                                                       value="<?= htmlspecialchars($image['filename']) ?>" 
                                                       class="image-checkbox" 
                                                       onchange="updateCount()">
                                                <img src="../uploads/<?= htmlspecialchars($image['filename']) ?>" 
                                                     alt="<?= htmlspecialchars($image['filename']) ?>">
                                                <div class="p-2">
                                                    <small class="d-block text-truncate" title="<?= htmlspecialchars($image['filename']) ?>">
                                                        <i class="bi bi-file-image"></i> <?= htmlspecialchars($image['filename']) ?>
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-hdd"></i> <?= number_format($image['size'] / 1024, 2) ?> KB
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-calendar"></i> <?= date('d/m/Y H:i', strtotime($image['date'])) ?>
                                                    </small>
                                                    <?php if (count($image['used_by']) > 0): ?>
                                                        <small class="text-success d-block">
                                                            <i class="bi bi-check-circle"></i> Digunakan: 
                                                            <strong><?= count($image['used_by']) ?> produk</strong>
                                                        </small>
                                                        <small class="text-muted">
                                                            <?= implode(', ', array_slice($image['used_by'], 0, 2)) ?>
                                                            <?= count($image['used_by']) > 2 ? '...' : '' ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-warning d-block">
                                                            <i class="bi bi-exclamation-circle"></i> Tidak digunakan
                                                        </small>
                                                    <?php endif; ?>
                                                    <div class="mt-2 d-flex gap-1">
                                                        <a href="../uploads/<?= htmlspecialchars($image['filename']) ?>" 
                                                           target="_blank" class="btn btn-sm btn-info flex-fill">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="?delete_image=<?= urlencode($image['filename']) ?>" 
                                                           class="btn btn-sm btn-danger flex-fill"
                                                           onclick="return confirm('Hapus gambar ini?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-images fs-1"></i>
                                <p class="mt-3">Belum ada gambar yang diupload</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setColors(primary, secondary) {
            document.querySelector('input[name="primary_color"]').value = primary;
            document.querySelectorAll('input[name="primary_color"]')[1].value = primary;
            document.querySelector('input[name="secondary_color"]').value = secondary;
            document.querySelectorAll('input[name="secondary_color"]')[1].value = secondary;
            
            // Update preview
            document.querySelectorAll('.color-preview')[0].style.backgroundColor = primary;
            document.querySelectorAll('.color-preview')[1].style.backgroundColor = secondary;
        }

        function selectAll() {
            document.querySelectorAll('.image-checkbox').forEach(cb => cb.checked = true);
            updateCount();
        }

        function deselectAll() {
            document.querySelectorAll('.image-checkbox').forEach(cb => cb.checked = false);
            updateCount();
        }

        function updateCount() {
            const count = document.querySelectorAll('.image-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // Update color preview on input change
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('change', function() {
                const textInput = this.nextElementSibling;
                const preview = textInput.nextElementSibling;
                textInput.value = this.value;
                preview.style.backgroundColor = this.value;
            });
        });
    </script>
</body>
</html>
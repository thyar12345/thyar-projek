<?php
header("Content-type: text/css; charset: UTF-8");

// Include config
require_once '../../config.php';

// Get colors from settings
$primary = '#ff6b35'; // default - warm orange
$secondary = '#e63946'; // default - cherry red

$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM shop_settings WHERE setting_key IN ('primary_color', 'secondary_color')");
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['setting_key'] == 'primary_color') {
        $primary = $row['setting_value'];
    }
    if ($row['setting_key'] == 'secondary_color') {
        $secondary = $row['setting_value'];
    }
}

// Convert hex to RGB for rgba usage
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

$primary_rgb = hexToRgb($primary);
$secondary_rgb = hexToRgb($secondary);

// Darken color for hover effect
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

$primary_dark = adjustBrightness($primary, -30);
$secondary_dark = adjustBrightness($secondary, -30);
?>

/* Dynamic Colors CSS - Auto-generated from Database */

/* Hero Section & Gradient Backgrounds */
.hero-section,
.bg-gradient {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Primary Buttons */
.btn-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
    border: none !important;
}

.btn-primary:hover,
.btn-primary:focus {
    background: linear-gradient(135deg, <?= $primary_dark ?> 0%, <?= $secondary_dark ?> 100%) !important;
    box-shadow: 0 5px 15px rgba(<?= $primary_rgb ?>, 0.4) !important;
}

/* Navbar Primary */
.navbar.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Card Headers */
.card-header.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Dashboard Cards */
.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Form Focus */
.form-control:focus,
.form-select:focus {
    border-color: <?= $primary ?> !important;
    box-shadow: 0 0 0 0.2rem rgba(<?= $primary_rgb ?>, 0.25) !important;
}

/* Input Group Text */
.input-group-text {
    background-color: <?= $primary ?> !important;
    color: white !important;
    border: none !important;
}

/* Links */
a:not(.btn) {
    color: <?= $primary ?> !important;
}

a:not(.btn):hover {
    color: <?= $primary_dark ?> !important;
}

/* Text Primary */
.text-primary {
    color: <?= $primary ?> !important;
}

/* Border Primary */
.border-primary {
    border-color: <?= $primary ?> !important;
}

/* Badge Primary */
.badge.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Scrollbar */
::-webkit-scrollbar-thumb {
    background: <?= $primary ?> !important;
}

/* Product Card Hover */
.product-card:hover {
    border-color: <?= $primary ?> !important;
}

/* Alert Primary */
.alert-primary {
    background-color: rgba(<?= $primary_rgb ?>, 0.1) !important;
    border-color: <?= $primary ?> !important;
    color: <?= $primary_dark ?> !important;
}

/* Pagination Active */
.page-item.active .page-link {
    background-color: <?= $primary ?> !important;
    border-color: <?= $primary ?> !important;
}

/* Table Primary */
.table-primary {
    background-color: rgba(<?= $primary_rgb ?>, 0.1) !important;
}

/* Progress Bar Primary */
.progress-bar.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Nav Pills Active */
.nav-pills .nav-link.active {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Dropdown Item Active */
.dropdown-item:active,
.dropdown-item.active {
    background-color: <?= $primary ?> !important;
}

/* List Group Item Active */
.list-group-item.active {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
    border-color: <?= $primary ?> !important;
}

/* Outline Buttons */
.btn-outline-primary {
    color: <?= $primary ?> !important;
    border-color: <?= $primary ?> !important;
}

.btn-outline-primary:hover {
    background-color: <?= $primary ?> !important;
    border-color: <?= $primary ?> !important;
}

/* Custom Checkbox & Radio */
.form-check-input:checked {
    background-color: <?= $primary ?> !important;
    border-color: <?= $primary ?> !important;
}

/* Spinner Primary */
.spinner-border.text-primary {
    color: <?= $primary ?> !important;
}

/* Toast Primary */
.toast-header.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}

/* Modal Header Primary */
.modal-header.bg-primary {
    background: linear-gradient(135deg, <?= $primary ?> 0%, <?= $secondary ?> 100%) !important;
}
<?php

function red($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $path = ltrim($path, '/');
    
    header("Location: " . $protocol . "://" . $host . $script_path . "/" . $path);
    exit();
}
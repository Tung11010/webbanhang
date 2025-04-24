<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giữ giỏ hàng trong session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Xóa các session khác
session_unset();
session_destroy();

// Khởi tạo lại session và khôi phục giỏ hàng
session_start();
$_SESSION['cart'] = $cart;

header("Location: register.php");
exit();
?>
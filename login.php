<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Đồng bộ giỏ hàng từ session vào database
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $product_id]);
                $order = $stmt->fetch();

                // Lấy giá sản phẩm để tính total_price
                $product_stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                $product_stmt->execute([$product_id]);
                $product = $product_stmt->fetch();
                $total_price = $product ? $product['price'] * $quantity : 0;

                if ($order) {
                    // Cập nhật số lượng và tổng tiền nếu sản phẩm đã tồn tại trong orders
                    $stmt = $conn->prepare("UPDATE orders SET quantity = ?, total_price = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$quantity, $total_price, $_SESSION['user_id'], $product_id]);
                } else {
                    // Thêm mới vào orders
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, order_date) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $total_price]);
                }
            }
        }

        header("Location: index.php");
        exit();
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Book Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .form-container {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h3 class="text-center mb-1">Sign in</h3>
    <p class="text-center text-muted mb-4">Sign in to Book Stores</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <input type="text" class="form-control" name="username" placeholder="Name" required>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">Sign in</button>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Remember me</label>
        </div>
    </form>
    <p class="text-center mt-3">Don't have an account? <a href="register.php">Sign up</a></p>
</div>
</body>
</html>

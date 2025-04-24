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

include 'includes/header.php';
?>

<div class="container mt-5">
    <h2>Đăng nhập</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" class="col-md-6">
        <div class="mb-3">
            <label for="username" class="form-label">Tên đăng nhập</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mật khẩu</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Đăng nhập</button>
        <a href="register.php" class="btn btn-link">Đăng ký</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
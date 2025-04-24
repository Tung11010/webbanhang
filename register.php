<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Kiểm tra username đã tồn tại chưa
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = "Tên đăng nhập đã tồn tại!";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email đã được sử dụng!";
        } else {
            // Thêm người dùng mới
            try {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $email, $password]);
                $_SESSION['success'] = "Đăng ký thành công! Bạn có thể đăng nhập.";
                header("Location: register.php"); // Tạm thời chuyển hướng về chính trang này để hiển thị alert
                exit();
            } catch (PDOException $e) {
                $error = "Đăng ký thất bại: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Book Store</title>
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
    <h3 class="text-center mb-1">Sign up</h3>
    <p class="text-center text-muted mb-4">Sign up to continue</p>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <input type="text" class="form-control" name="username" placeholder="Name" required>
        </div>
        <div class="mb-3">
            <input type="email" class="form-control" name="email" placeholder="Email" required>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">Sign up</button>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Remember me</label>
        </div>
    </form>
    <p class="text-center mt-3">Already have an account? <a href="login.php">Sign in</a></p>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        alert("<?php echo $_SESSION['success']; ?>");
        <?php unset($_SESSION['success']); // Xóa session sau khi hiển thị ?>
        window.location.href = "login.php"; // Chuyển hướng sau khi nhấn OK
    </script>
<?php endif; ?>
</body>
</html>
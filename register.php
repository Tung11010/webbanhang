<?php
include 'includes/header.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
    if ($stmt->execute([$username, $email, $password])) {
        echo "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login here</a></div>";
    } else {
        echo "<div class='alert alert-danger'>Registration failed!</div>";
    }
}
?>
<h2>Register</h2>
<form method="POST" class="col-md-6">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Register</button>
</form>
<?php include 'includes/footer.php'; ?>
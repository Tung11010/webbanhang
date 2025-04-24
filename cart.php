<?php
include 'includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['add'])) {
    $product_id = $_GET['add'];
    $user_id = $_SESSION['user_id'];
    $quantity = 1;
    
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    $total_price = $product['price'] * $quantity;
    
    $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $quantity, $total_price]);
    echo "<div class='alert alert-success'>Product added to cart!</div>";
}

$stmt = $conn->prepare("SELECT o.*, p.name, p.price, p.image FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>
<h2>Your Cart</h2>
<table class="table">
    <thead>
        <tr>
            <th>Image</th>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><img src="assets/images/<?php echo $order['image']; ?>" width="50" alt="<?php echo $order['name']; ?>"></td>
                <td><?php echo $order['name']; ?></td>
                <td>$<?php echo $order['price']; ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td>$<?php echo $order['total_price']; ?></td>
                <td><?php echo $order['order_date']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include 'includes/footer.php'; ?>
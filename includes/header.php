<?php
require_once 'db.php';

// Lấy danh sách sản phẩm từ database
$stmt = $conn->query("SELECT * FROM products");
$books = $stmt->fetchAll();

// Nếu không có sản phẩm trong database, sử dụng dữ liệu mẫu
if (empty($books)) {
    $books = [
        ['id' => 1, 'name' => 'Coco Goose', 'image' => 'arrival_1.jpg', 'price' => 25.50],
        ['id' => 2, 'name' => 'Subtlety', 'image' => 'arrival_2.jpg', 'price' => 25.50],
        ['id' => 3, 'name' => 'Westpart', 'image' => 'arrival_3.jpg', 'price' => 25.50],
        ['id' => 4, 'name' => 'Book 4', 'image' => 'arrival_4.jpg', 'price' => 25.50],
        ['id' => 5, 'name' => 'Clever Lands', 'image' => 'arrival_5.jpg', 'price' => 25.50],
        ['id' => 6, 'name' => 'Book 6', 'image' => 'arrival_6.jpg', 'price' => 25.50],
        ['id' => 7, 'name' => 'Book 7', 'image' => 'arrival_7.jpg', 'price' => 25.50],
        ['id' => 8, 'name' => 'Book 8', 'image' => 'arrival_8.webp', 'price' => 25.50],
        ['id' => 9, 'name' => 'Book 9', 'image' => 'arrival_9.jpg', 'price' => 25.50],
        ['id' => 10, 'name' => 'Book 10', 'image' => 'arrival_10.jpg', 'price' => 25.50],
    ];
}

// Xử lý AJAX cho tăng/giảm số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($action === 'increase') {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]++;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
    } elseif ($action === 'decrease') {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]--;
            if ($_SESSION['cart'][$product_id] <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }

    if (empty($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }

    // Đồng bộ với database nếu người dùng đã đăng nhập
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['cart'][$product_id])) {
            $quantity = $_SESSION['cart'][$product_id];
            $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $order = $stmt->fetch();

            $product_stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $product_stmt->execute([$product_id]);
            $product = $product_stmt->fetch();
            $total_price = $product ? $product['price'] * $quantity : 0;

            if ($order) {
                $stmt = $conn->prepare("UPDATE orders SET quantity = ?, total_price = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $total_price, $_SESSION['user_id'], $product_id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, order_date) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $total_price]);
            }
        } else {
            // Xóa sản phẩm khỏi database nếu số lượng = 0
            $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
        }
    }

    // Tính toán lại tổng tiền và số lượng
    $total_price = 0;
    $cart_count = 0;
    if (isset($_SESSION['cart'])) {
        $cart_count = array_sum($_SESSION['cart']);
        foreach ($_SESSION['cart'] as $id => $quantity) {
            $product = array_filter($books, function($book) use ($id) {
                return $book['id'] == $id;
            });
            $product = array_shift($product);
            if ($product) {
                $total_price += $product['price'] * $quantity;
            }
        }
    }

    // Trả về dữ liệu JSON
    echo json_encode([
        'cart_count' => $cart_count,
        'total_price' => number_format($total_price, 2),
        'quantity' => isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0,
        'item_total' => isset($_SESSION['cart'][$product_id]) ? number_format($books[array_search($product_id, array_column($books, 'id'))]['price'] * $_SESSION['cart'][$product_id], 2) : 0,
        'cart_empty' => !isset($_SESSION['cart']) || empty($_SESSION['cart'])
    ]);
    exit();
}

// Xử lý xóa sản phẩm khỏi giỏ hàng
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        if (empty($_SESSION['cart'])) {
            unset($_SESSION['cart']);
        }
    }

    // Xóa sản phẩm khỏi database nếu người dùng đã đăng nhập
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Tính tổng số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// Tính tổng tiền
$total_price = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $quantity) {
        $product = array_filter($books, function($book) use ($id) {
            return $book['id'] == $id;
        });
        $product = array_shift($product);
        if ($product) {
            $total_price += $product['price'] * $quantity;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Book Store Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#featured">Featured</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Arrivals</a></li>
                    <li class="nav-item"><a class="nav-link" href="#reviews">Reviews</a></li>
                    <li class="nav-item"><a class="nav-link" href="#blog">Blog</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a class="nav-link me-3" href="admin/index.php">Admin</a>
                        <?php endif; ?>
                        <a class="nav-link me-3" href="logout.php">Logout</a>
                    <?php else: ?>
                        <a class="nav-link me-3" href="login.php">Login</a>
                        <a class="nav-link me-3" href="register.php">Register</a>
                    <?php endif; ?>
                </div>
                <div class="cart-container ms-3">
                    <a class="cart-icon" data-bs-toggle="offcanvas" data-bs-target="#cartSidebar" aria-controls="cartSidebar">
                        <i class="bi bi-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Cart Sidebar -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartSidebar" aria-labelledby="cartSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="cartSidebarLabel">Giỏ hàng của tơi</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php if (empty($_SESSION['cart'])): ?>
                <p>Giỏ hàng của bạn đang trống.</p>
            <?php else: ?>
                <div class="cart-items">
                    <?php
                    foreach ($_SESSION['cart'] as $product_id => $quantity):
                        $product = array_filter($books, function($book) use ($product_id) {
                            return $book['id'] == $product_id;
                        });
                        $product = array_shift($product);
                        if ($product):
                    ?>
                        <div class="cart-item d-flex mb-3" data-id="<?php echo $product['id']; ?>">
                            <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="cart-item-image me-3">
                            <div class="cart-item-details flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h6><?php echo $product['name']; ?></h6>
                                    <a href="?remove=<?php echo $product['id']; ?>" class="text-danger remove-item"><i class="bi bi-x"></i></a>
                                </div>
                                <p class="text-muted small">Cỡ: 39, Chống Nhăn, Kháng Khuẩn 85MDH455XNG</p>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-secondary me-2 decrease-quantity" data-id="<?php echo $product['id']; ?>">-</button>
                                    <span class="quantity"><?php echo $quantity; ?></span>
                                    <button class="btn btn-sm btn-outline-secondary ms-2 increase-quantity" data-id="<?php echo $product['id']; ?>">+</button>
                                </div>
                                <p class="mt-2 item-total">Tổng tiền: <?php echo number_format($product['price'] * $quantity, 2); ?>đ</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <hr>
                <div class="cart-summary">
                    <p><strong>Tổng tiền:</strong> <span class="total-price"><?php echo number_format($total_price, 2); ?></span>đ</p>
                    <p><strong>Số sản phẩm:</strong> <span class="total-items"><?php echo $cart_count; ?></span></p>
                    <a href="cart.php" class="btn btn-warning w-100">MUA HÀNG</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
    <script>
        document.querySelectorAll('.increase-quantity, .decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                const action = this.classList.contains('increase-quantity') ? 'increase' : 'decrease';
                const cartItem = this.closest('.cart-item');
                const quantityElement = cartItem.querySelector('.quantity');
                const itemTotalElement = cartItem.querySelector('.item-total');
                const totalPriceElement = document.querySelector('.total-price');
                const totalItemsElement = document.querySelector('.total-items');
                const cartCountElement = document.querySelector('.cart-count');
                const cartItemsContainer = document.querySelector('.cart-items');
                const cartSummary = document.querySelector('.cart-summary');

                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${action}&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.cart_empty) {
                        cartItemsContainer.innerHTML = '<p>Giỏ hàng của bạn đang trống.</p>';
                        cartSummary.style.display = 'none';
                        if (cartCountElement) cartCountElement.remove();
                    } else {
                        if (data.quantity === 0) {
                            cartItem.remove();
                        } else {
                            quantityElement.textContent = data.quantity;
                            itemTotalElement.textContent = `Tổng tiền: ${data.item_total}đ`;
                        }
                        totalPriceElement.textContent = data.total_price;
                        totalItemsElement.textContent = data.cart_count;
                        if (cartCountElement) {
                            cartCountElement.textContent = data.cart_count;
                        } else if (data.cart_count > 0) {
                            const newCount = document.createElement('span');
                            newCount.className = 'cart-count';
                            newCount.textContent = data.cart_count;
                            document.querySelector('.cart-icon').appendChild(newCount);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
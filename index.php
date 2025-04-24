<?php
// Khởi tạo session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['add'])) {
    $product_id = $_GET['add'];
    
    // Kiểm tra nếu giỏ hàng chưa được tạo trong session
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Thêm sản phẩm vào giỏ hàng (tăng số lượng nếu sản phẩm đã có)
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]++;
    } else {
        $_SESSION['cart'][$product_id] = 1;
    }
    
    // Nếu người dùng đã đăng nhập, lưu hoặc cập nhật vào bảng orders
    if (isset($_SESSION['user_id'])) {
        $quantity = $_SESSION['cart'][$product_id];
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $order = $stmt->fetch();

        // Lấy giá sản phẩm để tính total_price
        $product_stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch();
        $total_price = $product ? $product['price'] * $quantity : 0;

        if ($order) {
            // Cập nhật số lượng và tổng tiền
            $stmt = $conn->prepare("UPDATE orders SET quantity = ?, total_price = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $total_price, $_SESSION['user_id'], $product_id]);
        } else {
            // Thêm mới vào orders
            $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price, order_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $total_price]);
        }
    }
    
    // Chuyển hướng lại trang
    header("Location: index.php");
    exit();
}

// Đồng bộ giỏ hàng từ session vào database khi người dùng đăng nhập
if (isset($_SESSION['user_id']) && isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $order = $stmt->fetch();

        // Lấy thông tin sản phẩm để tính total_price
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

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="row align-items-center">
        <div class="col-md-6 hero-content">
            <h1>Welcome to <span class="text-success">Book Store</span></h1>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatibus.</p>
            <p>Lorem ipsum is simply dummy text of the printing and typesetting industry. Lorem ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
            <a href="#featured" class="btn btn-primary">Learn More</a>
        </div>
        <div class="col-md-6 books-shelf">
            <img src="assets/images/table.png" alt="Bookshelf">
        </div>
    </div>
</div>

<!-- Services Section -->
<div class="services-section">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="service-card">
                <i class="bi bi-truck"></i>
                <h5>Fast Delivery</h5>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit.</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="service-card">
                <i class="bi bi-headset"></i>
                <h5>24/7 Services</h5>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit.</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="service-card">
                <i class="bi bi-tag"></i>
                <h5>Best Deal</h5>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit.</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="service-card">
                <i class="bi bi-lock"></i>
                <h5>Secure Payment</h5>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit.</p>
            </div>
        </div>
    </div>
</div>

<!-- About Us Section -->
<div class="about-section" id="about">
    <div class="row align-items-center">
        <div class="col-md-6">
            <img src="assets/images/about.png" alt="About Books">
        </div>
        <div class="col-md-6">
            <h2>About Us</h2>
            <p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Distinctio iusto numquam commodo ea, architecto expedita hic nulla, vitae doloribus eius, inventore officiis ducimus facilis neque? Animi voluptas fugiat voluptatibus dicta! Lorem ipsum dolor sit amet consectetur adipiscing elit. Repellendus dolorem alias a dolorum impedit temporibus eius, asperiores tempore ut voluptatibus odit, necessitatibus possimus deserunt, quae laborum magnam. Error, magni.</p>
            <a href="#" class="btn btn-primary">Learn More</a>
        </div>
    </div>
</div>

<!-- Featured Books Section -->
<div class="featured-section" id="featured">
    <h2>Featured Books</h2>
    <div class="featured-books-row">
        <?php
        // Lấy 10 sách từ cơ sở dữ liệu
        $stmt = $conn->query("SELECT * FROM products LIMIT 10");
        $books = $stmt->fetchAll();
        // Nếu không có sách trong cơ sở dữ liệu, sử dụng dữ liệu mẫu
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
        foreach ($books as $book):
        ?>
            <div class="book-card">
                <img src="assets/images/<?php echo $book['image']; ?>" alt="<?php echo $book['name']; ?>">
                <h5><?php echo $book['name']; ?></h5>
                <p class="category">Featured Books<br>Thriller, Horror, Romance</p>
                <p class="price">$<?php echo number_format($book['price'], 2); ?> <del>$28.60</del></p>
                <a href="?add=<?php echo $book['id']; ?>" class="btn btn-primary">Add to Cart</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Reviews Section -->
<div class="reviews-section" id="reviews">
    <h2>Reviews</h2>
    <div class="row">
        <?php
        // Dữ liệu mẫu cho 4 đánh giá
        $reviews = [
            ['name' => 'John Doe', 'image' => 'review_1.png', 'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Aliquam, libero aut saepe similique neque sequi at. Dolor magnam magni unde vel eligendi maxime! A, rerum provident? Expedita quos iure quae.'],
            ['name' => 'John Doe', 'image' => 'review_2.png', 'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Aliquam, libero aut saepe similique neque sequi at. Dolor magnam magni unde vel eligendi maxime! A, rerum provident? Expedita quos iure quae.'],
            ['name' => 'John Doe', 'image' => 'review_3.png', 'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Aliquam, libero aut saepe similique neque sequi at. Dolor magnam magni unde vel eligendi maxime! A, rerum provident? Expedita quos iure quae.'],
            ['name' => 'John Doe', 'image' => 'review_4.png', 'text' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Aliquam, libero aut saepe similique neque sequi at. Dolor magnam magni unde vel eligendi maxime! A, rerum provident? Expedita quos iure quae.'],
        ];
        foreach ($reviews as $review):
        ?>
            <div class="col-md-3 mb-4">
                <div class="review-card">
                    <span class="quote-left">“</span>
                    <img src="assets/images/<?php echo $review['image']; ?>" alt="<?php echo $review['name']; ?>">
                    <h5><?php echo $review['name']; ?></h5>
                    <p><?php echo $review['text']; ?></p>
                    <div class="stars">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <span class="quote-right">”</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Blog Section -->
<div class="blog-section" id="blog">
    <h2>Our Blog</h2>
    <div class="row">
        <?php
        // Dữ liệu mẫu cho 3 bài viết blog
        $blogs = [
            ['image' => 'blog_1.jpg', 'title' => 'Blogger', 'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam inventore, deleniti itaque illo, similique officiis velit at ut vero recusandae quasi iure, excepturi sunt id libero provident reiciendis veniam cupiditate!'],
            ['image' => 'blog_2.jpg', 'title' => 'Blogger', 'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam inventore, deleniti itaque illo, similique officiis velit at ut vero recusandae quasi iure, excepturi sunt id libero provident reiciendis veniam cupiditate!'],
            ['image' => 'blog_3.jpg', 'title' => 'Blogger', 'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam inventore, deleniti itaque illo, similique officiis velit at ut vero recusandae quasi iure, excepturi sunt id libero provident reiciendis veniam cupiditate!'],
        ];
        foreach ($blogs as $blog):
        ?>
            <div class="col-md-4 mb-4">
                <div class="blog-card">
                    <img src="assets/images/<?php echo $blog['image']; ?>" alt="<?php echo $blog['title']; ?>">
                    <div class="blog-card-body">
                        <h5><?php echo $blog['title']; ?></h5>
                        <p><?php echo $blog['text']; ?></p>
                        <span class="like-icon"><i class="bi bi-heart-fill"></i></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
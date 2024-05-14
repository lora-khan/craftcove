<?php require "helpers.php"; ?>
<?php loadPartial('header'); ?>
<?php
require "database.php";

// Get product details
$product_id = isset($_GET["id"]) ? $_GET["id"] : null;
if (!$product_id || !is_numeric($product_id)) {
    echo "<script>window.location.href='404.php';</script>";
    exit; // Exit to prevent further execution
}
global $conn;
$sql_product = "SELECT * FROM product  WHERE id = ?";
$stmt_product = mysqli_prepare($conn, $sql_product);
mysqli_stmt_bind_param($stmt_product, "i", $product_id);
mysqli_stmt_execute($stmt_product);
$product_result = mysqli_stmt_get_result($stmt_product);
$num_rows = mysqli_num_rows($product_result);
if (!$num_rows > 0) {
    echo "<script>window.location.href='404.php';</script>";
    exit;
}
$product = mysqli_fetch_assoc($product_result);
$stock = $product['stock'];

$categories = mysqli_query($conn, "SELECT DISTINCT c.name as category_name FROM product p JOIN category c ON p.category_id=c.id");
$row_category = mysqli_fetch_assoc($categories);

// Get tags for the product
$sql_tags = "SELECT t.name FROM tag_product tp JOIN tag t ON t.id = tp.tag_id WHERE tp.product_id = ?";
$stmt_tags = mysqli_prepare($conn, $sql_tags);
mysqli_stmt_bind_param($stmt_tags, "i", $product_id);
mysqli_stmt_execute($stmt_tags);
$tags_result = mysqli_stmt_get_result($stmt_tags);

// Add product to cart
if (isset($_POST["cartBtn"])) {
    $quantity = isset($_POST["quantity"]) ? $_POST["quantity"] : 1;
    if ($quantity <= 0) {
        echo '<div id="alert" class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                     viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div class="ms-3 text-sm font-medium">
                    You should order at least one quantity
                </div>
                <button type="button"
                        class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 "
                        aria-label="Close" id="closeAlertBtn">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>';
    } elseif ($quantity > $stock) {
        echo '<div id="alert" class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                     viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div class="ms-3 text-sm font-medium">
                    Quantity exceeds available stock
                </div>
                <button type="button"
                        class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 "
                        aria-label="Close" id="closeAlertBtn">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>';
    } else {
        $user_id = isset($_SESSION["user"]["id"]) ? $_SESSION["user"]["id"] : null;
        if (!$user_id) {
            $_SESSION["login_msg"] = "You are not logged in, please log in first!";
            $uri = $_SERVER['REQUEST_URI'];
            echo "<script>window.location.href='login_view.php?next=$uri'</script>";
            exit();
        }
        $totalPrice = $quantity * $product["price"];
        $sql_insert_cart = "INSERT INTO cart(product_id, user_id, quantity, total_price) VALUES (?, ?, ?, ?)";
        $stmt_insert_cart = mysqli_prepare($conn, $sql_insert_cart);
        mysqli_stmt_bind_param($stmt_insert_cart, "iiid", $product_id, $user_id, $quantity, $totalPrice);
        $result_insert_cart = mysqli_stmt_execute($stmt_insert_cart);

        if ($result_insert_cart) {
            $totalSold = $product["total_sold"] + $quantity;
            $sql_update_sold = "UPDATE product SET total_sold = ? WHERE id = ?";
            $stmt_update_sold = mysqli_prepare($conn, $sql_update_sold);
            mysqli_stmt_bind_param($stmt_update_sold, "ii", $totalSold, $product_id);
            mysqli_stmt_execute($stmt_update_sold);

            // Decrease the stock by the ordered quantity
            $new_stock = $stock - $quantity;
            $sql_update_stock = "UPDATE product SET stock = ? WHERE id = ?";
            $stmt_update_stock = mysqli_prepare($conn, $sql_update_stock);
            mysqli_stmt_bind_param($stmt_update_stock, "ii", $new_stock, $product_id);
            mysqli_stmt_execute($stmt_update_stock);

            $_SESSION["cart_msg"] = "Order placed Successfully!";

            echo "<script>window.location.href='cart_view.php';</script>";
        } else {
            die("Error occurred while adding to cart.");
        }
    }
}
?>
<?php
$row_last_result = [];
if (isset($_SESSION["user"]) && isset($_SESSION["user"]["id"])) {
    $user_id = $_SESSION["user"]["id"];
    $last_result_sql = "SELECT status FROM cart where user_id=$user_id ORDER BY created_at DESC LIMIT 1";
    $result_last_result = mysqli_query($conn, $last_result_sql);
    if (!$result_last_result) {
        die("Error: " . mysqli_error($conn)); // Output any error if query fails
    }
    if (mysqli_num_rows($result_last_result) > 0) {
        $row_last_result = mysqli_fetch_assoc($result_last_result);
    } else {
        $row_last_result = ['status' => 'complete'];
        //        echo "No rows found in the cart table for user ID: $user_id";
    }
    //    var_dump($row_last_result); // Debugging statement to check contents of $row_last_result
} else {
    //    echo "User session or user ID is not set.";
    $row_last_result = ['status' => 'complete'];
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_btn'])) {
    $comment = $_POST['comment'];
    if (!empty($comment)) {
        $comment_sql = "INSERT INTO review(product_id, user_id, comment, created_at) VALUES(?, ?, ?, NOW())";
        $stmt_comment = mysqli_prepare($conn, $comment_sql);
        mysqli_stmt_bind_param($stmt_comment, "iis", $product_id, $user_id, $comment);
        $result_comment = mysqli_stmt_execute($stmt_comment);
        $_SESSION['comment_msg'] = "Comment Added Successfully!";
    }
}
?>


<?php
$total_comment_sql = "SELECT COUNT(*) as total_comment
FROM review r
         JOIN user_ac u ON r.user_id = u.id
where product_id = $product_id";
$result_comment_total = mysqli_query($conn, $total_comment_sql);
$row_comment_total = mysqli_fetch_assoc($result_comment_total);
?>


<?php
$comment_data_sql = "SELECT name, comment, profile_img, r.created_at as created_at
FROM review r
         JOIN user_ac u ON r.user_id = u.id
where product_id = $product_id order by created_at DESC LIMIT 3
";
$result_comment_data = mysqli_query($conn, $comment_data_sql);
?>


<?php if (!empty($_SESSION['login_msg'])) : ?>
    <?php echo success_message($_SESSION['login_msg']) ?>
    <?php unset($_SESSION['login_msg']) ?>
<?php endif; ?>

<div class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-4xl w-full bg-white p-8 rounded-lg shadow-lg">
        <div class="flex flex-col lg:flex-row">
            <div class="w-full lg:w-1/2 flex justify-center p-2">
                <?php if ($product['img_url']) : ?>
                    <img class="w-full max-h-72 object-cover object-contain rounded-xl shadow-lg hover:scale-105 duration-300" src="<?= $product['img_url'] ?>" alt="Product Image">
                <?php else : ?>
                    <img class="w-full max-h-96 object-contain rounded-lg shadow-lg" src="https://via.placeholder.com/400x300" alt="Product Image">
                <?php endif; ?>
            </div>
            <div class="w-full lg:w-1/2 lg:ml-8 mt-6 lg:mt-0">
                <h2 class="text-3xl font-bold mb-4"><?= $product['name'] ?></h2>
                <div class="flex flex-wrap mb-4">
                    <?php while ($tag = mysqli_fetch_assoc($tags_result)) : ?>
                        <div class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full mr-2 mb-2"><?= $tag['name'] ?></div>
                    <?php endwhile; ?>
                </div>
                <div class="flex justify-between px-3  items-center mb-4">

                    <p class="text-md"><span class="font-semibold">Category:</span> <?= $row_category['category_name'] ?></p>

                    <p class="text-2xl text-orange-600 font-semibold ">$<?= $product['price'] ?></p>
                </div>
                <form action="product_view.php?id=<?= $product_id ?>" method="post" class="relative">
                    <div class="flex items-center justify-between px-3 mb-4">
                        <div>
                            <label for="quantity" class="mr-2">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" class="w-16 focus:outline-none text-center border rounded border-orange-300 focus:border-orange-500 focus:ring focus:ring-orange-200 focus:ring-opacity-50">
                        </div>
                        <?php if ($stock >= 10) : ?>
                            <div class="flex flex-items space-x-2 group items-center">
                                <div class="w-3 h-3 bg-green-400 rounded-full group-hover:animate-ping"></div>
                                <div class="text-sm"><span class="mr-1">Stock:</span> <?= $stock ?></div>
                            </div>
                        <?php else : ?>
                            <div class="flex flex-items space-x-2 group items-center">
                                <div class="w-3 h-3 bg-red-400 rounded-full group-hover:animate-ping"></div>
                                <div class="text-sm"><span class="mr-1">Stock:</span> <?= $stock ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($stock <= 0) : ?>
                        <button class="w-full bg-orange-300 text-white font-semibold py-2 rounded hover:bg-orange-100 focus:outline-none focus:bg-orange-100 hover:text-slate-300" disabled name="cartBtn">
                            Order
                        </button>
                    <?php elseif ($row_last_result && ($row_last_result['status'] == 'pending') || ($row_last_result['status'] == 'process')) : ?>
                        <button class="w-full bg-orange-300 text-white font-semibold py-2 rounded hover:bg-orange-100 focus:outline-none focus:bg-orange-100 hover:text-slate-300" disabled name="cartBtn">
                            Order
                        </button>
                        <p class="text-center text-sm text-red-500 py-2 bg-red-50 my-2 rounded-lg">Don't leave your
                            order hanging! Pay now and let's keep the virtual shelves busy!</p>
                    <?php else : ?>
                        <button class="w-full bg-orange-500 text-white font-semibold py-2 rounded hover:bg-orange-600 focus:outline-none focus:bg-orange-600" name="cartBtn">
                            Order
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <div class="mt-8">
            <h3 class="text-xl font-semibold mb-4">Description</h3>
            <p class="text-gray-700"><?= $product['description'] ?></p>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['comment_msg'])) : ?>
    <?php echo success_message($_SESSION['comment_msg']) ?>
    <?php unset($_SESSION['comment_msg']) ?>
<?php endif; ?>

<section class="pt-8 antialiased">
    <div class="max-w-2xl mx-auto px-4">
        <div class="mb-8">
            <h2 class="text-lg lg:text-2xl font-bold text-gray-900">Discussion
                (<?= $row_comment_total['total_comment'] ?>)</h2>
        </div>
        <form action="product_view.php?id=<?= $product_id ?>" method="post">
            <div class="mb-2">
                <label for="comment" class="sr-only">Your comment</label>
                <textarea id="comment" rows="6" name="comment" class="w-full px-4 py-2 text-sm text-gray-900 bg-white rounded-lg border border-gray-200 focus:ring-0 focus:outline-none" placeholder="Write a comment..." required></textarea>
            </div>
            <button type="submit" name="comment_btn" class="inline-flex  items-center px-4 py-2.5 text-xs font-medium text-white bg-orange-500 rounded-lg focus:ring-4 focus:ring-orange-200 hover:bg-orange-700 mb-8">
                Post comment
            </button>
        </form>
        <?php while ($row = mysqli_fetch_assoc($result_comment_data)) : ?>
            <article class="pb-6 text-base bg-white rounded-lg mb-4">
                <footer class="flex justify-between items-center mb-2">
                    <div class="flex items-center">
                        <p class="inline-flex items-center mr-3 text-sm text-gray-900 font-semibold">
                            <?php if ($row['profile_img']) : ?>
                                <img class="mr-2 w-6 h-6 rounded-full" src="<?= $row['profile_img'] ?>" alt="<?= $row['name'] ?>">
                            <?php else : ?>
                                <img class="mr-2 w-6 h-6 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-2.jpg" alt="Michael Gough">
                            <?php endif; ?>
                            <?= $row['name'] ?>
                        </p>
                        <p class="text-sm text-gray-600">
                            <time pubdate datetime="<?= $row['created_at'] ?>" title="<?= $row['created_at'] ?>"><?php echo getTimeElapsedString($row['created_at']) ?>
                            </time>
                        </p>
                    </div>
                </footer>
                <p class="text-gray-500"><?= $row['comment'] ?></p>
            </article>
        <?php endwhile; ?>
    </div>
</section>


<div class="mt-8 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <h3 class="text-2xl font-semibold my-5 text-center underline underline-offset-8 text-orange-500 uppercase">Similar
        Products</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <?php
        $sql_similar_products = "SELECT p.* FROM product p
                                INNER JOIN tag_product tp ON p.id = tp.product_id
                                INNER JOIN tag t ON tp.tag_id = t.id
                                WHERE t.name IN (
                                    SELECT t2.name FROM tag_product tp2
                                    INNER JOIN tag t2 ON tp2.tag_id = t2.id
                                    WHERE tp2.product_id = ?
                                )
                                AND p.id != ?
                                GROUP BY p.id
                                LIMIT 3";
        $stmt_similar_products = mysqli_prepare($conn, $sql_similar_products);
        mysqli_stmt_bind_param($stmt_similar_products, "ii", $product_id, $product_id);
        mysqli_stmt_execute($stmt_similar_products);
        $result_similar_products = mysqli_stmt_get_result($stmt_similar_products);
        if ($result_similar_products && mysqli_num_rows($result_similar_products) > 0) :
            while ($row_similar = mysqli_fetch_assoc($result_similar_products)) : ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col justify-between">
                    <?php if ($row_similar['img_url']) : ?>
                        <div class="p-3">
                            <img class="w-full h-48 object-cover object-center rounded-md hover:scale-105 duration-300" src="<?= $row_similar['img_url'] ?>" alt="Product Image">
                        </div>
                    <?php else : ?>
                        <div class="p-3">
                            <img class="w-full h-48 object-cover object-center rounded-md" src="default.jpeg" alt="Product Image">
                        </div>
                    <?php endif; ?>
                    <div class="px-4">
                        <h4 class="text-lg font-semibold mb-2 "><?= $row_similar['name'] ?></h4>
                        <p class="text-gray-600 mb-2"><?= $row_similar['description'] ?></p>
                    </div>
                    <div class="px-4 pb-3">
                        <div class="flex justify-between items-center">
                            <a href="product_view.php?id=<?= $row_similar['id'] ?>" class="mt-2 block bg-orange-500 text-white font-semibold py-1 px-4 rounded hover:bg-orange-600">View
                                Product</a>
                            <p class="text-orange-600 font-semibold text-lg">$<?= $row_similar['price'] ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile;
        else : ?>
    </div> <!-- Closing grid div -->
    <div class="mt-8 mx-auto text-center">
        <p class="text-lg text-gray-600">There are no similar products added yet.</p>
    </div>
<?php endif; ?>
</div>


<script>
    const closeAlertBtn = document.querySelector('#closeAlertBtn');
    const alert = document.querySelector('#alert');
    closeAlertBtn.addEventListener('click', () => {
        alert.style.display = 'none';
    });
</script>

<?php loadPartial('footer'); ?>
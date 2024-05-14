<?php
require 'helpers.php';
$conn = mysqli_connect("localhost", "root", "", "craftcove2");
loadPartial('header');

if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $cart_order_sql = "SELECT total_price as price, p.name as product_name, description, quantity,coupon_used FROM cart c
         JOIN product p on c.product_id = p.id WHERE status = 'pending' and user_id=$user_id order by c.created_at DESC LIMIT 1";
    $results = $conn->query($cart_order_sql);
    $num = mysqli_num_rows($results);

    if ($num > 0) {
        $row = $results->fetch_assoc();
        $_SESSION['price'] = (float)$row['price'];
        ?>
        <?php if (!empty($_SESSION['cart_msg'])) : ?>
            <?php echo success_message($_SESSION['cart_msg']) ?>
            <?php unset($_SESSION['cart_msg']) ?>
        <?php endif; ?>
        <?php if (isset($_POST['coupon_btn']) && $_SERVER["REQUEST_METHOD"] == "POST") {
            $coupon_code = $_POST['coupon_code'];
            $already_coupon_applied = $row['coupon_used'];
            if ($already_coupon_applied) {
                $_SESSION['already_coupon_applied'] = "You have already redeemed a coupon, so you cannot apply multiple coupon codes in your cart.";
                echo "<script>window.location.href='cart_view.php';</script>";
                exit;
            }

            $coupon_sql = "SELECT cc.id as id, name, discount, applied FROM applied_coupon ac
                JOIN coupon_code cc ON ac.coupon_id = cc.id
                WHERE applied = 0 AND user_id = ? AND name=?";

            $stmt_coupon = $conn->prepare($coupon_sql);
            $stmt_coupon->bind_param("is", $user_id, $coupon_code);
            $stmt_coupon->execute();
            $results_coupon = $stmt_coupon->get_result();

            if ($results_coupon->num_rows > 0) {
                $row_coupon = $results_coupon->fetch_assoc();
                $already_coupon_applied = true;
                $discount = $row_coupon['discount'];
                $new_price = $row['price'] - ($row['price'] * $discount / 100); // Corrected calculation
                $_SESSION['price'] = number_format($new_price, 2);
                $row['price'] = number_format($new_price, 2);
                $update_price_sql = "UPDATE cart SET total_price = ?, coupon_used = ? WHERE user_id = ? AND status = 'pending'";
                $one = 1;
                $stmt_update_price = $conn->prepare($update_price_sql);
                $stmt_update_price->bind_param("dii", $_SESSION['price'], $one, $user_id);

                $stmt_update_price->execute();

                $sql_update_coupon = "UPDATE applied_coupon SET applied = 1 WHERE user_id = ? AND coupon_id=? AND applied = 0";
                $stmt_update_coupon = $conn->prepare($sql_update_coupon);
                $stmt_update_coupon->bind_param("ii", $user_id, $row_coupon['id']);
                $stmt_update_coupon->execute();
                if ($stmt_update_coupon) {
                    $_SESSION['coupon_code'] = "'" . $coupon_code . "'" . " Coupon Applied successfully! you got " . $discount . " % discount!";
                }
            } else {
                $_SESSION['coupon_error'] = "Invalid coupon code!";
            }
        } ?>

        <div class="container mx-auto my-8">
            <h2 class="text-2xl font-bold mb-4">Checkout</h2>
            <div class="bg-white p-4 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-semibold mb-4">Order Summary</h3>

                <div class="flex justify-between items-center border-b border-gray-300 py-2">
                    <div>
                        <h4 class="text-gray-800 font-semibold"><?= $row['product_name'] ?></h4>
                        <p class="text-gray-600"><?= $row['description'] ?></p>
                    </div>
                    <div class="text-gray-800">
                        <span class="mr-2">$<?= $row['price'] ?></span>
                        <span class="text-sm text-gray-500">(Quantity: <?= $row['quantity'] ?>)</span>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <h4 class="text-lg font-semibold">Total:</h4>
                    <div class="text-lg font-semibold">$<?= $row['price'] ?></div>
                </div>
            </div>
            <div class="bg-gray-100  p-6 rounded-lg shadow-lg mb-8 ">

                <form method="POST" action="">

                    <div class="mb-4 inline">
                        <input type="text" id="coupon" name="coupon_code"
                               class="inline w-[75%] px-4 py-2 border border-orange-300 focus:outline-none rounded-lg focus:ring focus:ring-orange-300 focus:border-orange-400"
                               placeholder="Enter your coupon code">
                    </div>


                    <div class="text-center inline-block w-[12.5%] ml-[12%]">

                        <button class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 focus:outline-none focus:ring focus:ring-orange-500 focus:border-orange-500 w-full"
                                name="coupon_btn">
                            Apply Coupon
                        </button>


                    </div>
                </form>

                <?php if (!empty($_SESSION['coupon_code'])): ?>
                    <div class="mt-4 text-green-500 text-center w-[75%]">
                        <?= $_SESSION['coupon_code'] ?>
                        <?php unset($_SESSION['coupon_code']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['already_coupon_applied'])): ?>
                    <div class="mt-4 text-yellow-600 text-center w-[75%]">
                        <?= $_SESSION['already_coupon_applied'] ?>
                        <?php unset($_SESSION['already_coupon_applied']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['coupon_error'])): ?>
                    <div class="mt-4 text-red-500 text-center w-[75%]">
                        <?= $_SESSION['coupon_error'] ?>
                        <?php unset($_SESSION['coupon_error']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex justify-end mr-10">
                <?= loadPartial('payment') ?>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="bg-gray-100 min-h-screen flex justify-center items-center">
            <div class="max-w-md p-8 bg-white rounded-lg shadow-md text-center">
                <h2 class="text-red-500 text-3xl font-semibold mb-6">No Orders Yet</h2>
                <p class="text-gray-700 mb-6">You haven't placed any orders yet.</p>
                <a href="index.php"
                   class="bg-red-500 text-white py-2 px-6 rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 transition-colors duration-300">
                    Start Shopping
                </a>
            </div>
        </div>
        <?php
    }
}

?>


<?php if (!isset($_SESSION['user'])) : ?>
    <div class="bg-gray-100 min-h-screen flex justify-center items-center">
        <div class="max-w-md p-8 bg-white rounded-lg shadow-md text-center">
            <h2 class="text-red-500 text-3xl font-semibold mb-6">No Orders Yet</h2>
            <p class="text-gray-700 mb-6">You haven't placed any orders yet.</p>
            <a href="index.php"
               class="bg-red-500 text-white py-2 px-6 rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 transition-colors duration-300">
                Start Shopping
            </a>
        </div>
    </div>
<?php endif; ?>

    <script>
        const closeAlertBtn = document.querySelector('#closeAlertBtn');
        const alert = document.querySelector('#alert');
        closeAlertBtn.addEventListener('click', () => {
            alert.style.display = 'none';
        });
    </script>
<?php loadPartial('footer');
?>
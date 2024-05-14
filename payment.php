<?php
require "config.php";
require "database.php";
global $conn;
$user_id = $_SESSION['user']['id'];
$cart_order_sql = "SELECT total_price as price, p.name as product_name, description, quantity FROM cart c
         JOIN product p on c.product_id = p.id WHERE status = 'pending' and user_id=$user_id order by c.created_at DESC LIMIT 1";
$results = $conn->query($cart_order_sql);
$row = $results->fetch_assoc();
if (isset($_SESSION['price'])) {
    $amount = $_SESSION['price'] * 100;
} else {
    $amount = $row['price'] * 100;
}
$_SESSION['description'] = $row['description'];
?>

<form action="payment_submit.php" method="post">
    <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
            data-key="<?= STRIPE_PUBLISH_KEY ?>"
            data-amount="<?= $amount ?>"
            data-name="Securing your transaction..."
            data-description="CraftCove Ecommerce Website payment"
            data-image="payment_logo.png"
            data-email="<?= $_SESSION['user']['email'] ?>"
    ></script>

</form>

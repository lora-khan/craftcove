<?php
require "helpers.php";
require "database.php";

global $conn;
admin_authentication();
// Check if tag-product ID is provided and valid
$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id || !is_numeric($id)) {
    echo "<script>window.location.href='admin_tag_product.php';</script>";
    exit; // Exit to prevent further execution
}

// Fetch the tag-product data
$sql = "SELECT t.name as tag_name, p.name as product_name FROM tag_product tp JOIN tag t ON t.id=tp.tag_id JOIN product p ON p.id=tp.product_id WHERE tp.id = '$id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<script>alert('Tag-Product not found');</script>";
    echo "<script>window.location.href='admin_tag_product.php';</script>";
    exit; // Exit if tag-product not found
}
$row = $result->fetch_assoc();

if (isset($_POST['confirm'])) {
    try {
        // Delete the tag-product
        $sql_delete = "DELETE FROM tag_product WHERE id = $id";
        if ($conn->query($sql_delete) === TRUE) {
            $_SESSION['product_tag_delete_msg'] = "Delete product tag successful!";
            echo "<script>window.location.href='admin_tag_product.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error deleting tag-product: " . $conn->error . "');</script>";
        }
    } catch (mysqli_sql_exception $e) {
        // Check if the error message contains information about foreign key constraint
        if (strpos($e->getMessage(), 'foreign key constraint')) {
            echo "<script>alert('Error deleting Tag-Product: Cannot delete or update a parent row due to a foreign key constraint');</script>";
        } else {
            echo "<script>alert('Error deleting Tag-Product: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<?php loadPartial("admin_header"); ?>

<div class="w-[65%] mt-8 mx-auto p-12 bg-white border border-gray-200 rounded-3xl shadow dark:bg-gray-800 dark:border-gray-700">
    <h5 class="mb-2 text-2xl font-semibold font-mono tracking-tight text-gray-900 dark:text-white text-center">Are you
        sure you want to delete
        "<?= htmlspecialchars($row['tag_name']) ?>" Tag From "<?= htmlspecialchars($row['product_name']) ?>"
        product?</h5>
    <form action="admin_product_tag_delete.php?id=<?= $id ?>" method="post" class="flex justify-between mt-8">
        <a href="admin_tag_product.php"
           class="inline-flex space-x-2 items-center px-3 py-2 text-sm font-medium text-center text-white bg-gray-700 rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
            <i class="fa-solid fa-arrow-left"></i>
            <span class="pr-3">Back</span>
        </a>
        <button type="submit" name="confirm"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
            <span class="px-2">Confirm</span>
        </button>
    </form>
</div>

<?php loadPartial("admin_footer"); ?>

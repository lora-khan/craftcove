<?php
require "helpers.php";
require "database.php";

global $conn;
admin_authentication();
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize the input
    $name = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';

    // Validate input (you can add more validation as needed)
    if (empty($name)) {
        echo "<script>alert('Please enter a name for the category');</script>";
        echo "<script>window.location.href='admin_category.php';</script>";
        exit;
    }

    $check_query = "SELECT COUNT(*) AS count FROM category WHERE name = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $_SESSION['category_add_error'] = "Category already exists";
        echo "<script>window.location.href='admin_category_add.php';</script>";
        exit;
    }

    // Insert the new category into the database
    $sql = "INSERT INTO category (name) VALUES ('$name')";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['category_add_msg'] = "Category added successfully";
        echo " <script>window.location.href = 'admin_category.php';</script> ";
        exit;
    } else {
        echo "<script > alert('Error adding category: " . $conn->error . "');</script > ";
    }
}
?>

<?php loadPartial('admin_header'); ?>

<?php if (!empty($_SESSION['category_add_error'])) : ?>
    <?php echo admin_warning_message($_SESSION['category_add_error']) ?>
    <?php unset($_SESSION['category_add_error']) ?>
<?php endif; ?>

<div class="flex min-h-screen items-center mt-4 flex-col">
    <h2 class="text-slate-300 text-xl uppercase underline underline-offset-8 mb-8">ADD a new category</h2>
    <form action="admin_category_add.php" class="w-[65%] mx-auto bg-slate-700 px-10 py-8 rounded-2xl" method="post">
        <div class="relative z-0 w-full mb-5 group">
            <input type="text" name="name" id="name" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
            <label for="name" class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category
                name</label>
        </div>
        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
            Submit
        </button>
    </form>
</div>

<?php loadPartial('admin_footer'); ?>
<?php
require_once __DIR__ . '/../includes/functions.php';

// Read table number from query string
$tableNumber = isset($_GET['table']) ? (int)$_GET['table'] : 0;
if ($tableNumber <= 0) {
    echo 'Invalid table number';
    exit;
}

// Fetch menu data
$menuData = getCategoriesWithItems();

// Encode as JSON for embedding
$menuJson = json_encode($menuData, JSON_UNESCAPED_UNICODE);

?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สั่งอาหาร - โต๊ะ <?php echo $tableNumber; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script>
        window.menuData = <?php echo $menuJson; ?>;
        window.tableNumber = <?php echo $tableNumber; ?>;
    </script>
</head>
<body>
    <div class="container">
        <h2>โต๊ะ #<?php echo $tableNumber; ?></h2>
        <button id="call-staff-btn" class="btn btn-outline" style="float:right;margin-top:-2.5rem;">เรียกพนักงาน</button>
        <div id="menu-container"></div>
    </div>
    <div class="fixed-bottom">
        <div>รายการ: <span id="cart-count">0</span> • รวม: ฿<span id="cart-total">0.00</span></div>
        <button id="checkout-btn" class="btn btn-primary" disabled>ส่งออเดอร์</button>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
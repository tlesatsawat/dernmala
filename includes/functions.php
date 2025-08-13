<?php
require_once __DIR__ . '/db.php';

/**
 * Retrieve menu categories with their active items.
 *
 * @return array
 */
function getCategoriesWithItems(): array
{
    $pdo = getPDO();
    $sql = 'SELECT c.id as category_id, c.name as category_name, m.id as item_id, m.name as item_name, m.description, m.price, m.photo
            FROM categories c
            JOIN menu_items m ON c.id = m.category_id
            WHERE m.is_active = 1
            ORDER BY c.id, m.name';
    $stmt = $pdo->query($sql);
    $categories = [];
    while ($row = $stmt->fetch()) {
        $cid = $row['category_id'];
        if (!isset($categories[$cid])) {
            $categories[$cid] = [
                'id' => $cid,
                'name' => $row['category_name'],
                'items' => []
            ];
        }
        $categories[$cid]['items'][] = [
            'id' => (int)$row['item_id'],
            'name' => $row['item_name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'photo' => $row['photo']
        ];
    }
    return array_values($categories);
}

/**
 * Create a new order with items.
 *
 * @param int   $tableNumber
 * @param array $items Each item: ['id' => menu_item_id, 'qty' => qty, 'mods' => array]
 * @return int Order ID
 */
function createOrder(int $tableNumber, array $items): int
{
    $pdo = getPDO();
    // Get table id
    $stmt = $pdo->prepare('SELECT id, status FROM tables WHERE number = ?');
    $stmt->execute([$tableNumber]);
    $table = $stmt->fetch();
    if (!$table) {
        throw new Exception('Table not found');
    }
    $tableId = (int)$table['id'];
    // Insert order
    $stmt = $pdo->prepare('INSERT INTO orders (table_id, status) VALUES (?, "OPEN")');
    $stmt->execute([$tableId]);
    $orderId = (int)$pdo->lastInsertId();
    $total = 0;
    foreach ($items as $item) {
        $menuId = (int)$item['id'];
        $qty    = (int)$item['qty'];
        $mods   = isset($item['mods']) ? json_encode($item['mods'], JSON_UNESCAPED_UNICODE) : null;
        // Fetch price
        $pstmt = $pdo->prepare('SELECT price FROM menu_items WHERE id=?');
        $pstmt->execute([$menuId]);
        $priceRow = $pstmt->fetch();
        if (!$priceRow) {
            continue;
        }
        $price = (float)$priceRow['price'];
        $lineTotal = $price * $qty;
        $total += $lineTotal;
        // Insert item
        $istmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, qty, price, modifications) VALUES (?,?,?,?,?)');
        $istmt->execute([$orderId, $menuId, $qty, $price, $mods]);
    }
    // Update order total
    $pdo->prepare('UPDATE orders SET total=? WHERE id=?')->execute([$total, $orderId]);
    // Update table status to ORDERING
    $pdo->prepare('UPDATE tables SET status="ORDERING" WHERE id=?')->execute([$tableId]);
    // Add notification: new order
    addNotification('NEW_ORDER', $tableId, 'มีออเดอร์ใหม่');
    return $orderId;
}

/**
 * Update order status and table status when paid.
 *
 * @param int $orderId
 */
function markOrderPaid(int $orderId): void
{
    $pdo = getPDO();
    $pdo->prepare('UPDATE orders SET status="PAID" WHERE id=?')->execute([$orderId]);
    // Get table id
    $stmt = $pdo->prepare('SELECT table_id FROM orders WHERE id=?');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    if ($row) {
        $tableId = (int)$row['table_id'];
        $pdo->prepare('UPDATE tables SET status="PAID" WHERE id=?')->execute([$tableId]);
        addNotification('PAID', $tableId, 'ชำระเงินแล้ว');
    }
}

/**
 * Get open order for a table along with items.
 *
 * @param int $tableNumber
 * @return array|null
 */
function getOpenOrderByTable(int $tableNumber): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT o.id, o.total, o.status
                            FROM orders o
                            JOIN tables t ON o.table_id = t.id
                            WHERE t.number = ? AND o.status = "OPEN"
                            ORDER BY o.created_at DESC LIMIT 1');
    $stmt->execute([$tableNumber]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }
    // Fetch items
    $istmt = $pdo->prepare('SELECT oi.id, oi.menu_item_id, oi.qty, oi.price, oi.modifications, m.name
                             FROM order_items oi
                             JOIN menu_items m ON oi.menu_item_id = m.id
                             WHERE oi.order_id = ?');
    $istmt->execute([$order['id']]);
    $items = [];
    while ($row = $istmt->fetch()) {
        $mods = $row['modifications'] ? json_decode($row['modifications'], true) : [];
        $items[] = [
            'id' => (int)$row['id'],
            'menu_item_id' => (int)$row['menu_item_id'],
            'name' => $row['name'],
            'qty' => (int)$row['qty'],
            'price' => (float)$row['price'],
            'modifications' => $mods
        ];
    }
    return [
        'order_id' => (int)$order['id'],
        'total' => (float)$order['total'],
        'status' => $order['status'],
        'items' => $items
    ];
}

/**
 * Get statuses of all tables with optional open order summary.
 *
 * @return array
 */
function getAllTableStatuses(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, number, status FROM tables ORDER BY number');
    $tables = [];
    while ($row = $stmt->fetch()) {
        $tables[] = [
            'id' => (int)$row['id'],
            'number' => (int)$row['number'],
            'status' => $row['status']
        ];
    }
    return $tables;
}

/**
 * Retrieve a list of all menu categories.
 *
 * @return array
 */
function getCategories(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, name FROM categories ORDER BY id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retrieve all menu items with their category name.
 *
 * @return array
 */
function getMenuItemsAll(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT m.id, m.category_id, c.name as category_name, m.name, m.description, m.price, m.photo, m.is_active
                          FROM menu_items m
                          JOIN categories c ON m.category_id = c.id
                          ORDER BY m.id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a menu item by ID.
 *
 * @param int $id
 * @return array|null
 */
function getMenuItemById(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT m.id, m.category_id, m.name, m.description, m.price, m.photo, m.is_active
                            FROM menu_items m WHERE m.id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Add a new category.
 *
 * @param string $name
 */
function addCategory(string $name): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
    $stmt->execute([$name]);
}

/**
 * Update an existing category.
 *
 * @param int $id
 * @param string $name
 */
function updateCategory(int $id, string $name): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE categories SET name=? WHERE id=?');
    $stmt->execute([$name, $id]);
}

/**
 * Delete a category by ID.
 * Deleting a category will also delete its menu items due to foreign key cascade.
 *
 * @param int $id
 */
function deleteCategory(int $id): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id=?');
    $stmt->execute([$id]);
}

/**
 * Add a new menu item.
 *
 * @param int    $categoryId
 * @param string $name
 * @param string $description
 * @param float  $price
 * @param string $photo
 */
function addMenuItem(int $categoryId, string $name, string $description, float $price, string $photo): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO menu_items (category_id, name, description, price, photo) VALUES (?,?,?,?,?)');
    $stmt->execute([$categoryId, $name, $description, $price, $photo]);
}

/**
 * Update a menu item.
 *
 * @param int    $id
 * @param int    $categoryId
 * @param string $name
 * @param string $description
 * @param float  $price
 * @param string $photo
 * @param int    $isActive
 */
function updateMenuItem(int $id, int $categoryId, string $name, string $description, float $price, string $photo, int $isActive = 1): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE menu_items SET category_id=?, name=?, description=?, price=?, photo=?, is_active=? WHERE id=?');
    $stmt->execute([$categoryId, $name, $description, $price, $photo, $isActive, $id]);
}

/**
 * Delete a menu item by ID.
 *
 * @param int $id
 */
function deleteMenuItem(int $id): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM menu_items WHERE id=?');
    $stmt->execute([$id]);
}

/**
 * Retrieve all settings as key/value pairs.
 *
 * @return array
 */
function getSettings(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT name, value FROM settings');
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['name']] = $row['value'];
    }
    return $settings;
}

/**
 * Update or insert a setting.
 *
 * @param string $name
 * @param string $value
 */
function updateSetting(string $name, string $value): void
{
    $pdo = getPDO();
    // Try update first
    $stmt = $pdo->prepare('UPDATE settings SET value=? WHERE name=?');
    $stmt->execute([$value, $name]);
    if ($stmt->rowCount() === 0) {
        // Insert new setting
        $stmt = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)');
        $stmt->execute([$name, $value]);
    }
}

/**
 * Record a call staff event.
 *
 * @param int $tableNumber
 */
function callStaff(int $tableNumber): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM tables WHERE number=?');
    $stmt->execute([$tableNumber]);
    $table = $stmt->fetch();
    if ($table) {
        $tableId = (int)$table['id'];
        $pdo->prepare('UPDATE tables SET status="NEED_STAFF" WHERE id=?')->execute([$tableId]);
        addNotification('CALL_STAFF', $tableId, 'ลูกค้าเรียกพนักงาน');
    }
}

/**
 * Add a notification.
 *
 * @param string $type
 * @param int    $tableId
 * @param string $message
 */
function addNotification(string $type, int $tableId, string $message): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO notifications (type, table_id, message) VALUES (?,?,?)');
    $stmt->execute([$type, $tableId, $message]);
}

/**
 * Fetch notifications since a given ID. Used for polling by main display and staff UI.
 *
 * @param int $lastId
 * @return array
 */
function fetchNotifications(int $lastId = 0): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT n.id, n.type, n.table_id, n.message, n.created_at, t.number as table_number
                            FROM notifications n
                            LEFT JOIN tables t ON n.table_id = t.id
                            WHERE n.id > ?
                            ORDER BY n.created_at ASC');
    $stmt->execute([$lastId]);
    $notifs = [];
    while ($row = $stmt->fetch()) {
        $notifs[] = [
            'id' => (int)$row['id'],
            'type' => $row['type'],
            'table_id' => (int)$row['table_id'],
            'table_number' => isset($row['table_number']) ? (int)$row['table_number'] : null,
            'message' => $row['message'],
            'created_at' => $row['created_at'],
        ];
    }
    return $notifs;
}
<?php
// Admin back office page for POS system
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'add_category':
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') {
                addCategory($name);
            }
            break;
        case 'update_category':
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($id > 0 && $name !== '') {
                updateCategory($id, $name);
            }
            break;
        case 'delete_category':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                deleteCategory($id);
            }
            break;
        case 'add_item':
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            $photo = trim($_POST['photo'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($catId > 0 && $name !== '' && $price > 0) {
                addMenuItem($catId, $name, $desc, $price, $photo);
            }
            break;
        case 'update_item':
            $id = (int)($_POST['id'] ?? 0);
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            $photo = trim($_POST['photo'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($id > 0 && $catId > 0 && $name !== '' && $price > 0) {
                updateMenuItem($id, $catId, $name, $desc, $price, $photo, $isActive);
            }
            break;
        case 'delete_item':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                deleteMenuItem($id);
            }
            break;
        case 'update_settings':
            // Update settings (Beam keys). We don't currently read these settings in config,
            // but they can be stored for reference.
            $beamKey = trim($_POST['beam_api_key'] ?? '');
            $webhookSecret = trim($_POST['beam_webhook_secret'] ?? '');
            if ($beamKey !== '') {
                updateSetting('BEAM_API_KEY', $beamKey);
            }
            if ($webhookSecret !== '') {
                updateSetting('BEAM_WEBHOOK_SECRET', $webhookSecret);
            }
            break;
    }
    // Redirect to avoid form resubmission
    header('Location: admin.php');
    exit;
}

// Fetch data for display
$categories = getCategories();
$items = getMenuItemsAll();
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หลังร้าน | POS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
    }
    th, td {
      padding: 0.5rem;
      border: 1px solid var(--line);
    }
    th {
      background: var(--card-bg);
    }
    input[type="text"], input[type="number"], select {
      width: 100%;
      box-sizing: border-box;
    }
    </style>
</head>
<body>
    <div class="container">
        <h2>หลังร้าน</h2>
        <!-- Categories Management -->
        <section>
            <h3>หมวดหมู่</h3>
            <table>
                <tr><th>ID</th><th>ชื่อหมวดหมู่</th><th>การกระทำ</th></tr>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <form method="post" onsubmit="return confirm('ยืนยัน?');">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <td><?= $cat['id'] ?></td>
                        <td><input type="text" name="name" value="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"></td>
                        <td>
                            <button type="submit" name="action" value="update_category" class="btn btn-primary">ปรับปรุง</button>
                            <button type="submit" name="action" value="delete_category" class="btn btn-outline" onclick="return confirm('ลบหมวดหมู่นี้?');">ลบ</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
                <!-- Add new category -->
                <tr>
                    <form method="post">
                        <input type="hidden" name="action" value="add_category">
                        <td>ใหม่</td>
                        <td><input type="text" name="name" placeholder="ชื่อหมวดหมู่"></td>
                        <td><button type="submit" class="btn btn-primary">เพิ่ม</button></td>
                    </form>
                </tr>
            </table>
        </section>
        <!-- Menu Items Management -->
        <section>
            <h3>เมนู</h3>
            <table>
                <tr><th>ID</th><th>หมวดหมู่</th><th>ชื่อเมนู</th><th>ราคา</th><th>คำอธิบาย</th><th>รูป</th><th>แสดง?</th><th>การกระทำ</th></tr>
                <?php foreach ($items as $item): ?>
                <tr>
                    <form method="post" onsubmit="return confirm('ยืนยัน?');">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <td><?= $item['id'] ?></td>
                        <td>
                            <select name="category_id">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $item['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"></td>
                        <td><input type="number" step="0.01" name="price" value="<?= $item['price'] ?>"></td>
                        <td><input type="text" name="description" value="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES) ?>"></td>
                        <td><input type="text" name="photo" value="<?= htmlspecialchars($item['photo'] ?? '', ENT_QUOTES) ?>"></td>
                        <td><input type="checkbox" name="is_active" value="1" <?= $item['is_active'] ? 'checked' : '' ?>></td>
                        <td>
                            <button type="submit" name="action" value="update_item" class="btn btn-primary">ปรับปรุง</button>
                            <button type="submit" name="action" value="delete_item" class="btn btn-outline" onclick="return confirm('ลบเมนูนี้?');">ลบ</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
                <!-- Add new item -->
                <tr>
                    <form method="post">
                        <input type="hidden" name="action" value="add_item">
                        <td>ใหม่</td>
                        <td>
                            <select name="category_id">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="name" placeholder="ชื่อเมนู"></td>
                        <td><input type="number" step="0.01" name="price" placeholder="ราคา"></td>
                        <td><input type="text" name="description" placeholder="คำอธิบาย (ไม่จำเป็น)"></td>
                        <td><input type="text" name="photo" placeholder="ชื่อไฟล์รูป (ใน assets/images)"></td>
                        <td><input type="checkbox" name="is_active" value="1" checked></td>
                        <td><button type="submit" class="btn btn-primary">เพิ่ม</button></td>
                    </form>
                </tr>
            </table>
        </section>
        <!-- Settings -->
        <section>
            <h3>ตั้งค่า</h3>
            <form method="post">
                <input type="hidden" name="action" value="update_settings">
                <div style="margin-bottom:1rem;">
                    <label>Beam API Key:</label><br>
                    <input type="text" name="beam_api_key" value="<?= htmlspecialchars($settings['BEAM_API_KEY'] ?? '', ENT_QUOTES) ?>" style="width:100%;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label>Beam Webhook Secret:</label><br>
                    <input type="text" name="beam_webhook_secret" value="<?= htmlspecialchars($settings['BEAM_WEBHOOK_SECRET'] ?? '', ENT_QUOTES) ?>" style="width:100%;">
                </div>
                <button type="submit" class="btn btn-primary">บันทึกตั้งค่า</button>
            </form>
            <p style="font-size:0.85rem;margin-top:0.5rem;">* หมายเหตุ: ค่าที่บันทึกในที่นี้จะถูกเก็บในฐานข้อมูลแต่ไม่แทนที่คอนสแตนท์ใน config.php</p>
        </section>
    </div>
</body>
</html>
<?php
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จอหลักร้าน</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
      /* Grid display for tables */
      #table-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
      }
      .grid-item {
        background: var(--card-bg);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 0.5rem;
        text-align: center;
        box-shadow: var(--shadow);
      }
    </style>
</head>
<body>
    <div class="container">
        <h2>สถานะโต๊ะ</h2>
        <div id="table-grid"></div>
    </div>
    <script src="assets/js/main_display.js"></script>
</body>
</html>
<?php
require_once 'config.php';

// Xử lý thêm link
$message = '';
if (isset($_POST['add_link'])) {
    $link = trim($_POST['link']);
    $delay = (int)$_POST['delay'];

    if (!empty($link) && $delay > 0) {
        // Tính thời gian last_update và last_request (hiện tại - delay phút)
        $currentTime = new DateTime();
        $interval = new DateInterval("PT{$delay}M"); // PTXM: X phút
        $currentTime->sub($interval); // Trừ đi delay phút
        $lastTime = $currentTime->format('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO cron (link, delay, last_update, last_request, total, status) VALUES (?, ?, ?, ?, 0, 0)");
        $stmt->bind_param('siss', $link, $delay, $lastTime, $lastTime);
        $stmt->execute();
        $stmt->close();
        $message = "Đã thêm link thành công!";
    } else {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    }
}

// Xử lý chỉnh sửa link
if (isset($_POST['edit_link'])) {
    $id = (int)$_POST['id'];
    $link = trim($_POST['link']);
    $delay = (int)$_POST['delay'];

    if (!empty($link) && $delay > 0) {
        $stmt = $conn->prepare("UPDATE cron SET link = ?, delay = ? WHERE id = ?");
        $stmt->bind_param('sii', $link, $delay, $id);
        $stmt->execute();
        $stmt->close();
        $message = "Đã cập nhật link thành công!";
    } else {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    }
}

// Xử lý xóa link
if (isset($_POST['delete_link'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM cron WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $message = "Đã xóa link thành công!";
}

// Lấy danh sách link từ database
$stmt = $conn->prepare("SELECT id, link, delay, last_update, last_request, total, status FROM cron ORDER BY id ASC");
$stmt->execute();
$stmt->bind_result($id, $link, $delay, $last_update, $last_request, $total, $status);

$links = [];
while ($stmt->fetch()) {
    $links[] = [
        'id' => $id,
        'link' => $link,
        'delay' => $delay,
        'last_update' => $last_update,
        'last_request' => $last_request,
        'total' => $total,
        'status' => $status
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Cron Links</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            margin: 0;
            padding: 80px 20px 20px 20px; /* Thêm padding-top 80px để tránh header */
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container, .list-container {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .nut {
            background: rgba(52, 152, 219, 0.9);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 10px 0;
        }
        .nut:hover {
            background: rgba(52, 152, 219, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .nut-danger {
            background: rgba(231, 76, 60, 0.9);
        }
        .nut-danger:hover {
            background: rgba(231, 76, 60, 1);
        }
        .message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
        }
        .message.success {
            background: #2ecc71;
        }
        .message.error {
            background: #e74c3c;
        }
        .link-list {
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .link-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #3498db;
        }
        .link-item:hover {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .link-info {
            flex-grow: 1;
        }
        .link-info span {
            display: block;
            margin: 5px 0;
            color: #2c3e50;
        }
        .link-actions {
            display: flex;
            gap: 10px;
        }
        .edit-form {
            display: none;
            padding: 15px;
            background: #f1f1f1;
            border-radius: 5px;
            margin-top: 10px;
        }
        .confirmation {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        .confirmation p {
            margin: 0 0 15px;
            color: #2c3e50;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        /* CSS cho bảng lịch sử cron */
        .cron-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .cron-history-table th, .cron-history-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .cron-history-table th {
            background: #3498db;
            color: white;
        }
        .cron-history-table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Quản lý Cron Links</h1>
        <center><h2><a href="index.php" style="text-decoration:none">Trở về trang quản trị</a></h2></center>

        <!-- Thông báo -->
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'thành công') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Form thêm link -->
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="link">Link hoặc đường dẫn file:</label>
                    <input type="text" id="link" name="link" placeholder="Nhập link hoặc file" required>
                </div>
                <div class="form-group">
                    <label for="delay">Thời gian delay (phút):</label>
                    <input type="number" id="delay" name="delay" min="1" placeholder="Nhập số phút" required>
                </div>
                <button type="submit" name="add_link" class="nut">Thêm Link</button>
            </form>
        </div>

        <!-- Danh sách link -->
        <div class="list-container">
            <h2>Danh sách Link Cron</h2>
            <div class="link-list">
                <?php foreach ($links as $link): ?>
                    <div class="link-item" id="link-<?php echo $link['id']; ?>">
                        <div class="link-info">
                            <span><strong>ID:</strong> <?php echo $link['id']; ?></span>
                            <span><strong>Link:</strong> <?php echo htmlspecialchars($link['link']); ?></span>
                            <span><strong>Delay:</strong> <?php echo $link['delay']; ?> phút</span>
                        </div>
                        <div class="link-actions">
                            <button class="nut" onclick="showEditForm(<?php echo $link['id']; ?>)">Chỉnh sửa</button>
                            <button class="nut nut-danger" onclick="showDeleteConfirmation(<?php echo $link['id']; ?>)">Xóa</button>
                        </div>
                    </div>
                    <!-- Form chỉnh sửa -->
                    <div class="edit-form" id="edit-form-<?php echo $link['id']; ?>">
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                            <div class="form-group">
                                <label for="edit-link-<?php echo $link['id']; ?>">Link:</label>
                                <input type="text" id="edit-link-<?php echo $link['id']; ?>" name="link" value="<?php echo htmlspecialchars($link['link']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="edit-delay-<?php echo $link['id']; ?>">Delay (phút):</label>
                                <input type="number" id="edit-delay-<?php echo $link['id']; ?>" name="delay" value="<?php echo $link['delay']; ?>" min="1" required>
                            </div>
                            <button type="submit" name="edit_link" class="nut">Lưu</button>
                            <button type="button" class="nut nut-danger" onclick="hideEditForm(<?php echo $link['id']; ?>)">Hủy</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Lịch sử Cron -->
            <h2>Lịch sử Cron</h2>
            <table class="cron-history-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Link</th>
                        <th>Delay</th>
                        <th>Lượt update</th>
                        <th>Last request</th>
                        <th>Total update</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td><?php echo $link['id']; ?></td>
                            <td><?php echo htmlspecialchars($link['link']); ?></td>
                            <td><?php echo $link['delay']; ?> phút</td>
                            <td><?php echo $link['last_update']; ?></td>
                            <td><?php echo $link['last_request']; ?></td>
                            <td><?php echo $link['total']; ?></td>
                            <td><?php echo $link['status'] == 'WAIT' ? 'chờ' : 'thành công'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation box -->
    <div class="overlay" id="overlay"></div>
    <div class="confirmation" id="confirmation">
        <p>Bạn có chắc chắn muốn xóa link này không?</p>
        <form method="POST" id="delete-form">
            <input type="hidden" name="id" id="delete-id">
            <button type="submit" name="delete_link" class="nut">Xác nhận</button>
            <button type="button" class="nut nut-danger" onclick="hideConfirmation()">Hủy</button>
        </form>
    </div>

    <script>
        function showEditForm(id) {
            document.getElementById(`edit-form-${id}`).style.display = 'block';
        }

        function hideEditForm(id) {
            document.getElementById(`edit-form-${id}`).style.display = 'none';
        }

        function showDeleteConfirmation(id) {
            document.getElementById('delete-id').value = id;
            document.getElementById('confirmation').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function hideConfirmation() {
            document.getElementById('confirmation').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }
    </script>
</body>
</html>
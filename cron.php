<?php
// Kết nối cơ sở dữ liệu qua file config.php
require_once 'config.php';

// Thiết lập múi giờ GMT+7
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Hàm tính khoảng thời gian (phút) giữa hai mốc thời gian
function getTimeDifferenceInMinutes($lastUpdate) {
    if ($lastUpdate === '0000-00-00 00:00:00' || !$lastUpdate) {
        return PHP_INT_MAX; // Nếu chưa chạy lần nào hoặc giá trị không hợp lệ, trả về giá trị lớn để chạy ngay
    }

    try {
        $last = new DateTime($lastUpdate); // lastUpdate là chuỗi datetime
        $now = new DateTime();
        $interval = $now->diff($last);
        return ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i; // Tổng số phút
    } catch (Exception $e) {
        error_log("Lỗi DateTime trong getTimeDifferenceInMinutes: " . $e->getMessage());
        return PHP_INT_MAX; // Nếu lỗi, chạy link ngay
    }
}

// Hàm chạy link và cập nhật database
function runLink($conn, $linkData) {
    $id = $linkData['id'];
    $link = $linkData['link'];
    $delay = $linkData['delay'];
    $lastUpdate = $linkData['last_update'];
    $total = $linkData['total'];
    $currentTime = date('Y-m-d H:i:s'); // Thời gian hiện tại dạng datetime

    // Ghi thời gian truy cập vào last_request (luôn thực hiện bất kể link có chạy hay không)
    $stmt = $conn->prepare("UPDATE cron SET last_request = ? WHERE id = ?");
    $stmt->bind_param('si', $currentTime, $id);
    $stmt->execute();
    $stmt->close();

    $timeDiff = getTimeDifferenceInMinutes($lastUpdate);

    if ($timeDiff >= $delay) {
        // Chạy link nếu đã đủ thời gian delay
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Timeout 300 giây
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response !== false) {
            // Thành công: Cập nhật last_update, tăng total, status = OK
            $newTotal = $total + 1;
            $stmt = $conn->prepare("UPDATE cron SET last_update = ?, total = ?, status = 'OK' WHERE id = ?");
            $stmt->bind_param('sii', $currentTime, $newTotal, $id);
            $stmt->execute();
            $stmt->close();
            return ['link' => $link, 'status' => 'OK', 'message' => "Chạy thành công"];
        } else {
            // Thất bại: Cập nhật last_update, status = ERROR
            $stmt = $conn->prepare("UPDATE cron SET last_update = ?, status = 'ERROR' WHERE id = ?");
            $stmt->bind_param('si', $currentTime, $id);
            $stmt->execute();
            $stmt->close();
            return ['link' => $link, 'status' => 'ERROR', 'message' => "Chạy thất bại (HTTP Code: $httpCode)"];
        }
    } else {
        // Chưa đủ thời gian: Ghi status = WAIT, không thay đổi last_update
        $stmt = $conn->prepare("UPDATE cron SET status = 'WAIT' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        return ['link' => $link, 'status' => 'WAIT', 'message' => "Chưa đủ $delay phút kể từ lần chạy cuối"];
    }
}

// Lấy danh sách link từ database
$stmt = $conn->prepare("SELECT id, link, delay, last_update, total FROM cron ORDER BY id ASC");
$stmt->execute();
$stmt->bind_result($id, $link, $delay, $last_update, $total);

$links = [];
while ($stmt->fetch()) {
    $links[] = [
        'id' => $id,
        'link' => $link,
        'delay' => $delay,
        'last_update' => $last_update,
        'total' => $total
    ];
}
$stmt->close();

// Xử lý chạy tất cả các link
$results = [];
foreach ($links as $linkData) {
    $results[] = runLink($conn, $linkData);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Job</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            max-width: 900px;
            width: 100%;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
        }
        .loading {
            text-align: center;
            font-size: 18px;
            color: #3498db;
            margin: 20px 0;
            display: none;
        }
        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #3498db;
            border-top: 3px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .result-container {
            margin-top: 20px;
        }
        .result-item {
            padding: 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
        }
        .result-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            background: #fff;
        }
        .result-item.OK { border-left-color: #2ecc71; } /* Xanh lá cho OK */
        .result-item.ERROR { border-left-color: #e74c3c; } /* Đỏ cho ERROR */
        .result-item.WAIT { border-left-color: #f1c40f; } /* Vàng cho WAIT */
        .result-link {
            color: #2c3e50;
            font-weight: bold;
            word-break: break-all;
        }
        .result-status {
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 12px;
            margin-left: 10px;
        }
        .result-status.OK { background: #2ecc71; color: white; }
        .result-status.ERROR { background: #e74c3c; color: white; }
        .result-status.WAIT { background: #f1c40f; color: white; }
        .result-message {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 5px;
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
            display: inline-block;
            text-decoration: none;
        }
        .nut:hover {
            background: rgba(52, 152, 219, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cron Job</h1>
        <div class="loading" id="loading">Đang xử lý...</div>
        <div class="result-container" id="results" style="display: none;">
            <?php foreach ($results as $result): ?>
                <div class="result-item <?php echo $result['status']; ?>">
                    <span class="result-link"><?php echo htmlspecialchars($result['link']); ?></span>
                    <span class="result-status <?php echo $result['status']; ?>"><?php echo $result['status']; ?></span>
                    <div class="result-message"><?php echo $result['message']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');

            loading.style.display = 'block';
            results.style.display = 'none';

            setTimeout(() => {
                loading.style.display = 'none';
                results.style.display = 'block';
            }, 2000); // Hiển thị loading trong 2 giây (có thể điều chỉnh)
        });
    </script>
</body>
</html>
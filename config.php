<?php

// Thông tin kết nối database
define('DB_SERVER', 'localhost'); // Thay đổi nếu server database của bạn khác
define('DB_USERNAME', 'username'); // Thay đổi username
define('DB_PASSWORD', 'password'); // Thay đổi password
define('DB_NAME', 'database'); // Thay đổi tên database bạn muốn tạo

// Cố gắng kết nối đến database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập encoding UTF-8 để tránh lỗi font
$conn->set_charset("utf8");

?>
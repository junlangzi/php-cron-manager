# php-cron-manager Trình quản lý cron tổng hợp all in one

Đây là file quản lý toàn bộ các link cron của web gom hết vào 1 link tổng hợp. Tại đây bạn có thể

* Set thời gian delay cron từng link
* Đếm tổng số lượt cron đã chạy
* Kiểm tra lần cron chạy gần nhất là cách đây bao lâu
* Tình trạng Link cron trong lần chạy gần đây nhất

**File cron.php là file được gọi để chạy các link cron.**
**File cronadmin.php là file quản lý, thêm, bớt chỉnh sửa các link cron vào database**

**Hướng dẫn sử dụng.**
Upload file lên hosting. Vị trí tuỳ ý.
Kết nối file config với database:

```
define('DB_SERVER', 'localhost'); // Thay đổi nếu server database của bạn khác
define('DB_USERNAME', 'username'); // Thay đổi username
define('DB_PASSWORD', 'password'); // Thay đổi password
define('DB_NAME', 'database'); // Thay đổi tên database bạn muốn tạo
```

import file database.sql vào data quản lý file cron

Có thể sử dụng https://www.fastcron.com/ để chạy file cron

Màn hỉnh quản lý: 

![image](https://raw.githubusercontent.com/junlangzi/php-cron-manager/refs/heads/main/demo/demo1.png)

Màn hình file cron khi chạy:

![image](https://raw.githubusercontent.com/junlangzi/php-cron-manager/refs/heads/main/demo/demo2.png)

<?php
session_start();
session_destroy(); // Xóa toàn bộ phiên đăng nhập
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng xuất</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            background: #f0f2f5; 
        }
        .logout-box { 
            text-align: center; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        h2 { color: #1a73e8; }
        .btn-home { 
            display: inline-block; 
            margin-top: 20px; 
            padding: 10px 20px; 
            background: #28a745; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold; 
        }
        .btn-home:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="logout-box">
        <h2>Bạn đã đăng xuất thành công!</h2>
        <p>Cảm ơn bạn đã sử dụng hệ thống.</p>
        <a href="index.php" class="btn-home">Quay về trang chủ</a>
    </div>
</body>
</html>

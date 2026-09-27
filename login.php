<?php
// login.php
session_start();
require 'db.php';

$error = '';

if (isset($_SESSION['auth_user'])) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['auth_user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'role'     => $user['role'],
                'be_name'  => $user['be_name']
            ];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Sai tên đăng nhập hoặc mật khẩu!';
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Đăng Nhập Quản Trị</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .login-box {
            background: white;
            width: 100%;
            max-width: 380px;
            padding: 30px 24px;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        }
        .login-header { text-align: center; margin-bottom: 24px; }
        .login-header i { font-size: 38px; color: #4f46e5; margin-bottom: 8px; }
        .login-header h2 { font-size: 20px; font-weight: 800; color: #0f172a; }
        .login-header p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: #4f46e5; }
        .password-wrapper .form-control {
            padding-right: 42px;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            transition: color 0.2s;
        }
        .toggle-password:hover {
            color: #4f46e5;
        }
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn-submit:active { transform: scale(0.98); }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .back-home { text-align: center; margin-top: 18px; }
        .back-home a { color: #64748b; text-decoration: none; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <i class="fa-solid fa-user-shield"></i>
            <h2>Đăng Nhập Quản Trị</h2>
            <p>Quản trị thời khoá biểu</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><i class="fa-solid fa-circle-exclamation mr-1"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Tên tài khoản</label>
                <input type="text" name="username" class="form-control" placeholder="" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                    <button type="button" class="toggle-password" id="btnTogglePassword" aria-label="Hiện/Ẩn mật khẩu">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-submit">Đăng nhập</button>
        </form>

        <div class="back-home">
            <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Xem thời khóa biểu</a>
        </div>
    </div>

    <script>
        const btnToggle = document.getElementById('btnTogglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        btnToggle.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });
    </script>
</body>
</html>

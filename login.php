<?php
/**
 * login.php
 * Login screen for KaTunYu Coffee POS.
 * Checks username/password against the `employees` table (md5 password,
 * same scheme used by handlers/employees_handler.php) and sets the
 * session keys that index.php and the rest of the app expect:
 *   $_SESSION['user_id'], $_SESSION['fullname'], $_SESSION['role']
 */
session_start();

require_once 'config.php';
require_once 'functions.php';

// Already logged in -> go straight to the app
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
    
}

$conn = getDBConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, fullname, role, status FROM employees WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $employee = $result->fetch_assoc();

        if (!$employee) {
            $error = 'ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານ ບໍ່ຖືກຕ້ອງ';
        } elseif ($employee['status'] !== 'active') {
            $error = 'ບັນຊີນີ້ຖືກລະງັບການໃຊ້ງານ ກະລຸນາຕິດຕໍ່ຜູ້ດູແລລະບົບ';
        } elseif (md5($password) !== $employee['password']) {
            $error = 'ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານ ບໍ່ຖືກຕ້ອງ';
        } else {
            $_SESSION['user_id']  = $employee['id'];
            $_SESSION['fullname'] = $employee['fullname'];
            $_SESSION['role']     = $employee['role'];
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>☕ ເຂົ້າສູ່ລະບົບ - KaTunYu Coffee POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
        }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(160deg, #2D1B14 0%, #4A2A1E 55%, #6C3A2E 100%);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-hover);
            padding: 40px 32px;
        }
        .login-brand { text-align: center; margin-bottom: 28px; }
        .login-brand .logo-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: var(--secondary);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 30px; color: #2D1B14;
            box-shadow: 0 4px 16px rgba(212, 163, 115, 0.3);
            margin-bottom: 12px;
        }
        .login-brand h4 { font-weight: 700; color: var(--text-dark); margin: 0; }
        .login-brand small { color: #999; letter-spacing: 1px; text-transform: uppercase; font-size: 11px; }
        .login-alert {
            background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none;
            border-radius: 12px; padding: 12px 16px; font-size: 14px;
            display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
        }
        .login-input-group { position: relative; margin-bottom: 16px; }
        .login-input-group i {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #b8a89a; font-size: 15px;
        }
        .login-input-group input {
            padding-left: 44px;
        }
        .btn-login {
            width: 100%; margin-top: 8px;
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <div class="logo-icon"><i class="fas fa-mug-hot"></i></div>
            <h4>KaTunYu Coffee</h4>
            <small>POS System</small>
        </div>

        <?php if ($error): ?>
            <div class="login-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="login-input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" class="form-pos w-100" placeholder="ຊື່ຜູ້ໃຊ້" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="login-input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="form-pos w-100" placeholder="ລະຫັດຜ່ານ" required>
            </div>
            <button type="submit" class="btn-pos btn-login">
                <i class="fas fa-sign-in-alt"></i> ເຂົ້າສູ່ລະບົບ
            </button>
        </form>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>

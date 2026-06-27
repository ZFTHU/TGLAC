<?php
/**
 * 登录页面
 */

$pageTitle = '登录';
require_once __DIR__ . '/config/config.php';

// 检查安装状态
if (!isInstalled()) {
    header('Location: /install.php');
    exit;
}

// 已登录则跳转到后台
if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// 处理登录请求
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $dbConfig = getDatabaseConfig();
        $db = Database::create($dbConfig);
        $userModel = new User($db);

        $user = $userModel->verifyLogin($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_admin'] = ($user['role'] === 'admin') ? true : false;

            header('Location: /admin/');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    } else {
        $error = '请填写用户名和密码';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 博客系统</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('/img/<?php
                $imgDir = __DIR__ . '/img';
                $images = [];
                if (is_dir($imgDir)) {
                    foreach (glob($imgDir . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) as $f) {
                        $images[] = basename($f);
                    }
                }
                echo !empty($images) ? $images[array_rand($images)] : '';
            ?>') center/cover no-repeat;
            position: relative;
            padding: 20px;
        }
        .login-container::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.1);
        }
        .login-card {
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h1 {
            font-family: var(--font-serif);
            font-size: 1.75rem;
            margin-bottom: 8px;
        }

        .login-header p {
            opacity: 0.8;
            font-size: 0.875rem;
        }

        .login-body {
            padding: 30px;
        }

        .login-error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }

        .login-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            margin-top: 20px;
        }

        .login-footer a {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .login-footer a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>博客系统</h1>
                <p>管理员登录</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <input type="text" name="username" class="form-input" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label">密码</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">登录</button>
                </form>

                <div class="login-footer">
                    <a href="/">返回首页</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>

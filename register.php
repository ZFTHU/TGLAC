<?php
/**
 * 注册页面
 */

$pageTitle = '注册';
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

// 处理注册请求
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 验证输入
    if (empty($username)) {
        $error = '请输入用户名';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度必须在3-20个字符之间';
    } elseif (empty($email)) {
        $error = '请输入邮箱';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } elseif (empty($password)) {
        $error = '请输入密码';
    } elseif (strlen($password) < 6) {
        $error = '密码长度不能少于6位';
    } elseif ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } else {
        $dbConfig = getDatabaseConfig();
        $db = Database::create($dbConfig);
        $userModel = new User($db);

        // 检查用户名是否已存在
        if ($userModel->getByUsername($username)) {
            $error = '用户名已存在';
        } elseif ($userModel->getByEmail($email)) {
            $error = '邮箱已被注册';
        } else {
            // 创建用户
            $userId = generateUUID();
            $userModel->create([
                'id' => $userId,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'user'
            ]);

            // 自动登录
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user_name'] = $username;
            $_SESSION['user_role'] = 'user';
            $_SESSION['is_admin'] = false;

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 博客系统</title>
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
            background: var(--accent-color);
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
            opacity: 0.9;
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

        .login-success {
            background: #c6f6d5;
            color: #2f855a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            text-align: center;
        }

        .login-success a {
            color: #2f855a;
            font-weight: 600;
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

        .form-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(237, 137, 54, 0.1);
        }

        .btn-accent {
            background: var(--accent-color);
            color: white;
            border: none;
        }

        .btn-accent:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>博客系统</h1>
                <p>用户注册</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="login-success">
                    注册成功！<br>
                    <a href="/login.php">点击这里登录</a>
                </div>
                <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <input type="text" name="username" class="form-input" required autofocus
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               placeholder="3-20个字符">
                    </div>

                    <div class="form-group">
                        <label class="form-label">邮箱</label>
                        <input type="email" name="email" class="form-input" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="用于找回密码">
                    </div>

                    <div class="form-group">
                        <label class="form-label">密码</label>
                        <input type="password" name="password" class="form-input" required
                               placeholder="至少6位">
                    </div>

                    <div class="form-group">
                        <label class="form-label">确认密码</label>
                        <input type="password" name="confirm_password" class="form-input" required
                               placeholder="再次输入密码">
                    </div>

                    <button type="submit" class="btn btn-accent btn-lg" style="width: 100%;">注册</button>
                </form>

                <div class="login-footer">
                    <a href="/login.php">已有账号？立即登录</a>
                    <span style="margin: 0 8px; color: #ddd;">|</span>
                    <a href="/">返回首页</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>

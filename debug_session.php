<?php
require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Session Info ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);

echo "\n=== isLoggedIn Check ===\n";
$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
echo "Session user_id: " . ($uid ? $uid : 'NOT SET') . "\n";
echo "Session user_role: " . ($role ? $role : 'NOT SET') . "\n";
echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "\n";
echo "isAdmin(): " . (isAdmin() ? 'true' : 'false') . "\n";

echo "\n=== Database Users ===\n";
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$users = $db->fetchAll("SELECT id, username, email, role FROM users");
foreach ($users as $u) {
    echo "User: id=" . $u['id'] . ", username=" . $u['username'] . ", email=" . $u['email'] . ", role=" . $u['role'] . "\n";
}

if ($uid) {
    echo "\n=== Try to find user by session id ===\n";
    $userModel = new User($db);
    $user = $userModel->getById($uid);
    if ($user) {
        echo "Found user: " . $user['username'] . "\n";
    } else {
        echo "User NOT found by id: " . $uid . "\n";
    }
}

echo "\n=== Test Article ===\n";
$articleModel = new Article($db);
$article = $articleModel->getById('fd739a2c-9b75-49ed-a344-b21c2a3451c8');
if ($article) {
    echo "Article found: " . $article['title'] . "\n";
} else {
    echo "Article NOT found\n";
}

<?php
/**
 * 公告详情页
 */

require_once __DIR__ . '/../config/config.php';

// 检查安装状态
if (!isInstalled()) {
    header('Location: /install.php');
    exit;
}

// 获取公告ID
$announcementId = $_GET['id'] ?? null;

if (!$announcementId) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../404.html';
    exit;
}

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取公告详情
$announcementModel = new Announcement($db);
$announcement = $announcementModel->getById($announcementId);

if (!$announcement) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../404.html';
    exit;
}

// 获取分类（用于导航）
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

// 获取系统设置
$settingsModel = new Settings($db);
$settings = $settingsModel->getAll();

$pageTitle = $announcement['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="main-layout">
        <!-- 左栏：用户头像 + 名称 + 一言 + 公告 + 时间 + IP签名 -->
        <aside class="site-left-panel">
            <div class="site-info-card">
                <?php
                $defaultAvatarFile = glob(__DIR__ . '/../img/tx/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
                $defaultAvatar = !empty($defaultAvatarFile) ? basename($defaultAvatarFile[0]) : '';
                ?>
                <div class="site-avatar" style="background: transparent; box-shadow: none; overflow: hidden;">
                    <img src="/img/tx/<?= $defaultAvatar ?>" alt="头像" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>

                <?php if (isLoggedIn()): ?>
                <h2 class="site-name"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                <p class="site-desc"><?= $_SESSION['user_role'] === 'admin' ? '网站管理员' : '注册用户' ?></p>
                <?php else: ?>
                <h2 class="site-name">游客</h2>
                <p class="site-desc">访客ID: <?= substr(session_id(), 0, 8) ?></p>
                <?php endif; ?>

                <!-- 一言 -->
                <div class="site-hitokoto">
                    <div class="hitokoto-quote-mark">&ldquo;</div>
                    <p id="hitokoto-text" class="hitokoto-content">正在寻找美好的句子...</p>
                    <div class="hitokoto-quote-mark-bottom">&rdquo;</div>
                </div>

                <!-- 时间 -->
                <div class="site-time">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="site-time-text">--:--:--</span>
                </div>

                <!-- IP签名 -->
                <div class="site-ip-sign">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"></path>
                    </svg>
                    <span id="site-ip-text">正在获取IP信息...</span>
                </div>
            </div>
        </aside>

        <!-- 中栏：公告内容 -->
        <main class="site-main-section">
            <article class="article-detail">
                <!-- 公告头部 -->
                <header class="article-header" style="margin-bottom: 32px;">
                    <span class="article-card-category" style="margin-bottom: 12px; display: inline-block;">
                        <?php if (!empty($announcement['is_top'])): ?>置顶公告<?php else: ?>公告<?php endif; ?>
                    </span>

                    <h1 style="font-family: var(--font-serif); font-size: 2.5rem; font-weight: 700; line-height: 1.2; margin-bottom: 16px;"><?= htmlspecialchars($announcement['title']) ?></h1>

                    <div class="article-meta" style="display: flex; align-items: center; gap: 24px; color: var(--text-muted); font-size: 0.875rem;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <?= date('Y年m月d日 H:i', strtotime($announcement['created_at'])) ?>
                        </span>
                    </div>
                </header>

                <!-- 公告图片 -->
                <?php if (!empty($announcement['image_url'])): ?>
                <div class="article-cover-figure" style="margin-bottom: 32px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);">
                    <img src="<?= htmlspecialchars($announcement['image_url']) ?>" alt="<?= htmlspecialchars($announcement['title']) ?>" 
                         style="width: 100%; height: auto; display: block; cursor: zoom-in;" 
                         class="lightbox-trigger" loading="lazy">
                </div>
                <?php endif; ?>

                <!-- 公告内容 -->
                <div class="article-content" style="font-size: 1.125rem; line-height: 1.8; color: var(--text-primary);">
                    <?php
                    $contentVar = $announcement['content'];
                    $lines = explode("\n", $contentVar);
                    $paragraphBuffer = '';
                    $lineIndex = 0;
                    foreach ($lines as $line) {
                        $line = trim($line);
                        $lineIndex++;
                        // 检测图片URL行
                        if (preg_match('/^(https?:\/\/.+\.(jpg|jpeg|png|gif|webp|svg))$/i', $line)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<figure class="article-figure"><img src="' . htmlspecialchars($line) . '" alt="图片" loading="lazy" class="lightbox-trigger" style="cursor: zoom-in;"></figure>';
                            continue;
                        }
                        // 检测Markdown图片语法: ![alt](url)
                        if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)\s*$/i', $line, $imgMatches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            $alt = htmlspecialchars(trim($imgMatches[1]));
                            $url = htmlspecialchars(trim($imgMatches[2]));
                            echo '<figure class="article-figure"><img src="' . $url . '" alt="' . ($alt ?: '图片') . '" loading="lazy" class="lightbox-trigger" style="cursor: zoom-in;">';
                            if ($alt) { echo '<figcaption>' . $alt . '</figcaption>'; }
                            echo '</figure>';
                            continue;
                        }
                        // 处理标题
                        if (preg_match('/^### (.*?)$/', $line, $matches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<h3>' . htmlspecialchars($matches[1]) . '</h3>'; continue;
                        }
                        if (preg_match('/^## (.*?)$/', $line, $matches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<h2>' . htmlspecialchars($matches[1]) . '</h2>'; continue;
                        }
                        if (preg_match('/^# (.*?)$/', $line, $matches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<h1>' . htmlspecialchars($matches[1]) . '</h1>'; continue;
                        }
                        // 空行 - 输出段落
                        if (empty($line)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            continue;
                        }
                        // 处理粗体和斜体
                        $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
                        $line = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $line);
                        $paragraphBuffer .= ($paragraphBuffer ? ' ' : '') . $line;
                    }
                    if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; }
                    ?>
                </div>

                <!-- 返回按钮 -->
                <div style="margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                    <a href="/" class="btn btn-outline">&larr; 返回首页</a>
                </div>
            </article>
        </main>

        <!-- 右栏：公告列表 -->
        <aside class="site-right-panel">
            <!-- 最新公告 -->
            <div class="sidebar-widget sidebar-widget-wide">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    最新公告
                </h3>
                <div class="announcement-list">
                    <?php
                    $allAnnouncements = $announcementModel->getAll(10);
                    foreach ($allAnnouncements as $ann):
                    ?>
                    <a href="/pages/announcement.php?id=<?= $ann['id'] ?>" class="announcement-item <?= $ann['is_top'] ? 'announcement-top' : '' ?> <?= $ann['id'] === $announcementId ? 'announcement-active' : '' ?>">
                        <?php if ($ann['is_top']): ?>
                        <span class="announcement-badge">置顶</span>
                        <?php endif; ?>
                        <h4 class="announcement-title"><?= htmlspecialchars($ann['title']) ?></h4>
                        <p class="announcement-content"><?= htmlspecialchars(mb_substr($ann['content'], 0, 50)) ?><?= mb_strlen($ann['content']) > 50 ? '...' : '' ?></p>
                        <span class="announcement-date"><?= date('m-d H:i', strtotime($ann['created_at'])) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
// ===== 图片灯箱 =====
(function initLightbox() {
    const images = document.querySelectorAll('.lightbox-trigger');
    if (images.length === 0) return;

    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-overlay"></div>
        <div class="lightbox-content">
            <img class="lightbox-image" alt="">
            <button class="lightbox-close" aria-label="关闭">&times;</button>
        </div>
    `;
    document.body.appendChild(lightbox);

    const style = document.createElement('style');
    style.textContent = `
        .lightbox {
            position: fixed; inset: 0; z-index: 10000;
            display: none; align-items: center; justify-content: center;
        }
        .lightbox.active { display: flex; animation: fadeIn 200ms ease; }
        .lightbox-overlay { position: absolute; inset: 0; background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(8px); }
        .lightbox-content { position: relative; max-width: 90vw; max-height: 90vh; z-index: 1; animation: zoomIn 300ms ease; }
        .lightbox-image { max-width: 100%; max-height: 90vh; border-radius: 8px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5); }
        .lightbox-close { position: absolute; top: -40px; right: -10px; width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 255, 255, 0.1); color: white; border: none; font-size: 1.5rem; cursor: pointer; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .announcement-active { background: var(--bg-gray); border-left: 3px solid var(--accent-color); }
        .article-content h1, .article-content h2, .article-content h3 {
            margin-top: 32px !important; margin-bottom: 16px !important; line-height: 1.3; padding-bottom: 8px;
        }
        .article-content h1 { font-size: 1.8rem; border-bottom: 2px solid var(--accent-color); padding-bottom: 12px; }
        .article-content h2 { font-size: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .article-content h3 { font-size: 1.25rem; }
        .article-content p { margin-bottom: 24px; line-height: 1.9; animation: fadeInUp 500ms ease-out; }
        .article-content figure { margin: 40px auto; text-align: center; animation: fadeInUp 600ms ease-out; }
        .article-content figure img { max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12); transition: transform 300ms ease, box-shadow 300ms ease; }
        .article-content figure img:hover { transform: scale(1.02); box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2); }
        .article-content figcaption { margin-top: 12px; font-size: 0.875rem; color: var(--text-muted); font-style: italic; }
    `;
    document.head.appendChild(style);

    const lightboxImage = lightbox.querySelector('.lightbox-image');
    const overlay = lightbox.querySelector('.lightbox-overlay');
    const closeBtn = lightbox.querySelector('.lightbox-close');

    function openLightbox(src) {
        lightboxImage.src = src;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
    images.forEach(img => img.addEventListener('click', function() { openLightbox(this.src); }));
    overlay.addEventListener('click', closeLightbox);
    closeBtn.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) closeLightbox();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

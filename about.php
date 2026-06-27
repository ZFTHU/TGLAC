<?php
/**
 * 关于页面
 * 布局：左栏（用户信息） | 中栏（关于内容） | 右栏（用户信息 + 分类 + 热门文章）
 */

$pageTitle = '关于';
require_once __DIR__ . '/includes/header.php';

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取系统设置
$settingsModel = new Settings($db);
$settings = $settingsModel->getAll();

// 获取分类
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

// 获取热门文章
$articleModel = new Article($db);
$popularArticles = $articleModel->getPopular(5);

// 获取公告（左栏）
$announcements = [];
try {
    $announcementModel = new Announcement($db);
    $announcements = $announcementModel->getAll(3);
} catch (Exception $e) {}
?>

<div class="container">
    <div class="main-layout">
        <!-- 左栏：用户头像 + 名称 + 一言 + 公告 + 时间 + IP签名 -->
        <aside class="site-left-panel">
            <div class="site-info-card">
                <?php
                $defaultAvatarFile = glob(__DIR__ . '/img/tx/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
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

                <!-- 公告 -->
                <?php if (!empty($announcements)): ?>
                <div class="left-announcement">
                    <?php foreach ($announcements as $ann): ?>
                    <a href="/pages/announcement.php?id=<?= $ann['id'] ?>" class="left-announcement-item">
                        <span class="left-announcement-title"><?= htmlspecialchars($ann['title']) ?></span>
                        <span class="left-announcement-date"><?= date('m-d', strtotime($ann['created_at'])) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

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

        <!-- 中栏：关于内容 -->
        <main class="site-main-section">
            <div class="sidebar-widget" style="padding: var(--spacing-xl);">
                <h1 style="font-family: var(--font-serif); font-size: 2.5rem; margin-bottom: var(--spacing-md); text-align: center;">关于本站</h1>
                <p style="color: var(--text-secondary); font-size: 1.125rem; text-align: center; margin-bottom: var(--spacing-xl);">记录生活，分享技术，与你同行</p>

                <section style="margin-bottom: var(--spacing-xl);">
                    <h2 style="font-family: var(--font-serif); font-size: 1.5rem; margin-bottom: var(--spacing-md); padding-left: var(--spacing-md); border-left: 4px solid var(--primary-color);">关于我</h2>
                    <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: var(--spacing-md);">你好！欢迎来到我的个人博客。这里是我分享技术文章和生活感悟的地方。</p>
                    <p style="color: var(--text-secondary); line-height: 1.8;">我热爱编程，喜欢探索新技术，希望通过这个平台与大家交流学习。</p>
                </section>

                <section style="margin-bottom: var(--spacing-xl);">
                    <h2 style="font-family: var(--font-serif); font-size: 1.5rem; margin-bottom: var(--spacing-md); padding-left: var(--spacing-md); border-left: 4px solid var(--primary-color);">关于本站</h2>
                    <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: var(--spacing-md);">这个博客使用 PHP + SQLite 构建，采用简洁现代的设计风格。</p>
                    <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: var(--spacing-md);">主要分享以下内容：</p>
                    <ul style="list-style: none; padding: 0; color: var(--text-secondary);">
                        <li style="padding: var(--spacing-sm) 0; padding-left: var(--spacing-lg); position: relative;">
                            <span style="position: absolute; left: 0; color: var(--primary-color); font-weight: bold;">•</span>
                            Web 开发技术
                        </li>
                        <li style="padding: var(--spacing-sm) 0; padding-left: var(--spacing-lg); position: relative;">
                            <span style="position: absolute; left: 0; color: var(--primary-color); font-weight: bold;">•</span>
                            编程心得与经验
                        </li>
                        <li style="padding: var(--spacing-sm) 0; padding-left: var(--spacing-lg); position: relative;">
                            <span style="position: absolute; left: 0; color: var(--primary-color); font-weight: bold;">•</span>
                            开源项目推荐
                        </li>
                        <li style="padding: var(--spacing-sm) 0; padding-left: var(--spacing-lg); position: relative;">
                            <span style="position: absolute; left: 0; color: var(--primary-color); font-weight: bold;">•</span>
                            生活感悟
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 style="font-family: var(--font-serif); font-size: 1.5rem; margin-bottom: var(--spacing-md); padding-left: var(--spacing-md); border-left: 4px solid var(--primary-color);">联系方式</h2>
                    <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: var(--spacing-md);">如果你有任何问题或建议，欢迎通过以下方式联系我：</p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="display: flex; align-items: center; gap: var(--spacing-sm); padding: var(--spacing-sm) 0; color: var(--text-secondary);">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>Email: example@email.com</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: var(--spacing-sm); padding: var(--spacing-sm) 0; color: var(--text-secondary);">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <span>GitHub: github.com/username</span>
                        </li>
                    </ul>
                </section>
            </div>
        </main>

        <!-- 右栏：分类 + 热门文章 -->
        <aside class="site-right-panel">
            <!-- 分类列表 -->
            <div class="sidebar-widget sidebar-widget-wide hide-on-mobile">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    分类
                </h3>
                <ul class="category-list">
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/category.php?slug=<?= htmlspecialchars($cat['slug']) ?>">
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                            <span class="category-count"><?= $cat['article_count'] ?? 0 ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 热门文章 -->
            <div class="sidebar-widget sidebar-widget-wide hide-on-mobile">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 20c0-2 2-3.5 2-3.5s2 1.5 2 3.5 2 2 2 2-1 1.5-2.343-3.343z"></path>
                    </svg>
                    热门文章
                </h3>
                <ul class="popular-list">
                    <?php foreach ($popularArticles as $article): ?>
                    <li>
                        <div class="popular-list-content">
                            <a href="/article.php?id=<?= $article['id'] ?>" class="popular-list-title"><?= htmlspecialchars($article['title']) ?></a>
                            <div class="popular-list-meta">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <?= $article['view_count'] ?? 0 ?> 次阅读
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

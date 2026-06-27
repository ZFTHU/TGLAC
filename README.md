# TGLAC 博客系统

**版本：2.4 LTS（长期稳定版本）**

一个现代化的个人博客系统，采用原生技术栈构建，无框架依赖，轻量高效。

## 项目简介

TGLAC（Trae's Blog）是一个功能完整的个人博客系统，专注于简洁的设计和流畅的用户体验。系统采用纯原生开发，无任何第三方框架依赖，代码轻量、性能优异、易于部署和定制。

项目名称来源于开发者昵称，寓意"记录生活、分享思考"的理念。系统设计注重移动端体验，采用响应式布局，完美适配各种设备屏幕。

## 核心特性

### 前端体验
- **响应式布局**：桌面端顶部导航 + 移动端底部导航双模式，自动适配设备
- **AJAX无刷新导航**：页面切换无需刷新，流畅丝滑的浏览体验
- **侧边栏导航**：移动端侧滑菜单，半透明毛玻璃效果
- **通知中心**：实时通知推送，红点提醒，声音提示
- **看板娘**：Live2D看板娘角色，增添趣味互动
- **一言 & IP位置**：动态展示优美语句和访客地理位置
- **音乐播放器**：内置背景音乐播放功能

### 内容管理
- **文章系统**：支持富文本编辑，分类归档，标签管理
- **分类管理**：灵活的多级分类体系
- **评论系统**：访客评论功能，支持审核机制
- **公告系统**：站点公告发布，支持展开收起动画

### 后台管理
- **可视化后台**：现代化的管理界面，操作直观便捷
- **文章管理**：文章发布、编辑、删除、状态控制
- **分类管理**：分类创建、排序、描述编辑
- **公告管理**：公告发布、编辑、置顶设置
- **通知管理**：站内通知发送、已读状态追踪
- **系统设置**：站点信息、SEO配置、功能开关

### 技术亮点
- **多数据库支持**：MySQL、MongoDB、SQLite 三选一
- **安装向导**：可视化安装流程，一键配置部署
- **安全防护**：SQL注入防护、XSS过滤、CSRF防护
- **缓存优化**：API数据缓存、请求节流、竞态保护

## 技术栈

| 层级 | 技术 |
|------|------|
| 前端 | HTML5 + CSS3 + JavaScript (ES6+) |
| 后端 | PHP 8.0+ |
| 数据库 | MySQL / MongoDB / SQLite |
| 样式 | CSS Variables + Flexbox/Grid |
| 动画 | CSS Transitions + JavaScript |

## 系统要求

- PHP 8.0 或更高版本
- PDO 扩展
- PDO MySQL 驱动（使用MySQL时）
- JSON 扩展
- Mbstring 扩展
- 可写入的文件系统

## 安装部署

### 快速安装

1. 将项目文件上传至Web服务器
2. 确保以下目录可写：
   - `config/` — 配置文件存储
   - `uploads/` — 上传文件存储
   - `data/` — SQLite数据库文件（使用SQLite时）
3. 浏览器访问站点，自动进入安装向导
4. 按向导完成环境检测、数据库配置、管理员创建
5. 安装完成，开始使用

### 数据库选择

| 数据库 | 适用场景 | 特点 |
|--------|----------|------|
| SQLite | 小型博客 | 零配置，文件存储，轻量便捷 |
| MySQL | 中大型博客 | 高性能，高并发，生产推荐 |
| MongoDB | 灵活需求 | 文档型，结构灵活，扩展性强 |

## 目录结构

```
TGLAC/
├── index.php              # 首页入口
├── install.php            # 安装向导
├── article.php            # 文章详情页
├── category.php           # 分类列表页
├── about.php              # 关于页面
├── login.php              # 登录页
├── register.php           # 注册页
├── 404.html               # 404错误页
├── favicon.svg            # 站点图标
├── config/                # 配置目录
│   └── config.php         # 主配置文件
├── includes/              # 公共模板
│   ├── header.php         # 页头模板
│   ├── footer.php         # 页脚模板
│   ├── admin-header.php   # 后台页头
│   └── admin-footer.php   # 后台页脚
│   └── auth.php           # 认证检查
├── pages/                 # 功能页面
│   ├── notifications.php  # 用户通知列表
│   └── announcement.php   # 公告详情页
├── admin/                 # 管理后台
│   ├── index.php          # 后台仪表盘
│   ├── articles.php       # 文章管理
│   ├── article-edit.php   # 文章编辑
│   ├── categories.php     # 分类管理
│   ├── announcements.php  # 公告管理
│   ├── notifications.php  # 通知管理
│   ├── settings.php       # 系统设置
│   ├── users.php          # 用户管理
│   └── database.php       # 数据库管理
├── api/                   # API接口
│   ├── articles/          # 文章接口
│   ├── categories/        # 分类接口
│   ├── comments/          # 评论接口
│   ├── auth/              # 认证接口
│   ├── settings/          # 设置接口
│   ├── hitokoto/          # 一言接口
│   ├── install/           # 安装接口
│   ├── announcement/      # 公告接口
│   ├── announcements.php  # 公告API
│   ├── notifications.php  # 通知API
│   └── maintenance.php    # 维护模式API
├── assets/                # 靜态资源
│   ├── css/               # 样式文件
│   │   ├── style.css      # 主样式
│   │   ├── admin.css      # 后台样式
│   │   └── animations.css # 动画样式
│   ├── js/                # 脚本文件
│   │   ├── main.js        # 主脚本
│   │   ├── ajax-nav.js    # AJAX导航
│   │   ├── admin.js       # 后台脚本
│   │   ├── install.js     # 安装脚本
│   │   └── sakura.js      # 樱花特效
├── classes/               # PHP类库
│   ├── Database.php       # 数据库连接
│   ├── Article.php        # 文章模型
│   ├── Category.php       # 分类模型
│   ├── Comment.php        # 评论模型
│   ├── User.php           # 用户模型
│   ├── Settings.php       # 设置模型
│   ├── Announcement.php   # 公告模型
│   ├── Notification.php   # 通知模型
│   ├── MySQL.php          # MySQL驱动
│   ├── MongoDB.php        # MongoDB驱动
│   └── SQLite.php         # SQLite驱动
├── img/                   # 图片资源
│   ├── tx/                # 头像目录
│   ├── jz/                # 其他图片
├── mp3/                   # 音频资源
│   ├── tsy/               # 提示音
├── live2dyy/              # Live2D看板娘资源
├── wh/                    # 网站维护页面
│   ├── index.html         # 维护页
│   └── img/               # 维护页图片
├── SJK/                   # Java数据库项目（独立）
├── uploads/               # 上传目录
├── .htaccess              # Apache配置
├── composer.json          # PHP依赖配置
└── README.md              # 项目说明
```

## 版本信息

**当前版本：2.4 LTS**

长期稳定版本，经过充分测试与优化，适合生产环境部署使用。后续版本计划暂未发布，当前版本将持续维护。

## 开源信息

本项目已开源发布，欢迎学习交流。

- **GitHub仓库**：[https://github.com/ZTFHU/TGLAC](https://github.com/ZTFHU/TGLAC)

## 联系方式

- **邮箱**：2636.474932@qq.com

## 安全建议

1. 生产环境关闭错误详细显示
2. 启用HTTPS加密传输
3. 定期备份数据库数据
4. 保护 `config/` 目录访问权限
5. 设置合理的文件上传限制

## 许可证

MIT License

---

感谢使用 TGLAC 博客系统。
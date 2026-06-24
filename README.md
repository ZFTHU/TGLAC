# 博客系统

一个基于 HTML + CSS + JavaScript + PHP 构建的现代化博客系统。

## 功能特性

- 📝 文章管理：支持Markdown写作，文章发布与编辑
- 🗂️ 分类管理：灵活的文章分类系统
- 💬 评论系统：访客评论功能
- 🎨 响应式设计：完美适配桌面端和移动端
- 🎬 流畅动画：底部导航栏滑动隐藏/弹出，侧边栏展开动画
- 🗄️ 多数据库支持：MySQL、MongoDB、SQLite
- 🔐 安装引导：可视化安装流程，环境检测，数据库配置

## 技术栈

- **前端**：HTML5 + CSS3 + JavaScript (ES6+)
- **后端**：PHP 8.0+
- **数据库**：MySQL / MongoDB / SQLite
- **样式**：CSS变量 + Flexbox/Grid布局
- **动画**：CSS Transitions + CSS Animations

## 安装要求

- PHP 8.0 或更高版本
- PDO 扩展
- PDO MySQL 驱动（使用MySQL时）
- JSON 扩展
- Mbstring 扩展
- 可写入的文件系统（用于配置和上传）

## 安装步骤

1. 将项目文件上传到Web服务器
2. 确保以下目录可写：
   - `config/`
   - `uploads/`
   - `data/`（使用SQLite时）
3. 在浏览器中访问网站，自动跳转到安装向导
4. 按照安装向导完成配置：
   - 环境检测
   - 数据库配置
   - 管理员账号创建
5. 安装完成后即可使用

## 目录结构

```
blog/
├── index.php              # 首页
├── install.php            # 安装引导页
├── article.php            # 文章详情页
├── login.php              # 登录页
├── config/                # 配置文件目录
│   ├── config.php         # 主配置
│   ├── database.json      # 数据库配置
│   └── installed.json     # 安装状态
├── includes/              # 公共模板
├── admin/                 # 管理后台
├── api/                   # API接口
├── assets/                # 静态资源
│   ├── css/               # 样式文件
│   └── js/                # 脚本文件
├── classes/               # PHP类文件
└── uploads/               # 上传文件目录
```

## 动画效果

### 底部导航栏（已暂时废弃移动端）
- 向下滚动时自动隐藏（translateY(100%)）
- 向上滚动时自动弹出（translateY(0)）
- 过渡时间：300ms
- 缓动函数：ease-out

### 侧边栏（已暂时废弃移动端）
- 点击菜单按钮从左侧滑出
- 背景遮罩层淡入效果
- 过渡时间：350ms
- 缓动函数：cubic-bezier(0.4, 0, 0.2, 1)

## 数据库配置

系统支持三种数据库：

### SQLite（推荐）
- 无需额外配置
- 数据存储在文件中
- 适合小型博客

### MySQL
- 适合中大型博客
- 需要预先创建数据库
- 支持高并发访问

### MongoDB
- 文档型数据库
- 灵活的数据结构
- 需要安装MongoDB PHP Driver

## 安全建议

1. 生产环境关闭错误显示
2. 使用HTTPS
3. 定期备份数据库
4. 保护 `config/` 目录
5. 设置正确的文件权限

## 许可证

MIT License

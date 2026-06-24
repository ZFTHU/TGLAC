/**
 * 主脚本文件
 */

// DOM加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化所有模块
    initNavigation();
    initSidebar();
    initCarousel();
    initAnimations();
    initForms();
    initToastContainer();
    initScrollToTop();
    initHitokoto();
});

/**
 * 初始化一言显示
 */
function initHitokoto() {
    const hitokotoEl = document.getElementById('hitokoto-text');
    const timeEl = document.getElementById('hitokoto-time');
    
    if (!hitokotoEl) return;
    
    // 显示加载中提示
    hitokotoEl.textContent = '正在寻找美好的句子...';
    
    // 更新当前时间
    function updateTime() {
        if (timeEl) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ms = String(now.getMilliseconds()).padStart(3, '0');
            timeEl.textContent = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}.${ms}`;
        }
    }
    
    // 初始更新时间
    updateTime();
    // 每秒更新时间
    setInterval(updateTime, 1000);
    
    // 从本地 API 获取
    fetch('/api/hitokoto/')
        .then(response => response.json())
        .then(data => {
            // 显示淡入动画
            hitokotoEl.style.opacity = '0';
            setTimeout(() => {
                hitokotoEl.textContent = '「' + data.hitokoto + '」';
                hitokotoEl.style.opacity = '1';
            }, 200);
        })
        .catch(() => {
            // 出错时直接显示一个默认句子
            hitokotoEl.textContent = '「愿你有一天能与重要的人重逢。」';
        });
}

/**
 * 初始化 Toast 容器
 */
function initToastContainer() {
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
}

/**
 * 初始化回到顶部按钮
 */
function initScrollToTop() {
    const btn = document.createElement('button');
    btn.className = 'scroll-to-top';
    btn.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>';
    document.body.appendChild(btn);

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    });

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

/**
 * 初始化导航栏
 */
function initNavigation() {
    const navBottom = document.querySelector('.nav-bottom');
    const menuBtn = document.querySelector('.menu-btn');

    // 底部导航栏滚动隐藏/显示
    if (navBottom) {
        let lastScrollY = window.scrollY;
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    handleNavScroll(navBottom, lastScrollY);
                    lastScrollY = window.scrollY;
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    // 移动端菜单按钮
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            openSidebar();
        });
    }

    // 设置当前页面导航高亮
    highlightCurrentNav();
}

/**
 * 处理导航栏滚动
 */
function handleNavScroll(navBottom, lastScrollY) {
    const currentScrollY = window.scrollY;
    const scrollThreshold = 100;

    if (currentScrollY > lastScrollY && currentScrollY > scrollThreshold) {
        // 向下滚动 - 隐藏导航栏
        navBottom.classList.add('hidden');
    } else {
        // 向上滚动 - 显示导航栏
        navBottom.classList.remove('hidden');
    }
}

/**
 * 高亮当前导航项
 */
function highlightCurrentNav() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-menu a, .nav-bottom-item, .sidebar-menu a');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (currentPath === '/' && href === '/index.php')) {
            link.classList.add('active');
        }
    });
}

/**
 * 初始化侧边栏
 */
function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const closeBtn = document.querySelector('.sidebar-close');

    if (!sidebar || !overlay) return;

    // 点击遮罩层关闭侧边栏
    overlay.addEventListener('click', function() {
        closeSidebar();
    });

    // 关闭按钮
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeSidebar();
        }
        );
    }

    // ESC键关闭侧边栏
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
}

/**
 * 打开侧边栏
 */
function openSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.add('open');
        overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * 关闭侧边栏
 */
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
        document.body.style.overflow = '';
    }
}

/**
 * 初始化轮播图
 */
function initCarousel() {
    const carousel = document.querySelector('.carousel');
    if (!carousel) return;

    const slides = carousel.querySelector('.carousel-slides');
    const dots = carousel.querySelectorAll('.carousel-dot');
    const slideCount = carousel.querySelectorAll('.carousel-slide').length;

    if (slideCount <= 1) return;

    let currentIndex = 0;
    let autoPlayTimer;

    // 自动播放
    function startAutoPlay() {
        autoPlayTimer = setInterval(function() {
            goToSlide((currentIndex + 1) % slideCount);
        }, 5000);
    }

    // 停止自动播放
    function stopAutoPlay() {
        clearInterval(autoPlayTimer);
    }

    // 切换到指定幻灯片
    function goToSlide(index) {
        currentIndex = index;
        slides.style.transform = `translateX(-${index * 100}%)`;

        // 更新指示点
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    // 点击指示点
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            stopAutoPlay();
            goToSlide(index);
            startAutoPlay();
        });
    });

    // 鼠标悬停暂停
    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);

    // 触摸滑动支持
    let touchStartX = 0;
    let touchEndX = 0;

    carousel.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        stopAutoPlay();
    }, { passive: true });

    carousel.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].clientX;
        handleSwipe();
        startAutoPlay();
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // 向左滑动 - 下一张
                goToSlide((currentIndex + 1) % slideCount);
            } else {
                // 向右滑动 - 上一张
                goToSlide((currentIndex - 1 + slideCount) % slideCount);
            }
        }
    }

    // 开始自动播放
    startAutoPlay();
}

/**
 * 初始化滚动动画
 */
function initAnimations() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');

    if (animatedElements.length === 0) return;

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    animatedElements.forEach(el => {
        observer.observe(el);
    });
}

/**
 * 初始化表单
 */
function initForms() {
    // 表单验证
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });

    // 实时验证
    const inputs = document.querySelectorAll('.form-input, .form-textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateInput(this);
        });

        input.addEventListener('input', function() {
            clearError(this);
        });
    });
}

/**
 * 验证表单
 */
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!validateInput(input)) {
            isValid = false;
        }
    });

    return isValid;
}

/**
 * 验证单个输入
 */
function validateInput(input) {
    const value = input.value.trim();
    const type = input.type;
    const required = input.hasAttribute('required');

    // 必填验证
    if (required && !value) {
        showError(input, '此字段为必填项');
        return false;
    }

    // 邮箱验证
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showError(input, '请输入有效的邮箱地址');
            return false;
        }
    }

    // 密码验证
    if (type === 'password' && value && input.dataset.minLength) {
        const minLength = parseInt(input.dataset.minLength);
        if (value.length < minLength) {
            showError(input, `密码至少需要${minLength}个字符`);
            return false;
        }
    }

    clearError(input);
    return true;
}

/**
 * 显示错误信息
 */
function showError(input, message) {
    clearError(input);

    input.classList.add('error');

    const errorEl = document.createElement('div');
    errorEl.className = 'form-error';
    errorEl.textContent = message;

    input.parentNode.appendChild(errorEl);
}

/**
 * 清除错误信息
 */
function clearError(input) {
    input.classList.remove('error');

    const errorEl = input.parentNode.querySelector('.form-error');
    if (errorEl) {
        errorEl.remove();
    }
}

/**
 * 显示加载状态
 */
function showLoading(element) {
    element.classList.add('loading');
    element.disabled = true;
}

/**
 * 隐藏加载状态
 */
function hideLoading(element) {
    element.classList.remove('loading');
    element.disabled = false;
}

/**
 * 显示提示消息
 */
function showToast(message, type = 'info') {
    // 确保 Toast 容器存在
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    const iconEl = document.createElement('span');
    iconEl.className = 'toast-icon';
    iconEl.textContent = icons[type] || icons.info;

    const messageEl = document.createElement('span');
    messageEl.className = 'toast-message';
    messageEl.textContent = message;

    toast.appendChild(iconEl);
    toast.appendChild(messageEl);
    container.appendChild(toast);

    // 自动消失
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

/**
 * 格式化日期
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return date.toLocaleDateString('zh-CN', options);
}

/**
 * 格式化相对时间
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;

    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 7) {
        return formatDate(dateString);
    } else if (days > 0) {
        return `${days}天前`;
    } else if (hours > 0) {
        return `${hours}小时前`;
    } else if (minutes > 0) {
        return `${minutes}分钟前`;
    } else {
        return '刚刚';
    }
}

/**
 * 防抖函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 节流函数
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// 导出全局函数
window.openSidebar = openSidebar;
window.closeSidebar = closeSidebar;
window.showToast = showToast;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.formatDate = formatDate;
window.formatRelativeTime = formatRelativeTime;

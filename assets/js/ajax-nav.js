/**
 * AJAX 无刷新导航
 * 切换页面时保持顶部导航栏（含音乐播放器）不销毁，实现音乐跨页连续播放
 */
(function() {
    'use strict';

    var isNavigating = false;
    var currentUrl = window.location.href;

    function init() {
        bindLinks();
        bindPopState();
    }

    function bindLinks() {
        document.addEventListener('click', function(e) {
            var link = e.target.closest('a');
            if (!link) return;

            var href = link.getAttribute('href');
            if (!href) return;

            if (link.target === '_blank') return;
            if (link.hasAttribute('download')) return;
            if (link.closest('[data-no-ajax]')) return;

            if (href.startsWith('http://') || href.startsWith('https://')) {
                try {
                    if (new URL(href, location.href).origin !== location.origin) {
                        return;
                    }
                } catch (err) {
                    return;
                }
            }

            if (href.startsWith('#') || href.startsWith('javascript:')) return;

            if (href.startsWith('/admin') || href.startsWith('/login') || 
                href.startsWith('/register') || href.startsWith('/install.php') ||
                href.startsWith('/api/')) return;

            e.preventDefault();
            e.stopPropagation();
            navigateTo(link.href);
        }, true);

        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.tagName !== 'FORM') return;
            if (form.method && form.method.toUpperCase() === 'POST') return;
            if (form.hasAttribute('data-no-ajax')) return;
            
            var action = form.getAttribute('action') || window.location.pathname;
            if (action.startsWith('/admin') || action.startsWith('/login') ||
                action.startsWith('/api/')) return;
        });
    }

    function bindPopState() {
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.url) {
                loadPage(e.state.url, false);
            }
        });
    }

    function navigateTo(url) {
        if (isNavigating) return;
        if (url === currentUrl) return;

        loadPage(url, true);
    }

    function loadPage(url, pushState) {
        isNavigating = true;
        showLoading();

        fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        })
        .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');

            var newTitle = doc.querySelector('title');
            if (newTitle) {
                document.title = newTitle.textContent;
            }

            var newMain = doc.querySelector('#appMain');
            var oldMain = document.querySelector('#appMain');

            if (newMain && oldMain) {
                oldMain.innerHTML = newMain.innerHTML;

                executeScripts(oldMain);

                window.scrollTo(0, 0);

                if (pushState) {
                    history.pushState({ url: url }, '', url);
                }
                currentUrl = url;

                updateActiveNav(url);

                setTimeout(function() {
                    hideLoading();
                    isNavigating = false;
                    var event = new CustomEvent('ajaxPageLoaded', { detail: { url: url } });
                    window.dispatchEvent(event);
                }, 100);
            } else {
                window.location.href = url;
            }
        })
        .catch(function() {
            window.location.href = url;
        });
    }

    function executeScripts(container) {
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function(oldScript) {
            var newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function updateActiveNav(url) {
        var path = new URL(url, location.origin).pathname;
        var queryString = new URL(url, location.origin).search;
        var params = new URLSearchParams(queryString);
        var slug = params.get('slug');

        document.querySelectorAll('.nav-menu a, .nav-bottom-item, .sidebar-menu a').forEach(function(link) {
            link.classList.remove('active');
        });

        function isLinkActive(linkHref) {
            var linkPath = new URL(linkHref, location.origin).pathname;
            var linkQuery = new URL(linkHref, location.origin).search;
            var linkParams = new URLSearchParams(linkQuery);
            var linkSlug = linkParams.get('slug');

            if (path === '/' || path === '/index.php') {
                return linkPath === '/' || linkPath === '/index.php';
            }

            if (path.indexOf('/category.php') !== -1) {
                if (linkPath.indexOf('/category.php') !== -1) {
                    if (!slug && !linkSlug) return true;
                    if (slug === linkSlug) return true;
                    if (!slug && linkSlug === 'default') return true;
                }
                return false;
            }

            return linkPath === path;
        }

        document.querySelectorAll('.nav-menu a').forEach(function(link) {
            var href = link.getAttribute('href');
            if (!href) return;
            if (isLinkActive(href)) {
                link.classList.add('active');
            }
        });

        document.querySelectorAll('.nav-bottom-item').forEach(function(link) {
            var href = link.getAttribute('href');
            if (!href) return;
            if (isLinkActive(href)) {
                link.classList.add('active');
            }
        });

        document.querySelectorAll('.sidebar-menu a').forEach(function(link) {
            var href = link.getAttribute('href');
            if (!href) return;
            if (isLinkActive(href)) {
                link.classList.add('active');
            }
        });
    }

    function showLoading() {
        var overlay = document.getElementById('ajaxLoadingOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'ajaxLoadingOverlay';
            overlay.className = 'ajax-loading-overlay';
            overlay.innerHTML = '<img class="loading-gif" src="/img/jz/emotion_download_1781406778374.gif" alt="加载中">';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }

    function hideLoading() {
        var overlay = document.getElementById('ajaxLoadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.ajaxNavigate = navigateTo;
})();

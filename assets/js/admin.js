/**
 * 管理后台脚本
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化管理后台
    initAdminSidebar();
    initAdminForms();
    initImageUpload();
});

/**
 * 初始化侧边栏
 */
function initAdminSidebar() {
    var menuBtn = document.getElementById('adminMenuBtn');
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminSidebarOverlay');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // 侧边栏内链接点击后自动关闭（移动端）
    if (sidebar) {
        var links = sidebar.querySelectorAll('a');
        links.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
    }

    // 旧版兼容
    var toggleBtn = document.querySelector('.admin-sidebar-toggle');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }
}

/**
 * 初始化图片上传
 */
function initImageUpload() {
    // 封面图片上传区域
    initCoverImageUpload();

    // 内容图片上传区域
    initContentImageUpload();
}

/**
 * 封面图片上传
 */
function initCoverImageUpload() {
    const dropZone = document.getElementById('cover-drop-zone');
    const fileInput = document.getElementById('cover-file-input');
    const urlInput = document.getElementById('cover-image-url');
    const preview = document.getElementById('cover-preview');

    if (!dropZone) return;

    // 点击区域打开文件选择
    dropZone.addEventListener('click', function(e) {
        if (e.target.closest('.preview-container')) return;
        if (fileInput) fileInput.click();
    });

    // 阻止默认拖拽行为
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    // 拖拽进入/悬停
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, function() {
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function() {
            dropZone.classList.remove('dragover');
        });
    });

    // 处理拖放
    dropZone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            uploadCoverImage(files[0]);
        }
    });

    // 文件选择
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                uploadCoverImage(e.target.files[0]);
            }
        });
    }

    // 上传封面图片
    function uploadCoverImage(file) {
        if (!file.type.startsWith('image/')) {
            showToast('请上传图片文件', 'error');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            showToast('图片大小不能超过10MB', 'error');
            return;
        }

        // 显示预览
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                const img = preview.querySelector('img');
                if (img) img.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);

        // 上传到服务器
        const formData = new FormData();
        formData.append('image', file);

        showToast('正在上传...', 'info');

        fetch('/api/articles/upload-image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (urlInput) urlInput.value = data.url;
                showToast('封面图上传成功', 'success');
            } else {
                showToast(data.message || '上传失败', 'error');
                if (preview) preview.style.display = 'none';
            }
        })
        .catch(error => {
            showToast('上传失败，请稍后重试', 'error');
            if (preview) preview.style.display = 'none';
        });
    }
}

/**
 * 移除封面图片
 */
function removeCoverImage(e) {
    e.stopPropagation();
    const urlInput = document.getElementById('cover-image-url');
    const preview = document.getElementById('cover-preview');

    if (urlInput) urlInput.value = '';
    if (preview) preview.style.display = 'none';
    showToast('已移除封面图', 'info');
}

/**
 * 内容图片上传
 */
function initContentImageUpload() {
    const dropZone = document.getElementById('content-drop-zone');
    const fileInput = document.getElementById('content-file-input');
    const contentEditor = document.getElementById('content-editor');
    const previewContainer = document.getElementById('uploaded-images-preview');

    if (!dropZone || !contentEditor) return;

    // 点击按钮选择文件
    const uploadBtn = document.getElementById('content-upload-btn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (fileInput) fileInput.click();
        });
    }

    // 阻止默认拖拽行为
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    // 拖拽视觉效果
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, function() {
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function() {
            dropZone.classList.remove('dragover');
        });
    });

    // 处理拖放
    dropZone.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleContentImages(Array.from(files));
        }
    });

    // 文件选择
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleContentImages(Array.from(e.target.files));
            }
        });
    }

    // 处理多张内容图片
    function handleContentImages(files) {
        files.forEach(file => {
            if (!file.type.startsWith('image/')) {
                showToast(`${file.name} 不是图片文件`, 'error');
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                showToast(`${file.name} 大小超过10MB`, 'error');
                return;
            }

            // 添加到预览
            const previewItem = document.createElement('div');
            previewItem.className = 'uploaded-image-item';
            previewItem.innerHTML = '<div class="uploaded-image-item-overlay"><span>上传中...</span></div>';

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                previewItem.insertBefore(img, previewItem.firstChild);
            };
            reader.readAsDataURL(file);

            if (previewContainer) {
                previewContainer.appendChild(previewItem);
            }

            // 上传到服务器
            const formData = new FormData();
            formData.append('image', file);

            fetch('/api/articles/upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 插入URL到内容编辑器
                    const currentValue = contentEditor.value;
                    const newLine = currentValue && !currentValue.endsWith('\n') ? '\n' : '';
                    contentEditor.value = currentValue + newLine + data.url + '\n';

                    // 更新预览项
                    if (previewItem) {
                        previewItem.innerHTML = `
                            <img src="${data.url}" alt="">
                            <div class="uploaded-image-item-overlay">
                                <span style="color:#fff;">✓ 已插入</span>
                            </div>
                        `;
                    }
                    showToast('图片上传成功', 'success');
                } else {
                    if (previewItem) previewItem.remove();
                    showToast(data.message || '上传失败', 'error');
                }
            })
            .catch(error => {
                if (previewItem) previewItem.remove();
                showToast('上传失败，请稍后重试', 'error');
            });
        });
    }
}

/**
 * 初始化表单
 */
function initAdminForms() {
    // 文章编辑表单
    const articleForm = document.getElementById('article-form');
    if (articleForm) {
        articleForm.addEventListener('submit', handleArticleSubmit);
    }

    // 分类表单
    const categoryForm = document.getElementById('category-form');
    if (categoryForm) {
        categoryForm.addEventListener('submit', handleCategorySubmit);
    }

    // 设置表单
    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', handleSettingsSubmit);
    }
}

/**
 * 处理文章提交
 */
async function handleArticleSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // 获取富文本编辑器内容
    const contentEditor = document.getElementById('content-editor');
    if (contentEditor) {
        data.content = contentEditor.value;
    }

    // 转换复选框
    data.published = formData.has('published');

    try {
        const isEdit = data.id ? true : false;
        const url = isEdit ? '/api/articles/update.php' : '/api/articles/create.php';

        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast(isEdit ? '文章更新成功' : '文章创建成功', 'success');
            setTimeout(() => {
                window.location.href = '/admin/articles.php';
            }, 1000);
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (error) {
        showToast('请求失败，请稍后重试', 'error');
    }
}

/**
 * 处理分类提交
 */
async function handleCategorySubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const isEdit = data.id ? true : false;
        const url = isEdit ? '/api/categories/update.php' : '/api/categories/create.php';

        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast(isEdit ? '分类更新成功' : '分类创建成功', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (error) {
        showToast('请求失败，请稍后重试', 'error');
    }
}

/**
 * 处理设置提交
 */
async function handleSettingsSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/api/settings/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('设置保存成功', 'success');
        } else {
            showToast(result.message || '保存失败', 'error');
        }
    } catch (error) {
        showToast('请求失败，请稍后重试', 'error');
    }
}

/**
 * 删除文章
 */
async function deleteArticle(id) {
    if (!confirm('确定要删除这篇文章吗？此操作不可撤销。')) {
        return;
    }

    try {
        const response = await fetch('/api/articles/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.success) {
            showToast('文章删除成功', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message || '删除失败', 'error');
        }
    } catch (error) {
        showToast('请求失败，请稍后重试', 'error');
    }
}

/**
 * 删除分类
 */
async function deleteCategory(id) {
    if (!confirm('确定要删除这个分类吗？该分类下的文章将变为未分类状态。')) {
        return;
    }

    try {
        const response = await fetch('/api/categories/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.success) {
            showToast('分类删除成功', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(result.message || '删除失败', 'error');
        }
    } catch (error) {
        showToast('请求失败，请稍后重试', 'error');
    }
}

// 导出全局函数
window.deleteArticle = deleteArticle;
window.deleteCategory = deleteCategory;
window.removeCoverImage = removeCoverImage;

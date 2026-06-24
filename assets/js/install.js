/**
 * 安装引导页脚本
 */

document.addEventListener('DOMContentLoaded', function() {
    initInstallWizard();
});

function initInstallWizard() {
    const dbTypeSelect = document.getElementById('db_type');
    if (dbTypeSelect) {
        dbTypeSelect.addEventListener('change', function() {
            updateDatabaseFields(this.value);
            saveDatabaseConfig();
        });
    }

    const testDbBtn = document.getElementById('test-db-btn');
    if (testDbBtn) {
        testDbBtn.addEventListener('click', testDatabaseConnection);
    }

    const installBtn = document.getElementById('install-btn');
    if (installBtn) {
        installBtn.addEventListener('click', performInstall);
    }

    const databaseInputs = document.querySelectorAll('#database-form input, #database-form select');
    databaseInputs.forEach(input => {
        input.addEventListener('change', saveDatabaseConfig);
        input.addEventListener('input', saveDatabaseConfig);
    });

    loadDatabaseConfig();
}

function saveDatabaseConfig() {
    const config = {
        type: document.getElementById('db_type')?.value || 'sqlite',
        host: document.getElementById('db_host')?.value || '',
        port: document.getElementById('db_port')?.value || '',
        username: document.getElementById('db_username')?.value || '',
        password: document.getElementById('db_password')?.value || '',
        database: document.getElementById('db_name')?.value || '',
        path: document.getElementById('db_path')?.value || 'data/blog.db'
    };
    localStorage.setItem('blog_install_db_config', JSON.stringify(config));
}

function loadDatabaseConfig() {
    const saved = localStorage.getItem('blog_install_db_config');
    if (!saved) return;

    try {
        const config = JSON.parse(saved);
        if (document.getElementById('db_type')) {
            document.getElementById('db_type').value = config.type || 'sqlite';
            updateDatabaseFields(config.type || 'sqlite');
        }
        if (document.getElementById('db_host') && config.host) {
            document.getElementById('db_host').value = config.host;
        }
        if (document.getElementById('db_port') && config.port) {
            document.getElementById('db_port').value = config.port;
        }
        if (document.getElementById('db_username') && config.username) {
            document.getElementById('db_username').value = config.username;
        }
        if (document.getElementById('db_password') && config.password) {
            document.getElementById('db_password').value = config.password;
        }
        if (document.getElementById('db_name') && config.database) {
            document.getElementById('db_name').value = config.database;
        }
        if (document.getElementById('db_path') && config.path) {
            document.getElementById('db_path').value = config.path;
        }
    } catch (e) {
        console.error('加载数据库配置失败', e);
    }
}

function getDatabaseConfig() {
    const saved = localStorage.getItem('blog_install_db_config');
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {}
    }
    return {
        type: document.getElementById('db_type')?.value || 'sqlite',
        host: document.getElementById('db_host')?.value || 'localhost',
        port: document.getElementById('db_port')?.value || '',
        username: document.getElementById('db_username')?.value || '',
        password: document.getElementById('db_password')?.value || '',
        database: document.getElementById('db_name')?.value || '',
        path: document.getElementById('db_path')?.value || 'data/blog.db'
    };
}

function updateDatabaseFields(dbType) {
    const mysqlFields = document.querySelectorAll('.mysql-fields');
    const sqliteFields = document.querySelectorAll('.sqlite-fields');
    const mongodbFields = document.querySelectorAll('.mongodb-fields');

    mysqlFields.forEach(el => el.style.display = 'none');
    sqliteFields.forEach(el => el.style.display = 'none');
    mongodbFields.forEach(el => el.style.display = 'none');

    switch (dbType) {
        case 'mysql':
            mysqlFields.forEach(el => el.style.display = 'block');
            break;
        case 'sqlite':
            sqliteFields.forEach(el => el.style.display = 'block');
            break;
        case 'mongodb':
            mongodbFields.forEach(el => el.style.display = 'block');
            break;
    }
}

async function testDatabaseConnection() {
    const btn = document.getElementById('test-db-btn');
    const resultEl = document.getElementById('db-test-result');

    const config = {
        type: document.getElementById('db_type').value,
        host: document.getElementById('db_host')?.value || 'localhost',
        port: parseInt(document.getElementById('db_port')?.value) || 3306,
        username: document.getElementById('db_username')?.value || '',
        password: document.getElementById('db_password')?.value || '',
        database: document.getElementById('db_name')?.value || '',
        path: document.getElementById('db_path')?.value || 'data/blog.db'
    };

    btn.disabled = true;
    btn.textContent = '测试中...';
    resultEl.innerHTML = '<span class="text-muted">正在测试连接...</span>';

    try {
        const response = await fetch('/api/install/test-db.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(config)
        });

        const result = await response.json();

        if (result.success) {
            resultEl.innerHTML = '<span class="text-success" style="color: #38a169;">✓ 数据库连接成功</span>';
        } else {
            resultEl.innerHTML = `<span class="text-error" style="color: #e53e3e;">✗ 连接失败: ${result.message}</span>`;
        }
    } catch (error) {
        resultEl.innerHTML = `<span class="text-error" style="color: #e53e3e;">✗ 请求失败: ${error.message}</span>`;
    } finally {
        btn.disabled = false;
        btn.textContent = '测试连接';
    }
}

async function performInstall() {
    const btn = document.getElementById('install-btn');
    const statusEl = document.getElementById('install-status');
    const modeEl = document.getElementById('install-mode');
    const isResetMode = modeEl && modeEl.value === 'reset';

    const adminUsername = document.getElementById('admin_username').value;
    const adminEmail = document.getElementById('admin_email').value;
    const adminPassword = document.getElementById('admin_password').value;
    const adminPasswordConfirm = document.getElementById('admin_password_confirm').value;

    if (!adminUsername || !adminEmail || !adminPassword) {
        statusEl.innerHTML = '<p style="color: #e53e3e;">请填写所有必填项</p>';
        return;
    }

    if (adminPassword !== adminPasswordConfirm) {
        statusEl.innerHTML = '<p style="color: #e53e3e;">两次密码不一致</p>';
        return;
    }

    const dbConfig = getDatabaseConfig();

    const data = {
        mode: isResetMode ? 'reset' : 'normal',
        database: dbConfig,
        admin: {
            username: adminUsername,
            email: adminEmail,
            password: adminPassword
        },
        site: {
            name: document.getElementById('site_name')?.value || 'My Blog',
            description: document.getElementById('site_description')?.value || ''
        }
    };

    btn.disabled = true;
    btn.textContent = isResetMode ? '重新安装中...' : '安装中...';
    btn.style.cursor = 'not-allowed';

    statusEl.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div style="width: 24px; height: 24px; border: 3px solid #3182ce; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <span style="margin-left: 12px; color: #718096;">${isResetMode ? '正在重新安装，请稍候...' : '正在安装，请稍候...'}</span>
        </div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
    `;

    try {
        const response = await fetch('/api/install/setup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            localStorage.removeItem('blog_install_db_config');
            statusEl.innerHTML = `
                <div style="text-align: center; padding: 20px; background: #c6f6d5; border-radius: 8px;">
                    <svg width="48" height="48" fill="none" stroke="#38a169" viewBox="0 0 24 24" style="margin-bottom: 12px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 style="color: #22543d; margin-bottom: 8px;">${isResetMode ? '重新安装成功！' : '安装成功！'}</h3>
                    <p style="color: #276749; margin-bottom: 16px;">${isResetMode ? '数据库已重新配置并初始化完成。' : '您的博客系统已成功安装。'}</p>
                    <div>
                        <a href="/" style="display: inline-block; padding: 8px 16px; background: #3182ce; color: white; text-decoration: none; border-radius: 4px; margin-right: 8px;">访问首页</a>
                        <a href="/login.php" style="display: inline-block; padding: 8px 16px; background: white; color: #3182ce; border: 1px solid #3182ce; text-decoration: none; border-radius: 4px;">登录后台</a>
                    </div>
                </div>
            `;
        } else {
            statusEl.innerHTML = `
                <div style="padding: 16px; background: #fed7d7; border-radius: 8px;">
                    <h4 style="color: #c53030; margin-bottom: 8px;">安装失败</h4>
                    <p style="color: #c53030;">${result.message}</p>
                    <button style="margin-top: 12px; padding: 6px 12px; background: white; border: 1px solid #e53e3e; color: #e53e3e; border-radius: 4px; cursor: pointer;" onclick="location.reload()">重试</button>
                </div>
            `;
        }
    } catch (error) {
        statusEl.innerHTML = `
            <div style="padding: 16px; background: #fed7d7; border-radius: 8px;">
                <h4 style="color: #c53030; margin-bottom: 8px;">安装失败</h4>
                <p style="color: #c53030;">请求错误: ${error.message}</p>
                <button style="margin-top: 12px; padding: 6px 12px; background: white; border: 1px solid #e53e3e; color: #e53e3e; border-radius: 4px; cursor: pointer;" onclick="location.reload()">重试</button>
            </div>
        `;
    } finally {
        btn.disabled = false;
        btn.textContent = isResetMode ? '重新安装' : '开始安装';
        btn.style.cursor = 'pointer';
    }
}

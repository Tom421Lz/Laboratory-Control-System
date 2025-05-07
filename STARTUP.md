# 实验室管理系统启动说明

## 系统要求

- PHP 8.0 或更高版本
- MySQL 5.7 或更高版本
- Node.js 16 或更高版本
- Composer
- npm 或 yarn

## 后端启动步骤

1. 克隆项目到本地
```bash
git clone [项目地址]
cd Laboratory-Control-System
```

2. 安装 PHP 依赖
```bash
composer install
```

3. 配置环境变量
```bash
cp .env.example .env
```
然后编辑 `.env` 文件，配置数据库连接信息和其他必要的环境变量。

4. 生成应用密钥
```bash
php artisan key:generate
```

5. 运行数据库迁移
```bash
php artisan migrate
```

6. 启动后端服务
```bash
php artisan serve
```
后端服务将在 http://localhost:8000 运行

## 前端启动步骤

1. 进入前端目录
```bash
cd frontend
```

2. 安装依赖
```bash
npm install
```

3. 配置开发环境
创建 `.env.development` 文件，添加以下内容：
```
VITE_API_BASE_URL=http://localhost:8000
```

4. 启动开发服务器
```bash
npm run dev
```
前端开发服务器将在 http://localhost:5173 运行

## 访问系统

1. 打开浏览器访问 http://localhost:5173
2. 使用默认管理员账号登录：
   - 用户名：admin
   - 密码：admin123

## 常见问题

1. 如果遇到跨域问题，请确保后端已正确配置 CORS。

2. 如果数据库连接失败，请检查：
   - 数据库服务是否启动
   - 数据库连接信息是否正确
   - 数据库用户权限是否正确

3. 如果前端无法连接后端，请检查：
   - 后端服务是否正常运行
   - 环境变量中的 API 地址是否正确
   - 网络连接是否正常

## 生产环境部署

1. 构建前端
```bash
cd frontend
npm run build
```

2. 配置生产环境
```bash
cp .env.example .env.production
```
编辑 `.env.production` 文件，配置生产环境变量。

3. 配置 Web 服务器（Apache/Nginx）指向 `frontend/dist` 目录

4. 确保后端服务正常运行并配置正确的 CORS 设置

## 安全建议

1. 生产环境中请修改默认的管理员密码
2. 确保 JWT 密钥足够复杂
3. 定期备份数据库
4. 配置适当的文件权限
5. 启用 HTTPS 
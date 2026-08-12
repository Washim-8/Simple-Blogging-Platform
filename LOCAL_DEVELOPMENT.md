# 🛠 Local Development Guide

This guide helps you set up and run the Simple Blog Platform on your local machine for development and testing.

---

## 📋 Prerequisites

- **PHP 8.0+** with PDO and PostgreSQL extensions
- **PostgreSQL 12+** database
- **Composer** (optional, for future dependencies)
- **Docker** (optional, for containerized development)
- **Git** for version control

---

## 🚀 Quick Start (Without Docker)

### 1. Install PHP and PostgreSQL

#### Windows (XAMPP or standalone)
```bash
# Download PHP 8.2 from php.net
# Download PostgreSQL from postgresql.org
# Install both and add to PATH
```

#### macOS (Homebrew)
```bash
brew install php@8.2
brew install postgresql@15
brew services start postgresql@15
```

#### Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install php8.2 php8.2-pgsql php8.2-pdo postgresql postgresql-contrib
sudo systemctl start postgresql
```

### 2. Verify Installations

```bash
php -v
# PHP 8.2.x

psql --version
# psql (PostgreSQL) 15.x

php -m | grep pdo_pgsql
# pdo_pgsql
```

### 3. Clone Repository

```bash
git clone https://github.com/Washim-8/Simple-Blogging-Platform.git
cd Simple-Blogging-Platform
```

### 4. Create PostgreSQL Database

```bash
# Connect to PostgreSQL
psql -U postgres

# In psql prompt:
CREATE DATABASE blog_db;
\q
```

### 5. Initialize Database Schema

```bash
psql -U postgres -d blog_db -f db/schema.sql
```

**Optional:** Load sample data:
```bash
psql -U postgres -d blog_db -f db/seed.sql
```

Verify tables created:
```bash
psql -U postgres -d blog_db -c "\dt"
```

### 6. Configure Environment

Copy example env file:
```bash
cp .env.example .env
```

Edit `.env`:
```env
DATABASE_URL=postgres://postgres:yourpassword@localhost:5432/blog_db

# Or use individual variables:
DB_HOST=localhost
DB_PORT=5432
DB_NAME=blog_db
DB_USER=postgres
DB_PASSWORD=yourpassword
```

### 7. Start PHP Development Server

```bash
php -S localhost:8000
```

### 8. Access Application

Open browser: **http://localhost:8000**

---

## 🐳 Docker Development

### 1. Build Docker Image

```bash
docker build -t simple-blog-platform .
```

### 2. Run PostgreSQL in Docker

```bash
docker run --name blog-postgres \
  -e POSTGRES_DB=blog_db \
  -e POSTGRES_USER=postgres \
  -e POSTGRES_PASSWORD=postgres \
  -p 5432:5432 \
  -d postgres:15
```

### 3. Initialize Database

```bash
# Wait for PostgreSQL to start (10 seconds)
sleep 10

# Copy schema to container
docker cp db/schema.sql blog-postgres:/schema.sql

# Execute schema
docker exec blog-postgres psql -U postgres -d blog_db -f /schema.sql

# Optional: Load seed data
docker cp db/seed.sql blog-postgres:/seed.sql
docker exec blog-postgres psql -U postgres -d blog_db -f /seed.sql
```

### 4. Run Application Container

```bash
docker run --name blog-app \
  -p 8080:80 \
  -e DATABASE_URL="postgres://postgres:postgres@host.docker.internal:5432/blog_db" \
  -d simple-blog-platform
```

**Note:** Use `host.docker.internal` on Mac/Windows or `172.17.0.1` on Linux to connect from container to host PostgreSQL.

### 5. Access Application

Open browser: **http://localhost:8080**

### 6. View Logs

```bash
docker logs -f blog-app
```

### 7. Stop Containers

```bash
docker stop blog-app blog-postgres
docker rm blog-app blog-postgres
```

---

## 🔧 Docker Compose (Recommended)

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  postgres:
    image: postgres:15
    container_name: blog-postgres
    environment:
      POSTGRES_DB: blog_db
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./db/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
      - ./db/seed.sql:/docker-entrypoint-initdb.d/02-seed.sql
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5

  web:
    build: .
    container_name: blog-app
    ports:
      - "8080:80"
    environment:
      DATABASE_URL: postgres://postgres:postgres@postgres:5432/blog_db
    depends_on:
      postgres:
        condition: service_healthy

volumes:
  postgres_data:
```

### Run with Docker Compose

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop all services
docker-compose down

# Stop and remove volumes (deletes database data)
docker-compose down -v
```

---

## 📝 Development Workflow

### Making Changes

1. **Edit PHP files** in `src/` or `include/`
2. **Edit frontend** in `assets/`
3. **Refresh browser** to see changes
4. **No rebuild needed** for PHP development server

### Database Changes

1. **Create migration file:** `db/migrations/001_add_column.sql`
   ```sql
   ALTER TABLE posts ADD COLUMN author VARCHAR(100);
   ```

2. **Apply migration:**
   ```bash
   psql -U postgres -d blog_db -f db/migrations/001_add_column.sql
   ```

3. **Update application code** to use new schema

### Testing CRUD Operations

1. **Create Post:**
   - Click "Create New Post"
   - Fill form and save
   - Verify appears in list

2. **Edit Post:**
   - Click edit icon on post card
   - Modify and save
   - Verify changes appear

3. **Delete Post:**
   - Click delete icon
   - Confirm deletion
   - Verify post removed

4. **Search:**
   - Type in search bar
   - Verify filtering works

---

## 🧪 Testing

### Manual Testing

```bash
# Check database connection
php -r "
\$pdo = new PDO('pgsql:host=localhost;dbname=blog_db', 'postgres', 'yourpassword');
echo 'Connection successful!';
"

# Count posts
psql -U postgres -d blog_db -c "SELECT COUNT(*) FROM posts;"

# View recent posts
psql -U postgres -d blog_db -c "SELECT id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 5;"
```

### Browser Testing

1. Open DevTools (F12)
2. Check **Console** for JavaScript errors
3. Check **Network** tab for AJAX requests
4. Test responsive design (mobile/tablet/desktop)

---

## 🔍 Debugging

### Enable PHP Error Reporting

Edit `include/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check PHP Logs

```bash
# PHP built-in server logs
# Errors appear in terminal where server was started

# Apache logs (if using Apache)
tail -f /var/log/apache2/error.log
```

### Check PostgreSQL Logs

```bash
# Find log location
psql -U postgres -c "SHOW log_directory;"
psql -U postgres -c "SHOW log_filename;"

# View logs
tail -f /path/to/postgresql.log
```

### Test Database Connection

Create `test_db.php`:
```php
<?php
require_once 'include/db.php';

try {
    $pdo = getConnection();
    echo "✓ Database connection successful!\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $result = $stmt->fetch();
    echo "✓ Found {$result['count']} posts\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

Run:
```bash
php test_db.php
```

---

## 🛠 Common Issues

### "PDO driver not found"

**Solution:** Install PostgreSQL PDO extension

```bash
# Ubuntu/Debian
sudo apt install php8.2-pgsql

# macOS
brew install php@8.2 --with-pgsql

# Then restart PHP
```

### "Connection refused to localhost:5432"

**Solution:** PostgreSQL not running

```bash
# Check status
sudo systemctl status postgresql  # Linux
brew services list                # macOS

# Start PostgreSQL
sudo systemctl start postgresql   # Linux
brew services start postgresql    # macOS
```

### "FATAL: role 'postgres' does not exist"

**Solution:** Create postgres user

```bash
# Linux
sudo -u postgres createuser --superuser $USER

# macOS (already created by default)
```

### "AJAX requests failing"

**Solution:** Check browser console

1. Open DevTools (F12) → Console
2. Look for CORS errors
3. Verify request URL is correct
4. Check Network tab for 404/500 errors

---

## 📦 Installing Dependencies

### PHP Extensions (if missing)

```bash
# Ubuntu/Debian
sudo apt install php8.2-pdo php8.2-pgsql php8.2-mbstring php8.2-curl

# macOS
brew install php@8.2

# Windows
# Download and install from php.net
# Enable extensions in php.ini:
extension=pdo_pgsql
extension=pgsql
```

### PostgreSQL Client Tools

```bash
# Ubuntu/Debian
sudo apt install postgresql-client

# macOS
brew install libpq

# Windows
# Included with PostgreSQL installation
```

---

## 🎨 Frontend Development

### Live Reload (Optional)

Install browser extension:
- **Chrome:** LiveReload
- **Firefox:** LiveReload

Or use a tool like:
```bash
npm install -g browser-sync
browser-sync start --proxy "localhost:8000" --files "assets/**/*,*.php"
```

### CSS/JS Changes

- Edit `assets/css/style.css`
- Edit `assets/js/script.js`
- Refresh browser (Ctrl+F5 for hard refresh)

---

## 📊 Database Management

### Useful PostgreSQL Commands

```bash
# Connect to database
psql -U postgres -d blog_db

# List tables
\dt

# Describe table
\d posts

# View all posts
SELECT * FROM posts;

# Count posts
SELECT COUNT(*) FROM posts;

# Search posts
SELECT * FROM posts WHERE title ILIKE '%web%';

# Delete all posts (careful!)
TRUNCATE TABLE posts RESTART IDENTITY CASCADE;

# Exit
\q
```

### Backup Database

```bash
# Backup
pg_dump -U postgres blog_db > backup.sql

# Restore
psql -U postgres -d blog_db < backup.sql
```

---

## 🔐 Security for Development

- Never commit `.env` file
- Use strong database passwords
- Don't expose database to internet
- Keep dependencies updated
- Test with different user inputs

---

## ✅ Development Checklist

- [ ] PHP 8.2+ installed
- [ ] PostgreSQL running
- [ ] Database created and initialized
- [ ] Environment variables configured
- [ ] Application starts without errors
- [ ] Can create posts
- [ ] Can edit posts
- [ ] Can delete posts
- [ ] Search works
- [ ] Dark mode toggles
- [ ] Responsive on mobile

---

## 📚 Additional Resources

- [PHP Manual](https://www.php.net/manual/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [jQuery API](https://api.jquery.com/)

---

**Happy Coding! 🚀**

If you encounter issues, check:
1. PHP error logs
2. PostgreSQL logs
3. Browser console
4. Network tab in DevTools

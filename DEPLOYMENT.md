# 🚀 Deployment Guide - Simple Blog Platform

This guide provides detailed instructions for deploying the Simple Blog Platform to Render with persistent PostgreSQL storage.

---

## 📋 Prerequisites

- GitHub account with repository access
- Render account ([render.com](https://render.com))
- PostgreSQL client (psql) for database initialization
- Git installed locally

---

## 🎯 Deployment Architecture

```
GitHub Repository
       ↓
Render Web Service (Docker)
       ↓
Render PostgreSQL (Managed)
       ↓
Persistent Blog Data ✓
```

**Critical:** The PostgreSQL database exists **outside** the Docker container, ensuring data persistence across deployments.

---

## 📝 Step-by-Step Deployment

### Step 1: Prepare Your Repository

1. **Ensure all changes are committed:**
   ```bash
   git status
   git add .
   git commit -m "Upgrade to PostgreSQL with Render deployment"
   git push origin main
   ```

2. **Verify required files exist:**
   - ✓ `Dockerfile`
   - ✓ `.dockerignore`
   - ✓ `db/schema.sql`
   - ✓ `.env.example`
   - ✓ All PHP, HTML, CSS, JS files

---

### Step 2: Create Render PostgreSQL Database

1. **Log in to Render Dashboard**
   - Go to [https://dashboard.render.com/](https://dashboard.render.com/)

2. **Create New PostgreSQL Database**
   - Click **"New +"** button
   - Select **"PostgreSQL"**

3. **Configure Database:**
   ```
   Name: blog-db
   Database: blog_db
   User: (auto-generated)
   Region: Oregon (US West) or closest to you
   PostgreSQL Version: 15
   Plan: Free (for testing) or Starter
   ```

4. **Create Database**
   - Click **"Create Database"**
   - Wait for status to show **"Available"** (takes 1-2 minutes)

5. **Note Connection Details**
   - Internal Database URL: `postgres://user:pass@host/blog_db`
   - External Database URL: `postgres://user:pass@host/blog_db`

---

### Step 3: Initialize Database Schema

You have two options:

#### Option A: Using Render Shell (Recommended)

1. In Render dashboard, open your PostgreSQL database
2. Click **"Shell"** tab
3. Copy the contents of `db/schema.sql`
4. Paste into the shell and execute

#### Option B: Using Local psql

1. Copy the **External Database URL** from Render
2. Run from your local terminal:
   ```bash
   psql "postgres://user:password@hostname/blog_db" -f db/schema.sql
   ```

3. Verify tables created:
   ```bash
   psql "postgres://user:password@hostname/blog_db" -c "\dt"
   ```
   
   Expected output:
   ```
   List of relations
   Schema | Name  | Type  | Owner
   --------+-------+-------+-------
   public | posts | table | user
   ```

4. **Optional:** Load sample data:
   ```bash
   psql "postgres://user:password@hostname/blog_db" -f db/seed.sql
   ```

---

### Step 4: Create Render Web Service

1. **Start New Web Service**
   - Click **"New +"** → **"Web Service"**

2. **Connect GitHub Repository**
   - Select **"Build and deploy from a Git repository"**
   - Click **"Connect account"** if needed
   - Find repository: `Washim-8/Simple-Blogging-Platform`
   - Click **"Connect"**

3. **Configure Web Service:**
   ```
   Name: simple-blog-platform
   Region: Same as database (Oregon US West)
   Branch: main
   Root Directory: (leave empty)
   Runtime: Docker
   Dockerfile Path: ./Dockerfile
   Docker Command: (leave empty - Apache starts automatically)
   Plan: Free or Starter
   ```

4. **Advanced Settings** (scroll down):
   - Auto-Deploy: **Yes** (recommended)
   - Health Check Path: `/` (optional)

---

### Step 5: Configure Environment Variables

**Critical Step:** Connect the web service to the database.

1. In Web Service configuration, scroll to **"Environment Variables"**

2. Add environment variable:
   - Click **"Add Environment Variable"**
   - **Key:** `DATABASE_URL`
   - **Value:** Click **"Select Database"** dropdown
   - Select: `blog-db` (your PostgreSQL database)
   - This auto-fills the **Internal Database URL**

3. **Optional:** Add additional variables:
   ```
   Key: APP_ENV
   Value: production
   ```

4. **Important:** Do NOT manually type the database URL. Use "Select Database" to ensure proper internal networking.

---

### Step 6: Deploy Application

1. **Initiate Deployment**
   - Click **"Create Web Service"**
   - Render will:
     - Clone your repository
     - Build Docker image from Dockerfile
     - Install PHP + PostgreSQL extensions
     - Start Apache web server
     - Connect to PostgreSQL via `DATABASE_URL`

2. **Monitor Build Logs**
   - Watch the **"Logs"** tab
   - Look for:
     ```
     ==> Building image...
     ==> Installing PostgreSQL client...
     ==> Starting Apache...
     ==> Your service is live 🎉
     ```

3. **Deployment Time:** ~3-5 minutes for first deploy

4. **Check Status:**
   - Status should show: **"Live"** with green indicator
   - If failed, check logs for errors

---

### Step 7: Access Your Application

1. **Find Your URL**
   - Top of dashboard: `https://simple-blog-platform.onrender.com`
   - Click to open in browser

2. **First Visit**
   - Page should load (may take 10-30 seconds on free tier cold start)
   - If you loaded seed data, you'll see sample posts
   - If not, you'll see "No posts yet" message

3. **Test CRUD Operations:**
   - Click **"Create New Post"**
   - Fill in title and content
   - Click **"Save Post"**
   - ✓ Post appears in the list

---

### Step 8: Verify Data Persistence

**This is the critical test:**

1. **Create a test post** with title "Persistence Test"

2. **Verify in database:**
   ```bash
   psql "your-external-database-url" -c "SELECT title FROM posts WHERE title = 'Persistence Test';"
   ```
   
   Should return:
   ```
          title       
   -------------------
    Persistence Test
   (1 row)
   ```

3. **Force redeployment:**
   - Go to Render dashboard
   - Click **"Manual Deploy"** → **"Clear build cache & deploy"**
   - Wait for new deployment

4. **After redeployment:**
   - Open application URL again
   - ✓ **Verify "Persistence Test" post still exists**

5. **Success!** Data survived container replacement.

---

## 🔧 Troubleshooting

### Issue: "Unable to connect to database"

**Cause:** `DATABASE_URL` not set or incorrect.

**Solution:**
1. Check Web Service → Environment tab
2. Verify `DATABASE_URL` exists
3. Ensure you used "Select Database" not manual entry
4. Try clicking "Select Database" again to refresh URL
5. Redeploy

### Issue: "Table 'posts' doesn't exist"

**Cause:** Database schema not initialized.

**Solution:**
1. Connect to database using psql
2. Run: `psql "your-database-url" -f db/schema.sql`
3. Verify: `psql "your-database-url" -c "\dt"`

### Issue: "Docker build failed"

**Cause:** Dockerfile syntax error or missing dependencies.

**Solution:**
1. Check build logs in Render dashboard
2. Test Dockerfile locally:
   ```bash
   docker build -t test-blog .
   ```
3. Fix errors and push changes

### Issue: "500 Internal Server Error"

**Cause:** PHP error or database connection issue.

**Solution:**
1. Check Render logs: Logs tab → Filter by "error"
2. Common causes:
   - Database connection failed
   - PHP syntax error
   - Missing PDO extension
3. Test database connection:
   ```bash
   psql "your-database-url" -c "SELECT 1;"
   ```

### Issue: "Page loads but posts don't appear"

**Cause:** AJAX request failing or database empty.

**Solution:**
1. Open browser developer console (F12)
2. Check Network tab for failed requests
3. Check Console tab for JavaScript errors
4. Verify database has data:
   ```bash
   psql "your-database-url" -c "SELECT COUNT(*) FROM posts;"
   ```

### Issue: "Cold start takes 30+ seconds"

**Cause:** Free tier spins down after inactivity.

**Solution:**
- Upgrade to paid tier for always-on service
- Or accept cold start delay on free tier
- First request after inactivity will be slow

---

## 🔄 Updating Your Application

### Standard Update Process

1. **Make changes locally**
2. **Test locally** with Docker:
   ```bash
   docker build -t simple-blog-platform .
   docker run -p 8080:80 -e DATABASE_URL="your-local-db" simple-blog-platform
   ```

3. **Commit and push:**
   ```bash
   git add .
   git commit -m "Description of changes"
   git push origin main
   ```

4. **Auto-deployment:**
   - Render automatically detects push
   - Builds and deploys new version
   - Watch deployment in dashboard

### Database Schema Changes

**Important:** Be careful with schema changes in production.

1. **Test locally first**

2. **Create migration SQL:**
   ```sql
   -- Example: Add new column
   ALTER TABLE posts ADD COLUMN author VARCHAR(100);
   ```

3. **Apply to production database:**
   ```bash
   psql "production-database-url" -f migration.sql
   ```

4. **Then deploy application code** that uses new schema

---

## 📊 Monitoring

### Check Application Health

```bash
curl https://simple-blog-platform.onrender.com
```

### Check Database Connection

```bash
psql "your-database-url" -c "SELECT COUNT(*) FROM posts;"
```

### View Logs

- Render Dashboard → Your Web Service → **"Logs"** tab
- Filter by:
  - All logs
  - Error logs
  - Build logs

### Metrics (Paid Plans)

- CPU usage
- Memory usage
- Request count
- Response times

---

## 💰 Cost Considerations

### Free Tier Limits

**PostgreSQL:**
- Storage: 1 GB
- Expires: 90 days (must upgrade or migrate)
- Connections: 100

**Web Service:**
- CPU: Shared
- RAM: 512 MB
- Builds: Unlimited
- Bandwidth: 100 GB/month
- Spins down after inactivity

### When to Upgrade

Consider paid tier when:
- Need always-on service (no cold starts)
- Database > 1 GB
- High traffic (> 100 GB/month)
- Need custom domains
- Require faster builds

---

## 🔐 Security Checklist

- [ ] `DATABASE_URL` is environment variable (not hard-coded)
- [ ] `.env` file is in `.gitignore`
- [ ] No credentials committed to repository
- [ ] Database user has limited permissions (not superuser)
- [ ] HTTPS enabled (Render provides automatically)
- [ ] SQL injection protection via prepared statements
- [ ] XSS protection via input sanitization
- [ ] Error messages don't expose sensitive info

---

## 📚 Additional Resources

- [Render Documentation](https://render.com/docs)
- [Render PostgreSQL Guide](https://render.com/docs/databases)
- [Docker Documentation](https://docs.docker.com/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)

---

## ✅ Post-Deployment Checklist

- [ ] Application accessible at Render URL
- [ ] Can create new posts
- [ ] Can edit posts
- [ ] Can delete posts
- [ ] Search functionality works
- [ ] Dark mode toggles correctly
- [ ] Mobile responsive design works
- [ ] Data persists after redeployment
- [ ] Database connection secure
- [ ] No errors in browser console
- [ ] No errors in Render logs

---

## 🎉 Success!

Your Simple Blog Platform is now deployed on Render with persistent PostgreSQL storage. All blog data will survive application restarts, redeployments, and container recreation.

**Next Steps:**
- Share your application URL
- Monitor usage and logs
- Plan feature enhancements
- Consider custom domain (paid plans)

---

**Need Help?**
- Check Render Community: [community.render.com](https://community.render.com)
- Review logs for specific errors
- Contact: washimshaikh33@gmail.com

<div align="center">

# 📝 Simple Blog Platform

<p align="center">
  <img src="https://readme-typing-svg.herokuapp.com?size=22&duration=3000&color=0F766E&center=true&vCenter=true&width=750&lines=Production-Ready+Blogging+Platform;PHP+%2B+PostgreSQL+%2B+Docker;Deployed+on+Render+with+Persistent+Storage;Full+CRUD+with+Modern+UI" alt="Typing SVG" />
</p>

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Database-4169E1?style=for-the-badge&logo=postgresql)
![Docker](https://img.shields.io/badge/Docker-Container-2496ED?style=for-the-badge&logo=docker)
![Render](https://img.shields.io/badge/Render-Deployed-46E3B7?style=for-the-badge&logo=render)

</div>

---

## 📌 Overview

A professional, production-ready blogging platform built with PHP and PostgreSQL, designed for deployment on Render. This platform demonstrates modern web development practices with persistent database storage, ensuring **all blog data survives application restarts, redeployments, and container recreation**.

**Key Achievement:** Unlike file-based or container-stored data, this application uses **Render Managed PostgreSQL** as an external persistent database layer, making it truly production-ready.

---

## ✨ Features

- **📝 Complete CRUD Operations:** Create, read, update, and delete blog posts seamlessly
- **💾 Persistent Database Storage:** PostgreSQL ensures data survives application restarts and redeployments
- **⚡ AJAX-Powered UI:** Dynamic content updates without page reloads
- **🎨 Modern Responsive Design:** Clean, professional interface built with Bootstrap
- **🌙 Dark Mode:** Toggle between light and dark themes
- **🔍 Real-Time Search:** Instantly filter posts by title or content
- **💙 Like System:** Like posts with persistent local storage
- **📱 Mobile-Friendly:** Fully responsive across all devices
- **🔒 Secure:** SQL injection protection via prepared statements, XSS prevention
- **🐳 Docker-Ready:** Containerized for consistent deployment

---

## 🛠 Tech Stack

**Backend:**
- PHP 8.2
- PostgreSQL (via PDO)
- Prepared Statements (SQL injection protection)

**Frontend:**
- HTML5
- CSS3 (Custom variables for theming)
- JavaScript (ES6)
- jQuery
- AJAX
- Bootstrap 4
- Font Awesome

**DevOps:**
- Docker
- Render (Web Service + Managed PostgreSQL)
- Git/GitHub

**Development:**
- PDO with PostgreSQL
- Environment variable configuration
- RESTful API endpoints

---

## 🏗 Architecture

The application follows a clean separation of concerns with persistent external database storage:

```
┌─────────────────┐
│   GitHub Repo   │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│  Render Web Service     │
│  (Docker Container)     │
│  ┌─────────────────┐    │
│  │  Apache + PHP   │    │
│  │  Application    │    │
│  └────────┬────────┘    │
└───────────┼─────────────┘
            │
            │ PDO Connection
            │ (DATABASE_URL)
            ▼
┌──────────────────────────┐
│ Render PostgreSQL        │
│ (Managed Database)       │
│ ┌──────────────────────┐ │
│ │  posts table         │ │
│ │  - id (SERIAL)       │ │
│ │  - title (VARCHAR)   │ │
│ │  - content (TEXT)    │ │
│ │  - created_at        │ │
│ └──────────────────────┘ │
│                          │
│  ✓ Persistent Storage    │
│  ✓ Survives Restarts     │
│  ✓ External to Container │
└──────────────────────────┘
```

**Data Flow:**
```
User Action (Create Post)
    ↓
Frontend (AJAX Request)
    ↓
PHP Backend (post-handler.php)
    ↓
PDO (Prepared Statement)
    ↓
PostgreSQL Database
    ↓
Data Persisted ✓
```

---

## 📂 Project Structure

```
Simple-Blogging-Platform/
│
├── assets/
│   ├── css/
│   │   └── style.css           # Custom styles with CSS variables
│   └── js/
│       └── script.js           # AJAX and frontend logic
│
├── db/
│   ├── schema.sql              # PostgreSQL database schema
│   └── seed.sql                # Sample data (optional)
│
├── include/
│   ├── config.php              # Application configuration
│   └── db.php                  # Database connection & functions
│
├── src/
│   ├── get-posts.php           # GET endpoint for posts
│   └── post-handler.php        # POST endpoint for CRUD operations
│
├── index.php                   # Main application page
├── about_contact.php           # About & contact page
├── Dockerfile                  # Docker configuration
├── .dockerignore               # Docker ignore patterns
├── .gitignore                  # Git ignore patterns
├── .env.example                # Environment variable template
└── README.md                   # This file
```

---

## ⚙️ How It Works

### Data Persistence Architecture

**Critical Design Decision:** Blog data is stored in **Render Managed PostgreSQL**, which exists **outside** the Docker container.

```
Scenario 1: Application Restart
├─ Render restarts web service
├─ New PHP container starts
├─ Connects to SAME PostgreSQL database
└─ ✓ All posts remain available

Scenario 2: New Deployment
├─ Push code to GitHub
├─ Render builds new Docker image
├─ Old container replaced
├─ New container connects to SAME database
└─ ✓ All posts remain available

Scenario 3: Database Update
├─ User creates new post
├─ PHP inserts into PostgreSQL
├─ Data committed to database
├─ Container restarts
└─ ✓ New post still exists
```

### Request Flow

1. **User creates a blog post** via the UI
2. **JavaScript captures form data** and sends AJAX request
3. **PHP backend receives request** (`post-handler.php`)
4. **Server-side validation** sanitizes input
5. **PDO prepared statement** inserts data into PostgreSQL
6. **Database commits transaction** (data now persistent)
7. **Response sent to frontend** with success status
8. **UI dynamically updates** to show new post

---

## 🚀 Local Development

### Prerequisites

- Docker installed
- PostgreSQL database (local or remote)
- Git

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/Washim-8/Simple-Blogging-Platform.git
   cd Simple-Blogging-Platform
   ```

2. **Set up local PostgreSQL database**
   
   Create a database named `blog_db`:
   ```bash
   createdb blog_db
   ```
   
   Or via psql:
   ```sql
   CREATE DATABASE blog_db;
   ```

3. **Initialize database schema**
   ```bash
   psql -d blog_db -f db/schema.sql
   ```
   
   Optionally, load sample data:
   ```bash
   psql -d blog_db -f db/seed.sql
   ```

4. **Configure environment variables**
   
   Create a `.env` file (copy from `.env.example`):
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```env
   DATABASE_URL=postgres://username:password@localhost:5432/blog_db
   ```
   
   Or set individual variables:
   ```env
   DB_HOST=localhost
   DB_PORT=5432
   DB_NAME=blog_db
   DB_USER=postgres
   DB_PASSWORD=yourpassword
   ```

5. **Build and run with Docker**
   ```bash
   docker build -t simple-blog-platform .
   
   docker run -p 8080:80 \
     -e DATABASE_URL="postgres://username:password@host.docker.internal:5432/blog_db" \
     simple-blog-platform
   ```
   
   Or run without Docker using PHP's built-in server:
   ```bash
   php -S localhost:8000
   ```

6. **Access the application**
   
   Open your browser to:
   - Docker: `http://localhost:8080`
   - PHP server: `http://localhost:8000`

---

## 🌐 Render Deployment

### Step 1: Create Render PostgreSQL Database

1. Go to [Render Dashboard](https://dashboard.render.com/)
2. Click **"New +"** → **"PostgreSQL"**
3. Configure:
   - **Name:** `blog-db` (or your choice)
   - **Database:** `blog_db`
   - **User:** (auto-generated)
   - **Region:** Choose closest to your users
   - **Plan:** Free or Starter
4. Click **"Create Database"**
5. **Wait for database to be ready** (status: Available)

### Step 2: Initialize Database Schema

1. In Render dashboard, open your PostgreSQL database
2. Go to **"Shell"** or **"Connect"** tab
3. Copy the PSQL command and run locally:
   ```bash
   psql postgres://user:password@host/database < db/schema.sql
   ```
   
   Or use the Render shell directly to paste schema contents.

4. Optionally load sample data:
   ```bash
   psql postgres://user:password@host/database < db/seed.sql
   ```

### Step 3: Create Render Web Service

1. Click **"New +"** → **"Web Service"**
2. Connect your GitHub repository: `Washim-8/Simple-Blogging-Platform`
3. Configure:
   - **Name:** `simple-blog-platform`
   - **Region:** Same as database
   - **Branch:** `main`
   - **Runtime:** **Docker**
   - **Dockerfile Path:** `./Dockerfile`
   - **Plan:** Free or Starter

### Step 4: Configure Environment Variables

1. In the Web Service settings, go to **"Environment"** tab
2. Add environment variable:
   - **Key:** `DATABASE_URL`
   - **Value:** Click "Select Database" → Choose your PostgreSQL database
   - This automatically populates the **Internal Database URL**

3. Optionally add:
   - `APP_ENV=production`

### Step 5: Deploy

1. Click **"Create Web Service"**
2. Render will:
   - Clone your repository
   - Build the Docker image
   - Deploy the container
   - Connect to PostgreSQL via `DATABASE_URL`

3. **Monitor deployment logs** for any errors

4. Once deployed, access your application at:
   ```
   https://simple-blog-platform.onrender.com
   ```

### Step 6: Verify Persistence

**Test the persistence guarantee:**

1. Open your deployed application
2. Create a new blog post
3. Verify it appears in the post list
4. Go to Render dashboard → **"Manual Deploy"** → **"Clear build cache & deploy"**
5. Wait for redeployment
6. Open application again
7. **✓ Verify your post still exists**

This confirms data is stored in PostgreSQL, not the container.

---

## 🔒 Security Features

- **SQL Injection Protection:** All queries use PDO prepared statements with parameterized inputs
- **XSS Prevention:** User input sanitized with `htmlspecialchars()`
- **CSRF Protection:** Can be enhanced with token-based validation (future improvement)
- **Input Validation:** Server-side validation of all POST data
- **Error Handling:** Production mode hides sensitive error details
- **Secure Configuration:** No credentials committed to repository
- **Environment Variables:** Database credentials read from environment only

---

## 📊 Database Schema

### `posts` Table

| Column       | Type         | Constraints         | Description                    |
|------------- |------------- |-------------------- |------------------------------- |
| `id`         | SERIAL       | PRIMARY KEY         | Auto-incrementing post ID      |
| `title`      | VARCHAR(255) | NOT NULL            | Post title (max 255 chars)     |
| `content`    | TEXT         | NOT NULL            | Post content (unlimited)       |
| `created_at` | TIMESTAMP    | NOT NULL, DEFAULT NOW() | Creation timestamp      |

**Indexes:**
- `idx_posts_created_at` on `created_at DESC` for efficient sorting

---

## 🎯 API Endpoints

### GET `/src/get-posts.php`

Retrieve all posts or a single post by ID.

**Get all posts:**
```http
GET /src/get-posts.php
```

**Response:**
```json
[
  {
    "id": 1,
    "title": "Welcome to Simple Blog Platform",
    "content": "This is your first post...",
    "created_at": "2024-01-15 10:30:00"
  }
]
```

**Get single post:**
```http
GET /src/get-posts.php?id=1
```

**Response:**
```json
{
  "id": 1,
  "title": "Welcome to Simple Blog Platform",
  "content": "This is your first post...",
  "created_at": "2024-01-15 10:30:00"
}
```

### POST `/src/post-handler.php`

Handle create, update, and delete operations.

**Create post:**
```http
POST /src/post-handler.php
Content-Type: application/x-www-form-urlencoded

action=save&title=New Post&content=Post content here
```

**Update post:**
```http
POST /src/post-handler.php
Content-Type: application/x-www-form-urlencoded

action=save&id=1&title=Updated Title&content=Updated content
```

**Delete post:**
```http
POST /src/post-handler.php
Content-Type: application/x-www-form-urlencoded

action=delete&id=1
```

**Success Response:**
```json
{
  "success": true,
  "message": "Post created successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error: Title is required"
}
```

---

## 🧪 Testing

### Manual Testing Checklist

- [ ] Create a new blog post
- [ ] Edit an existing post
- [ ] Delete a post
- [ ] Search for posts
- [ ] Toggle dark mode
- [ ] Like a post
- [ ] Test responsive design on mobile
- [ ] Verify data persists after application restart
- [ ] Test with empty database
- [ ] Test error handling (invalid IDs, empty fields)

### Database Persistence Test

```bash
# 1. Create a post via UI
# 2. Connect to database and verify:
psql $DATABASE_URL -c "SELECT * FROM posts ORDER BY created_at DESC LIMIT 1;"

# 3. Restart application
# 4. Verify post still exists in UI
```

---

## 🚧 Future Improvements

- [ ] **User Authentication:** Login, registration, and session management
- [ ] **Role-Based Authorization:** Admin, author, and reader roles
- [ ] **Rich Text Editor:** WYSIWYG editor (Quill or TinyMCE)
- [ ] **Image Uploads:** Using cloud storage (AWS S3, Cloudinary)
- [ ] **Categories & Tags:** Post organization and filtering
- [ ] **Comments System:** User comments on posts
- [ ] **Pagination:** Load posts in batches
- [ ] **Advanced Search:** Full-text search with PostgreSQL
- [ ] **RESTful API:** JSON API for headless CMS
- [ ] **Automated Tests:** PHPUnit for backend testing
- [ ] **CI/CD Pipeline:** GitHub Actions for automated deployment
- [ ] **Rate Limiting:** Prevent abuse
- [ ] **Caching:** Redis for improved performance
- [ ] **Email Notifications:** Post updates and comments

---

## 👨‍💻 About the Developer

**Washim Shaikh** – Software Engineer

I'm passionate about building production-ready, scalable systems that solve real-world problems. This project demonstrates:
- Clean architecture and separation of concerns
- Persistent database design for production deployment
- Security best practices (prepared statements, input sanitization)
- Modern UI/UX with responsive design
- DevOps practices (Docker, environment configuration)

**Tech Stack Expertise:**
- Backend: PHP, Python, Java, Node.js
- Frontend: HTML, CSS, JavaScript, React
- Databases: PostgreSQL, MySQL, MongoDB
- DevOps: Docker, Git, Render, AWS
- AI/ML: Prompt Engineering, LLM Integration

---

## 📬 Contact

I'm always open to collaborations, interesting projects, and opportunities!

- **Email:** [washimshaikh33@gmail.com](mailto:washimshaikh33@gmail.com)
- **Phone:** +91 8884958185
- **GitHub:** [Washim-8](https://github.com/Washim-8)
- **LinkedIn:** [Washim Shaikh](https://www.linkedin.com/in/washim-shaikh-349868281/)

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Built with modern web development best practices
- Deployed on Render's excellent platform
- Designed for real-world production use
- Created to demonstrate full-stack PHP expertise

---

<div align="center">

### ⚡ Key Takeaway

**This application stores blog data in PostgreSQL, NOT in the Docker container filesystem.**

**Result:** Data persists across application restarts, redeployments, and container recreation.

✨ *Built to demonstrate production-ready PHP development with persistent storage.*

</div>

<?php
/**
 * Database Setup Script
 * 
 * This script automatically creates the database tables on first run.
 * Access this file once after deployment: https://your-app.onrender.com/setup-database.php
 */

// Prevent accidental re-runs
$setupFile = __DIR__ . '/.database_setup_complete';
if (file_exists($setupFile)) {
    die("✅ Database already set up! Delete .database_setup_complete file to run again.");
}

echo "<h1>Setting up database...</h1>";
echo "<pre>";

try {
    // Get database connection
    $databaseUrl = getenv('DATABASE_URL');
    
    if (!$databaseUrl) {
        echo "❌ ERROR: DATABASE_URL environment variable not found!\n\n";
        echo "Please follow these steps:\n";
        echo "1. Go to Render Dashboard\n";
        echo "2. Click on your Web Service (simple-blogging-platform)\n";
        echo "3. Click 'Environment' in left sidebar\n";
        echo "4. Click 'Add Environment Variable'\n";
        echo "5. Key: DATABASE_URL\n";
        echo "6. Value: Click 'Select Database' dropdown and choose 'blog-db'\n";
        echo "7. Click 'Save Changes'\n";
        echo "8. Wait for redeploy, then refresh this page\n";
        die();
    }
    
    echo "📡 DATABASE_URL found!\n";
    echo "🔗 URL: " . substr($databaseUrl, 0, 20) . "...\n\n";
    
    echo "📡 Connecting to database...\n";
    
    // Parse database URL - handle both postgres:// and postgresql://
    $databaseUrl = str_replace('postgres://', 'postgresql://', $databaseUrl);
    $dbParts = parse_url($databaseUrl);
    
    if (!$dbParts || !isset($dbParts['host'])) {
        echo "❌ ERROR: Invalid DATABASE_URL format!\n";
        echo "Current URL: " . $databaseUrl . "\n\n";
        echo "Expected format: postgres://user:password@host:port/database\n";
        die();
    }
    
    $host = $dbParts['host'];
    $port = isset($dbParts['port']) ? $dbParts['port'] : 5432;
    $dbname = ltrim($dbParts['path'], '/');
    $user = $dbParts['user'];
    $password = $dbParts['pass'] ?? '';
    
    echo "📊 Connection details:\n";
    echo "   Host: {$host}\n";
    echo "   Port: {$port}\n";
    echo "   Database: {$dbname}\n";
    echo "   User: {$user}\n\n";
    
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    
    // Create PDO connection
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30
    ]);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Create posts table
    echo "📝 Creating posts table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Posts table created!\n\n";
    
    // Create index
    echo "🔍 Creating index...\n";
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at DESC)
    ");
    echo "✅ Index created!\n\n";
    
    // Check if we should add sample data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "📚 Adding sample blog posts...\n";
        
        $samplePosts = [
            [
                'title' => 'Welcome to Simple Blog Platform',
                'content' => 'This is your first post on the Simple Blog Platform. You can edit or delete this post, or create new ones! This platform is designed to be simple yet powerful, allowing you to focus on what matters most: your content.',
                'days_ago' => 30
            ],
            [
                'title' => 'Getting Started with Web Development',
                'content' => 'Web development is an exciting field that combines creativity with technical skills. In this comprehensive guide, we will explore the fundamentals of HTML, CSS, and JavaScript. These three technologies form the backbone of every website you visit.',
                'days_ago' => 28
            ],
            [
                'title' => 'The Art of Minimalist Design',
                'content' => 'Minimalism in design is not just about removing elements; it is about adding value. When we strip away the unnecessary, what remains becomes more powerful. Every color, every line, every space serves a purpose.',
                'days_ago' => 25
            ],
            [
                'title' => 'Travel Diaries: Exploring Hidden Gems',
                'content' => 'Traveling opens your mind and soul to new experiences. Last summer, I discovered a small coastal town that changed my perspective on tourism. Instead of following the crowds to popular destinations, I wandered through narrow streets.',
                'days_ago' => 21
            ],
            [
                'title' => 'Mindfulness in the Digital Age',
                'content' => 'In our hyperconnected world, finding moments of peace has become increasingly challenging. Notifications constantly demand our attention, and the pressure to stay online never ends. However, practicing mindfulness can help us reclaim our mental space.',
                'days_ago' => 18
            ],
            [
                'title' => 'The Future of Artificial Intelligence',
                'content' => 'Artificial Intelligence is no longer science fiction; it is reshaping our world in real-time. From healthcare diagnostics to creative writing, AI tools are augmenting human capabilities in unprecedented ways.',
                'days_ago' => 14
            ],
            [
                'title' => 'Sustainable Living: Small Changes, Big Impact',
                'content' => 'Climate change can feel overwhelming, but individual actions do matter. Start with simple swaps: reusable bags instead of plastic, LED bulbs instead of incandescent, walking instead of driving for short trips.',
                'days_ago' => 10
            ],
            [
                'title' => 'Mastering the Art of Coffee Brewing',
                'content' => 'There is something magical about the perfect cup of coffee. It is not just caffeine; it is ritual, science, and art combined. Whether you prefer pour-over, French press, or espresso, understanding the basics can transform your morning routine.',
                'days_ago' => 5
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, content, created_at) 
            VALUES (:title, :content, NOW() - INTERVAL ':days days')
        ");
        
        foreach ($samplePosts as $post) {
            $stmt->execute([
                'title' => $post['title'],
                'content' => $post['content'],
                'days' => $post['days_ago']
            ]);
        }
        
        echo "✅ Added " . count($samplePosts) . " sample posts!\n\n";
    } else {
        echo "ℹ️  Database already has {$result['count']} post(s), skipping sample data.\n\n";
    }
    
    // Verify setup
    echo "🔍 Verifying setup...\n";
    $stmt = $pdo->query("
        SELECT 
            table_name,
            (SELECT COUNT(*) FROM posts) as post_count
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'posts'
    ");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ Table 'posts' exists\n";
        echo "✅ Total posts: {$result['post_count']}\n\n";
    }
    
    // Mark setup as complete
    @file_put_contents($setupFile, date('Y-m-d H:i:s'));
    
    echo "🎉 DATABASE SETUP COMPLETE!\n\n";
    echo "You can now visit your homepage:\n";
    echo "<a href='/' style='color: #0F766E; font-size: 18px; font-weight: bold;'>→ Go to Blog Platform</a>\n\n";
    echo "⚠️ For security, you can delete this file: setup-database.php\n";
    
} catch (PDOException $e) {
    echo "❌ DATABASE ERROR:\n";
    echo $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. DATABASE_URL environment variable is set correctly in Render\n";
    echo "2. PostgreSQL database is 'Available' (green status) on Render\n";
    echo "3. Database and Web Service are in the SAME region\n\n";
    echo "How to fix:\n";
    echo "1. Go to Render Dashboard → Your Web Service\n";
    echo "2. Click 'Environment' in left sidebar\n";
    echo "3. Find DATABASE_URL variable\n";
    echo "4. Click 'Select Database' and choose your PostgreSQL database\n";
    echo "5. Save and wait for redeploy\n";
} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo $e->getMessage() . "\n";
}

echo "</pre>";
?>

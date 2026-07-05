<?php
/**
 * Naeem Electronic - Blog Page
 * Blog posts and articles
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Blog';
$page_description = 'Latest news and articles about home appliances';
$current_page = 'blog';

$db = new Database();

// Get published blog posts
$db->query("SELECT * FROM blogs WHERE is_published = 1 ORDER BY published_at DESC LIMIT 12");
$blogs = $db->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Our Blog</h1>
        <p class="mb-0">Latest news, tips, and articles about home appliances</p>
    </div>
</section>

<!-- Blog Section -->
<section class="blog-section py-5">
    <div class="container">
        <?php if ($blogs): ?>
            <div class="row">
                <?php foreach ($blogs as $blog): ?>
                    <div class="col-md-4 mb-4">
                        <div class="blog-card">
                            <?php if ($blog['featured_image']): ?>
                                <img src="<?php echo UPLOADS_PATH . '/' . $blog['featured_image']; ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" class="blog-image">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600" alt="Blog Image" class="blog-image">
                            <?php endif; ?>
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span class="blog-category"><?php echo htmlspecialchars($blog['category'] ?? 'General'); ?></span>
                                    <span class="blog-date"><?php echo date('M d, Y', strtotime($blog['published_at'])); ?></span>
                                </div>
                                <h3 class="blog-title">
                                    <a href="blog-post.php?id=<?php echo $blog['id']; ?>"><?php echo htmlspecialchars($blog['title']); ?></a>
                                </h3>
                                <p class="blog-excerpt"><?php echo htmlspecialchars(substr($blog['excerpt'] ?? strip_tags($blog['content']), 0, 150)) . '...'; ?></p>
                                <a href="blog-post.php?id=<?php echo $blog['id']; ?>" class="blog-read-more">Read More <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                <h3>No blog posts yet</h3>
                <p class="text-muted">Check back later for updates!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.blog-card {
    background: white;
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: var(--transition);
    border: 1px solid rgba(0,0,0,0.05);
    height: 100%;
}

.blog-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.blog-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.blog-content {
    padding: 25px;
}

.blog-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 13px;
}

.blog-category {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
}

.blog-date {
    color: var(--gray-600);
}

.blog-title {
    font-size: 20px;
    margin-bottom: 15px;
    font-weight: 700;
}

.blog-title a {
    color: var(--dark-color);
    transition: var(--transition);
}

.blog-title a:hover {
    color: var(--secondary-color);
}

.blog-excerpt {
    color: var(--gray-600);
    margin-bottom: 20px;
    line-height: 1.6;
}

.blog-read-more {
    color: var(--primary-color);
    font-weight: 600;
    transition: var(--transition);
}

.blog-read-more:hover {
    color: var(--secondary-color);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

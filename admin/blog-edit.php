<?php
/**
 * Blog post editor — Pinion & Adams Admin
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requirePermission('blog');

$db = getDb();
$id = (int)($_GET['id'] ?? 0);

$post = [
    'id'        => 0,
    'title'     => '',
    'slug'      => '',
    'excerpt'   => '',
    'content'   => '',
    'image_url' => '',
    'category'  => 'General',
    'status'    => 'draft',
    'author'    => 'P&A Admin',
];

$isEditing = false;

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $post      = $row;
        $isEditing = true;
    } else {
        setFlash('error', 'Post not found.');
        header('Location: /admin/blog.php');
        exit;
    }
}

$categories = ['General', 'Industry News', 'Company Updates', 'Training', 'Safety', 'Technology'];

$pageTitle = $isEditing ? 'Edit Post' : 'New Post';
$pageSub   = $isEditing ? h($post['title']) : 'Create a new blog article';

adminHead($pageTitle);
adminSidebar('blog');
adminMain($pageTitle, $isEditing ? $post['title'] : 'Create a new blog article');
?>
  <div style="display:flex;gap:0.5rem;margin-top:0.5rem;">
    <a href="/admin/blog.php" class="btn btn-ghost btn-sm">&larr; All Posts</a>
    <?php if ($isEditing): ?>
      <a href="/blog-post.php?slug=<?= h($post['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">Preview &rarr;</a>
    <?php endif; ?>
  </div>
</div><!-- /page-header -->

<?= flashHtml() ?>

<form method="POST" action="/admin/blog-save.php">
  <?php if ($isEditing): ?>
    <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem;align-items:start;">

    <!-- Main content area -->
    <div>
      <div class="card">
        <div class="card-header"><h2>Post Content</h2></div>
        <div class="card-body padded">

          <div class="form-group">
            <label for="post-title">Title *</label>
            <input type="text" id="post-title" name="title" required value="<?= h($post['title']) ?>" placeholder="Post title…">
          </div>

          <div class="form-group">
            <label for="post-slug">URL Slug *</label>
            <input type="text" id="post-slug" name="slug" required value="<?= h($post['slug']) ?>" placeholder="url-friendly-title">
            <p class="form-hint">Auto-generated from title. URL: <code>/blog-post.php?slug=<?= h($post['slug'] ?: 'your-slug') ?></code></p>
          </div>

          <div class="form-group">
            <label for="excerpt">Intro Text</label>
            <textarea id="excerpt" name="excerpt" class="short" rows="3" placeholder="Opening paragraph shown at the top of the article in larger text…"><?= h($post['excerpt']) ?></textarea>
            <p class="form-hint">This appears as the large opening paragraph at the top of the post.</p>
          </div>

          <div class="form-group">
            <label for="post-content">Content</label>
            <textarea id="post-content" name="content" placeholder="Full post content — HTML supported…"><?= h($post['content']) ?></textarea>
            <p class="form-hint">HTML content supported. Use &lt;p&gt;, &lt;h2&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;strong&gt;, etc.</p>
          </div>

        </div>
      </div>
    </div>

    <!-- Sidebar meta -->
    <div>
      <div class="card" style="margin-bottom:1rem;">
        <div class="card-header"><h2>Publish</h2></div>
        <div class="card-body padded">
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
              <option value="draft"<?= $post['status'] === 'draft' ? ' selected' : '' ?>>Draft</option>
              <option value="published"<?= $post['status'] === 'published' ? ' selected' : '' ?>>Published</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
            <?= $isEditing ? 'Save Changes' : 'Create Post' ?>
          </button>
          <?php if ($isEditing): ?>
            <a href="/blog-post.php?slug=<?= h($post['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;margin-top:0.5rem;">
              Preview Post &rarr;
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Post Settings</h2></div>
        <div class="card-body padded">

          <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat) ?>"<?= $post['category'] === $cat ? ' selected' : '' ?>><?= h($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="author">Author</label>
            <input type="text" id="author" name="author" value="<?= h($post['author']) ?>">
          </div>

          <div class="form-group">
            <label for="image_url">Hero Image URL</label>
            <input type="url" id="image_url" name="image_url" value="<?= h($post['image_url']) ?>" placeholder="https://…">
            <p class="form-hint">Full URL to the post's featured image.</p>
          </div>

        </div>
      </div>
    </div>

  </div>
</form>

<?php adminFooter(); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$posts = readJson('posts');
usort($posts, fn($a, $b) => ($b['created_at'] ?? 0) <=> ($a['created_at'] ?? 0));
$recentPosts = array_slice($posts, 0, 5);
$user = currentUser();

renderHeader('Home');
?>
<section class="landing-hero">
    <div class="eyebrow">WEBMORE</div>
    <h1>A place for things worth posting.</h1>
    <p class="landing-lede">
        Facts you just learned. Thoughts that would be odd to keep to yourself.
        Pictures, links, observations, and the occasional thing that makes no sense until it does.
    </p>
    <div class="hero-actions">
        <a class="btn accent" href="explore.php">Browse around</a>
        <?php if ($user): ?>
            <a class="btn" href="create.php">Post something</a>
        <?php else: ?>
            <a class="btn" href="login.php?next=create.php">Post something</a>
        <?php endif; ?>
    </div>
    <div class="landing-note">
        <span class="note-dot" aria-hidden="true"></span>
        No particular subject. No particular schedule. Just whatever people decide is worth leaving here.
    </div>
</section>

<section class="how-section" aria-labelledby="what-title">
    <div class="section-intro">
        <div class="section-kicker">What you will find</div>
        <h2 id="what-title">A little bit of everything.</h2>
        <p>
            Some posts are one sentence. Some take a little longer. Some are mostly a picture.
            Others are a link that probably deserves opening.
        </p>
    </div>
    <div class="how-grid">
        <article class="how-item">
            <div class="how-index">01</div>
            <h3>Read something</h3>
            <p>Take a look at what has turned up lately, or search for something oddly specific.</p>
        </article>
        <article class="how-item">
            <div class="how-index">02</div>
            <h3>Leave something</h3>
            <p>Add a thought, fact, image, link, or a combination of them when you have something to say.</p>
        </article>
        <article class="how-item">
            <div class="how-index">03</div>
            <h3>Come back</h3>
            <p>There is always a chance someone has left something stranger than the last time you looked.</p>
        </article>
    </div>
</section>

<section class="feed-section" aria-labelledby="recent-title">
    <div class="feed-head">
        <div>
            <div class="section-kicker">From whoever happened to stop by</div>
            <h2 id="recent-title">Recent posts</h2>
        </div>
        <a class="text-link" href="explore.php">See everything <span aria-hidden="true">→</span></a>
    </div>

    <?php if ($recentPosts): ?>
        <div class="feed">
            <?php foreach ($recentPosts as $post): ?>
                <?php include __DIR__ . '/post-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty landing-empty">
            <strong>Nothing has been left here yet.</strong>
            <p>Which is either peaceful or a little suspicious.</p>
            <?php if ($user): ?>
                <a class="btn accent small" href="create.php">Be the first</a>
            <?php else: ?>
                <a class="btn accent small" href="register.php">Make an account</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<section class="closing-cta">
    <div>
        <div class="section-kicker">Anyway</div>
        <h2>That is probably enough explaining.</h2>
        <p>Go see what is here.</p>
    </div>
    <a class="btn primary" href="explore.php">Explore Webmore</a>
</section>
<?php renderFooter(); ?>

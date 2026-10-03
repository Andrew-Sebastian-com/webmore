<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
renderHeader('Security & Privacy');
?>
<section class="legal-page">
    <div class="eyebrow">security & privacy</div>
    <h1>Quietly protected.</h1>
    <p class="lede">Webmore tries to keep the things that should stay private out of the places where everyone can see them.</p>
    <div class="legal-meta">Last updated: <?= h(POLICY_UPDATED) ?></div>
    <article class="legal-content">
        <h2>What is public</h2>
        <p>Posts, replies, usernames, display names, bios, websites, and avatars are public when you choose to publish them. Do not put private information in public posts.</p>

        <h2>What is private</h2>
        <p>Your email address, password, session, and age-check record are kept separate from public profile content. The exact date of birth entered during signup is not retained after the 13+ check.</p>

        <h2>Account protection</h2>
        <p>Passwords are hashed rather than stored as readable passwords. Sessions use cookie-based authentication with HttpOnly and SameSite protections, and the session identifier is replaced when a user logs in. Authenticated forms also require a CSRF token.</p>

        <h2>Uploads</h2>
        <p>Image uploads are checked by detected file type, structure, size, and dimensions. Files are stored outside the public web directory and are only served through a controlled image endpoint. Uploaded images can still contain information such as camera or location metadata, so users should remove sensitive metadata before sharing an image.</p>

        <h2>Tracking</h2>
        <p>This starter does not include advertising scripts or analytics. Ordinary technical logs may still exist at the web server, hosting provider, reverse proxy, or CDN level.</p>

        <h2>Good practice for a public launch</h2>
        <p>Use HTTPS everywhere, keep secrets outside source control, restrict filesystem permissions, add server-side rate limiting, use a production database and backup plan, verify email addresses, monitor authentication failures, and regularly review dependencies and the server configuration.</p>

        <div class="legal-callout"><strong>One practical rule:</strong> public content should be treated as public forever, even though Webmore provides account deletion. Other people may have copied or linked to something before it was removed from this installation.</div>
    </article>
</section>
<?php renderFooter(); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
renderHeader('Disclaimer');
?>
<section class="legal-page">
    <div class="eyebrow">plain-language disclaimer</div>
    <h1>Disclaimer.</h1>
    <p class="lede">What Webmore does not promise about user posts, external links, availability, or outcomes.</p>
    <div class="legal-meta">Last updated: <?= h(POLICY_UPDATED) ?> · Policy version <?= h(POLICY_VERSION) ?></div>
    <article class="legal-content">
        <h2>1. User content</h2>
        <p>Webmore hosts content submitted by users. Posts may be inaccurate, satirical, offensive, incomplete, or based on personal opinions. The operator does not guarantee that any user-submitted statement is true, useful, current, or safe to act on.</p>

        <h2>2. No professional advice</h2>
        <p>Content on Webmore is not automatically professional, medical, legal, financial, educational, or technical advice. Do your own checking before relying on information for a consequential decision.</p>

        <h2>3. External links</h2>
        <p>Posts may contain links to websites that Webmore does not control. The operator is not responsible for the content, security, availability, or privacy practices of third-party sites.</p>

        <h2>4. Availability and changes</h2>
        <p>Webmore may be changed, interrupted, reset, or taken offline. Features and stored data may change during development. The operator does not promise uninterrupted availability or permanent storage.</p>

        <h2>5. No endorsement</h2>
        <p>The presence of a post, account, link, image, or opinion on Webmore does not mean the operator agrees with it or recommends it.</p>

        <h2>6. Use your judgment</h2>
        <p>Do not share secrets or sensitive personal information in a public post. Do not follow instructions from a random post merely because they appear confident or popular.</p>
    </article>
</section>
<?php renderFooter(); ?>

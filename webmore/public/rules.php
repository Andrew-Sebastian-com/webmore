<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
renderHeader('Community Rules');
?>
<section class="legal-page">
    <div class="eyebrow">rules & safety</div>
    <h1>Community Rules.</h1>
    <p class="lede">A strict, plain-language standard for what may and may not be posted on Webmore.</p>
    <div class="legal-meta">Last updated: <?= h(POLICY_UPDATED) ?> · Policy version <?= h(POLICY_VERSION) ?></div>
    <article class="legal-content">
        <h2>1. Be reasonably decent</h2>
        <p>Do not use Webmore to harass, threaten, stalk, bully, impersonate, or repeatedly target another person. Criticism and disagreement are allowed; personal abuse is not.</p>

        <h2>2. No illegal or dangerous content</h2>
        <p>Do not post content that is unlawful, meaningfully facilitates wrongdoing, or encourages dangerous behavior. Do not use Webmore to arrange or promote illegal transactions.</p>

        <h2>3. No sexual exploitation or sexual content involving minors</h2>
        <p>Sexual exploitation, sexualized content involving minors, or content that sexualizes minors is prohibited. Report suspected exploitation to the appropriate authorities and notify the site operator through the contact method listed by the operator.</p>

        <h2>4. Respect privacy</h2>
        <p>Do not post someone else’s private or identifying information without a legitimate reason and permission. Do not publish passwords, authentication codes, financial account details, private addresses, or other sensitive personal information.</p>

        <h2>5. Respect intellectual property</h2>
        <p>Only upload or publish material you have the right to use. Do not intentionally upload copyrighted, trademarked, or otherwise protected material when you do not have permission or another lawful basis to use it.</p>

        <h2>6. No spam or manipulation</h2>
        <p>No mass-posting, automated posting, fake engagement, phishing, malicious links, deceptive impersonation, or attempts to manipulate search, likes, or visibility.</p>

        <h2>7. Facts are not automatically verified</h2>
        <p>Webmore is a user-content platform. A post titled as a “fact” may still be a joke, opinion, mistake, or unsupported claim. Do not treat a post as authoritative merely because it appears on Webmore.</p>

        <h2>8. Replies are part of the same rules</h2>
        <p>Replies, like posts, must follow these rules. Replying to someone does not create a separate standard for harassment, spam, privacy violations, or prohibited content.</p>

<h2>8. Moderation</h2>
        <p>The operator may remove content, restrict visibility, suspend accounts, or permanently disable accounts when there is a rules, safety, legal, or security reason to do so. Moderation decisions may be made without prior notice where necessary.</p>

        <h2>9. Report problems</h2>
        <p>Use the contact method published by the operator to report illegal content, privacy violations, copyright concerns, security issues, or serious rule violations. Include enough information for the operator to locate the content.</p>

        <div class="legal-callout"><strong>Remember:</strong> public posts can be copied, archived, or linked to by other people. Do not publish anything you need to remain private.</div>
    </article>
</section>
<?php renderFooter(); ?>

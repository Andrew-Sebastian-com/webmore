<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
renderHeader('Terms of Use');
?>
<section class="legal-page">
    <div class="eyebrow">terms</div>
    <h1>Terms of Use.</h1>
    <p class="lede">The agreement between you and the Webmore operator for using accounts, posts, and the service.</p>
    <div class="legal-meta">Last updated: <?= h(POLICY_UPDATED) ?> · Policy version <?= h(POLICY_VERSION) ?></div>
    <article class="legal-content">
        <h2>1. Acceptance</h2>
        <p>By creating an account or using account-only features, you agree to these Terms, the Community Rules, the Privacy Policy, and the Disclaimer. If you do not agree, do not create or use an account.</p>

        <h2>2. Eligibility</h2>
        <p>You must be at least 13 years old to create an account on Webmore, and you must comply with any higher minimum age or consent requirement that applies to you where you live.</p>

        <h2>3. Your account</h2>
        <p>You are responsible for keeping your login credentials reasonably secure and for activity performed through your account. Do not impersonate another person or create accounts for abuse, fraud, or evasion of moderation.</p>

        <h2>4. Your content</h2>
        <p>You retain your ownership of content you submit, to the extent you have ownership rights in it. By posting it, you grant the Webmore operator a non-exclusive, worldwide, royalty-free license to host, store, reproduce, format, publicly display, and deliver that content as necessary to operate and promote the service. This license ends for deleted content when the operator’s technical and backup systems permit, except where retention is required for legal, security, or backup reasons.</p>

        <h2>5. You promise you have the right to post it</h2>
        <p>You are responsible for making sure your content is lawful and that you have permission or another lawful basis to upload images, text, trademarks, music, links, or other material you did not create.</p>

        <h2>6. Prohibited use</h2>
        <p>You must not violate the Community Rules, interfere with the service, attempt unauthorized access, upload malware, evade security controls, scrape or automate the site in a way that burdens it, or use Webmore to facilitate unlawful activity.</p>

        <h2>7. Moderation and account suspension</h2>
        <p>The operator may remove content, suspend accounts, or terminate access when reasonably necessary for safety, security, legal compliance, or rule enforcement. The operator may also preserve limited information when necessary to investigate abuse or meet legal obligations.</p>

        <h2>8. Copyright and complaints</h2>
        <p>If you believe content infringes your rights, use the operator’s published contact method to report it. A proper complaint should identify the work or right involved, the relevant Webmore URL, and enough information for the operator to evaluate the claim.</p>

        <h2>9. No warranties</h2>
        <p>Webmore is provided on an as-available basis during development. To the extent permitted by applicable law, the operator makes no promise that the service will be uninterrupted, error-free, secure, accurate, or suitable for a particular purpose.</p>

        <h2>10. Limitation of liability</h2>
        <p>To the extent permitted by applicable law, the operator is not liable for indirect, incidental, special, consequential, or exemplary losses arising from use of the service or user-generated content. This section does not remove rights or liabilities that cannot legally be excluded.</p>

        <h2>11. Changes to these terms</h2>
        <p>The operator may update these Terms when the service or legal requirements change. The current version and update date will be shown on this page. Continued use after a material update may be treated as acceptance to the extent permitted by applicable law.</p>

        <h2>12. Governing law</h2>
        <p>For a public launch, the operator should replace this generic clause with the governing-law and dispute-resolution terms required or chosen for the operator’s actual jurisdiction.</p>

        <div class="legal-callout"><strong>Operator setup required:</strong> before public launch, replace the placeholder contact email in <code>config.php</code> and review these Terms for the operator’s actual country, state, and business structure.</div>
    </article>
</section>
<?php renderFooter(); ?>

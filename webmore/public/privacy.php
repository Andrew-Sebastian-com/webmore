<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
renderHeader('Privacy Policy');
?>
<section class="legal-page">
    <div class="eyebrow">privacy</div>
    <h1>Privacy Policy.</h1>
    <p class="lede">A plain-language summary of what Webmore keeps, what other people can see, and how you can remove or export your account data.</p>
    <div class="legal-meta">Last updated: <?= h(POLICY_UPDATED) ?> · Policy version <?= h(POLICY_VERSION) ?></div>
    <article class="legal-content">
        <h2>1. What this policy covers</h2>
        <p>This policy describes the information handled by this Webmore application. Replace the operator contact details and review this document for the laws that apply to the real deployment before launching publicly.</p>

        <h2>2. Information you provide</h2>
        <p>When you create an account, Webmore stores your username, email address, password hash, account creation time, age-verification timestamp, profile settings, language preference, and the time/version of policy acceptance. Your exact date of birth is used only to perform the 13+ age check and is not retained after signup.</p>

        <h2>3. Content you publish</h2>
        <p>Posts and replies can contain titles, text, images, links, tags, author username, and creation time. This content is intended to be public. Your email address and password are not shown as part of your public profile.</p>

        <h2>4. Why we use the information</h2>
        <p>The application uses account information to create and authenticate accounts, keep posts and replies associated with their authors, apply profile settings, process account deletion/export requests, and protect forms and sessions.</p>

        <h2>5. Cookies and sessions</h2>
        <p>Webmore uses a server-side session cookie to keep you signed in and support security controls such as CSRF protection. The session cookie is HttpOnly and uses SameSite protection; the Secure flag is enabled when the site is served over HTTPS. The starter does not use advertising cookies or cross-site tracking cookies.</p>

        <h2>6. Rate limiting and security records</h2>
        <p>The starter applies short-lived rate limits inside the current session for actions such as login, signup, posting, replying, password changes, and account deletion. These counters are kept in the session rather than in a visitor profile or public account record. Your hosting environment may separately keep normal technical logs such as request time, IP address, and browser information.</p>

        <h2>7. Where account data is stored</h2>
        <p>In this build, application data and uploaded images are stored in a private server-side directory outside the public web document root. Public images are served through the application rather than by exposing the storage directory itself.</p>

        <h2>8. Images and third-party links</h2>
        <p>Images attached to public posts and profiles are public content. Image files may contain metadata such as camera or location information; Webmore does not promise to remove that metadata. Links can send you to third-party sites with their own privacy practices.</p>

        <h2>9. Data sharing</h2>
        <p>Your language preference is stored in your account when signed in; visitors who are not signed in may use a preference cookie. This starter does not sell personal information or include third-party advertising or analytics. Information may still be processed by the hosting provider, security infrastructure, or other service providers needed to operate the site, and may be disclosed when legally required or reasonably necessary to protect people or the service.</p>

        <h2>10. Retention and deletion</h2>
        <p>Account data and public content remain while the account and content exist. The Settings page can export your account data and can permanently delete your account, posts, replies, avatar, and uploaded post images from this installation. Server logs, caches, and backups controlled by the hosting environment may have different retention periods.</p>

        <h2>11. Your choices and requests</h2>
        <p>For privacy questions or applicable requests for access, correction, deletion, or other rights, contact the Webmore operator at <a href="mailto:<?= h(SITE_CONTACT_EMAIL) ?>"><?= h(SITE_CONTACT_EMAIL) ?></a>. The operator should review this policy for the laws that apply to the real deployment.</p>

        <h2>12. Children and the age gate</h2>
        <p>Webmore is intended for users who are at least 13. The signup form asks for a date of birth to determine whether the user is at least 13, then discards the exact date and retains only an age-verification timestamp. The site should not knowingly maintain accounts for users below its stated minimum age.</p>

        <h2>13. Security</h2>
        <p>The starter includes password hashing, strict session settings, session regeneration at login, CSRF protection, output encoding, controlled image serving, upload validation, rate limiting, and security headers. These measures reduce common risks but cannot guarantee perfect security.</p>

        <div class="legal-callout"><strong>Important:</strong> this is a development template, not a jurisdiction-specific legal opinion. Privacy obligations vary by the operator's location, users' locations, and the services used to host Webmore.</div>
    </article>
</section>
<?php renderFooter(); ?>

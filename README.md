# Webmore — security & privacy build

This build keeps the same Webmore experience, but moves the sensitive parts behind a real public/private boundary and adds several security and privacy protections.

## Start on macOS

From this folder:

```bash
./start.sh
```

Then open `http://127.0.0.1:8000/`.

You can also run:

```bash
php -S 127.0.0.1:8000 -t public
```

The important part is `-t public`: only the `public/` directory is web-visible. User data and uploads live in `private/` and are not directly addressable by URL.

## Layout

```text
webmore/
├── public/          # browser-facing PHP/CSS/JS
└── private/
    ├── data/        # account, post, reply data
    └── uploads/     # uploaded images
```

## Security improvements

- Session cookies use HttpOnly and SameSite=Lax; Secure is enabled automatically when served over HTTPS.
- PHP strict session mode and cookie-only sessions are enabled.
- Sessions expire after inactivity and have an absolute lifetime.
- Authentication regenerates the session ID.
- CSRF protection is applied to state-changing forms.
- Logout is POST-only.
- Login redirects are restricted to local application paths.
- Failed login, signup, posting, replying, password-change, and deletion attempts are rate-limited per session.
- Passwords use Argon2id when the PHP build supports it, with PHP's secure default fallback otherwise.
- Passwords can be transparently upgraded to the current hashing algorithm after a successful login.
- Uploaded files are checked by their detected MIME type, actual image structure, size, and pixel dimensions.
- Uploaded files are stored outside the public document root and are served only through `media.php`.
- User-controlled text is HTML-escaped on output.
- Strict security response headers and a Content Security Policy are sent on application responses.
- Sensitive pages are marked `no-store` to reduce browser/proxy caching of authenticated data.
- Account deletion removes the user's posts, replies, avatar, and uploaded post images from the private storage used by this installation.
- The exact date of birth is no longer retained after the 13+ age check; the app keeps only an age-verification timestamp.
- A personal data export is available from Settings. It excludes password hashes.

## Privacy notes

Webmore does not add advertising or analytics in this starter. Email addresses remain private from public profiles. Uploaded images are public content when attached to a public post or profile, so users should avoid uploading private photographs or files containing sensitive metadata.

This project is still a starter rather than a finished production security system. A public launch should use HTTPS, a production-grade database and backup strategy, server-level rate limiting/WAF protections, verified email flows, security monitoring, a secrets manager, and a formal privacy/legal review for the jurisdictions where the service operates.


## Post controls

- The author can edit or delete their own post.
- Any signed-in user can favourite or archive any public post, including other people's posts.
- Favourites and archive entries are private to the account that saved them.
- Deleting a post removes its replies, uploaded post image, and any personal saved references to it.
- `saved.php` shows the signed-in user's Favourites and Archive.


### Permission model

Editing and deleting are author-only actions. Favourites and Archive are private account actions and can be used on any public post, including posts written by other people.


Language: The interface can be switched between English and Japanese from the header or Settings. User-created post text and replies are not machine-translated.


Language switching is live: changing EN/日本語 updates the current page immediately, saves a browser preference cookie, and synchronizes the signed-in account in the background. User-created post/reply content is not translated.


## GitHub / source-control safety

This repository is designed so local Webmore runtime data stays out of Git. Account records, posts, replies, favourites/archive state, rate-limit files, and uploaded images are runtime data and are ignored by `.gitignore`. The application creates missing data files and directories automatically when it starts.

Before making a repository public, check the working tree and history for accidental secrets or private data. Do not force-add ignored runtime files. See `SECURITY.md` for deployment and reporting guidance.

### Interface polish

Webmore includes subtle, accessibility-aware micro-interactions: page reveals, scroll progress, button feedback, image loading transitions, and small favourite/archive confirmations. Reduced-motion preferences disable the decorative motion.

### Post features

Posts support account-linked likes, private favourites and archives, author-only editing/deletion, link copying, image lightbox viewing, and account-based reporting for other users’ posts. Runtime report data remains private and is created only on the server.
## Copyright

© Andrew Sebastian. YouTube: AndrewSudiro. GitHub: andrew-sebastian-com.

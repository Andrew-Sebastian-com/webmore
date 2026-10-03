<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
requireLogin();
$user = currentUser();
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$post = postById($id);
if (!$post) { http_response_code(404); renderHeader('Not found'); echo '<section class="panel"><div class="empty"><strong>Post not found.</strong></div></section>'; renderFooter(); exit; }
if (strtolower((string)$post['author']) === strtolower((string)$user['username'])) redirect('post.php?id=' . urlencode($id));
$errors=[]; $submitted=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    if (!throttle('report', 5, 3600)) $errors[]='You have sent too many reports. Please wait a little.';
    $reason=(string)($_POST['reason']??'');
    $details=trim((string)($_POST['details']??''));
    if ($details!=='' && u_strlen($details)>1000) $errors[]='Details must be 1,000 characters or fewer.';
    if (!$errors && reportPost($user,$id,$reason,$details)) { $submitted=true; }
    elseif (!$errors) { $errors[]='This post has already been reported or the report could not be saved.'; }
}
renderHeader('Report');
?>
<section class="panel">
  <div class="form-card report-card">
    <a class="backlink" href="post.php?id=<?= urlencode($id) ?>">← Back to post</a>
    <?php if ($submitted): ?>
      <div class="eyebrow">received</div>
      <h1>Thanks for flagging it.</h1>
      <p class="lede">The report is recorded for review. You do not need to do anything else.</p>
      <div class="form-actions"><a class="btn accent" href="post.php?id=<?= urlencode($id) ?>">Return to post</a></div>
    <?php else: ?>
      <div class="eyebrow">report</div>
      <h1>Something off?</h1>
      <p class="lede">Tell us what is wrong with this post. Reports are attached to your account and are not shown publicly.</p>
      <?php if ($errors): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><div><?= h($error) ?></div><?php endforeach; ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= h($id) ?>">
        <div class="field"><label for="reason">Reason</label><select id="reason" name="reason" required><option value="">Choose one</option><option value="spam">Spam</option><option value="harassment">Harassment</option><option value="privacy">Privacy issue</option><option value="copyright">Copyright</option><option value="dangerous">Dangerous content</option><option value="other">Other</option></select></div>
        <div class="field"><label for="details">Details <span class="muted">(optional)</span></label><textarea id="details" name="details" maxlength="1000" placeholder="What should we know?"><?= h($_POST['details'] ?? '') ?></textarea></div>
        <div class="form-actions"><a class="btn" href="post.php?id=<?= urlencode($id) ?>">Cancel</a><button class="btn accent" type="submit">Send report</button></div>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php renderFooter(); ?>

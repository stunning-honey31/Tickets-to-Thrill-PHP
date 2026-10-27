<?php
/**
 * Simple footer variant (no link columns / newsletter).
 * Optional vars the including page may set beforehand:
 *   $footerNoMargin (bool)   - true for the auth pages (login/register)
 *   $footerLinkHref  (string) - href for the right-hand footer link
 *   $footerLinkText  (string) - label for the right-hand footer link
 */
$footerNoMargin = $footerNoMargin ?? false;
$footerLinkHref = $footerLinkHref ?? null;
$footerLinkText = $footerLinkText ?? null;
?>
<footer class="site-footer"<?= $footerNoMargin ? ' style="margin-top:0;"' : '' ?>>
  <div class="container">
    <div class="footer-bottom" style="border-top:none;<?= $footerNoMargin ? ' padding-top:0;' : '' ?>">
      <span>© <?= date('Y') ?> Tickets to Thrill. All rights reserved.</span>
      <?php if ($footerLinkHref): ?>
        <span><a href="<?= h($footerLinkHref) ?>" style="color:#B7ADD9;"><?= h($footerLinkText) ?></a></span>
      <?php else: ?>
        <span><a href="about.php" style="color:#B7ADD9;">About</a> · <a href="contact.php" style="color:#B7ADD9;">Contact</a></span>
      <?php endif; ?>
    </div>
  </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>

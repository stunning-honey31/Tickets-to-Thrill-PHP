<?php
require_once __DIR__ . '/classes.php';

$subjectOptions = ['Ticket / booking issue', 'Group booking', 'Lost & found', 'Feedback', 'Other'];

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$formMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $formMessage = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
    } else {
        $old['name'] = trim((string) ($_POST['name'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['subject'] = trim((string) ($_POST['subject'] ?? ''));
        $old['message'] = trim((string) ($_POST['message'] ?? ''));

        $errors = v_collect([
            'name' => fn() => v_name($old['name']),
            'email' => fn() => v_email($old['email']),
            'subject' => fn() => v_in($old['subject'], $subjectOptions, 'subject'),
            'message' => fn() => v_length($old['message'], 10, 2000, 'Message'),
        ]);

        if (empty($errors)) {
            $pdo = get_db();
            $stmt = $pdo->prepare('
                INSERT INTO messages (name, email, subject, message, created_at)
                VALUES (:name, :email, :subject, :message, :created_at)
            ');
            $stmt->execute([
                ':name' => $old['name'],
                ':email' => $old['email'],
                ':subject' => $old['subject'],
                ':message' => $old['message'],
                ':created_at' => date('Y-m-d H:i:s'),
            ]);

            $formMessage = ['type' => 'success', 'text' => 'Message sent! Our team will reply within 24 hours.'];
            $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
        }
    }
}

$pageTitle = 'Contact — Tickets to Thrill';
$activePage = 'contact.php';
require __DIR__ . '/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb">HOME / CONTACT</div>
    <h1>Get in touch</h1>
    <p>Questions about tickets, groups or lost items? We're here.</p>
  </div>
</div>

<section>
  <div class="container contact-layout">
    <div>
      <div class="info-card">
        <div class="ico">📍</div>
        <div>
          <h4 style="margin-bottom:4px;">Visit the park</h4>
          <p style="color:var(--ink-soft); margin:0;">Thrill Valley Road, Sanand, Ahmedabad, Gujarat 382110</p>
        </div>
      </div>
      <div class="info-card">
        <div class="ico">📞</div>
        <div>
          <h4 style="margin-bottom:4px;">Call support</h4>
          <p style="color:var(--ink-soft); margin:0;">+91 79 6600 1234 · Daily, 9AM–8PM</p>
        </div>
      </div>
      <div class="info-card">
        <div class="ico">✉️</div>
        <div>
          <h4 style="margin-bottom:4px;">Email us</h4>
          <p style="color:var(--ink-soft); margin:0;">support@ticketstothrill.example</p>
        </div>
      </div>
      <div class="info-card">
        <div class="ico">👥</div>
        <div>
          <h4 style="margin-bottom:4px;">Group bookings</h4>
          <p style="color:var(--ink-soft); margin:0;">groups@ticketstothrill.example for 10+ guests</p>
        </div>
      </div>
      <div class="map-box">🗺️ Map preview — Thrill Valley, Sanand</div>
    </div>

    <div>
      <div style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:30px;">
        <h3>Send us a message</h3>
        <?php if ($formMessage): ?>
          <div class="form-msg show <?= h($formMessage['type']) ?>"><?= h($formMessage['text']) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <div class="row-2">
            <div class="field<?= isset($errors['name']) ? ' error' : '' ?>">
              <label for="cname">Name</label>
              <input type="text" id="cname" name="name" placeholder="Your name" value="<?= h($old['name']) ?>">
              <div class="err-msg"><?= h($errors['name'] ?? 'Please enter your name.') ?></div>
            </div>
            <div class="field<?= isset($errors['email']) ? ' error' : '' ?>">
              <label for="cemail">Email</label>
              <input type="email" id="cemail" name="email" placeholder="you@email.com" value="<?= h($old['email']) ?>">
              <div class="err-msg"><?= h($errors['email'] ?? 'Please enter a valid email.') ?></div>
            </div>
          </div>
          <div class="field<?= isset($errors['subject']) ? ' error' : '' ?>">
            <label for="csubject">Subject</label>
            <select id="csubject" name="subject">
              <option value="">Choose a topic</option>
              <?php foreach ($subjectOptions as $opt): ?>
                <option<?= $old['subject'] === $opt ? ' selected' : '' ?>><?= h($opt) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="err-msg"><?= h($errors['subject'] ?? 'Please choose a subject.') ?></div>
          </div>
          <div class="field<?= isset($errors['message']) ? ' error' : '' ?>">
            <label for="cmessage">Message</label>
            <textarea id="cmessage" name="message" rows="5" placeholder="How can we help?"><?= h($old['message']) ?></textarea>
            <div class="err-msg"><?= h($errors['message'] ?? 'Please enter a message (min 10 characters).') ?></div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<section style="background:var(--paper); border-top:1px solid var(--line);">
  <div class="container" style="max-width:800px;">
    <div class="section-head center">
      <span class="eyebrow">❓ FAQ</span>
      <h2>Quick answers</h2>
    </div>
    <details class="faq-item" open>
      <summary>Can I reschedule my visit date?</summary>
      <p>Most day passes are date-flexible — just show the QR code any day within its validity window. Season passes have no date restriction.</p>
    </details>
    <details class="faq-item">
      <summary>Do children need a separate ticket?</summary>
      <p>Children under 5 enter free. Ages 5–12 use the discounted Child Pass available on the Tickets page.</p>
    </details>
    <details class="faq-item">
      <summary>Is Fast Pass included in Combo tickets?</summary>
      <p>Not by default — Fast Pass is an add-on you can stack with any individual, combo or offer ticket at checkout.</p>
    </details>
    <details class="faq-item">
      <summary>What's your refund policy?</summary>
      <p>Unused tickets are refundable up to 24 hours before the visit date. Contact support with your order email to start a refund.</p>
    </details>
  </div>
</section>

<?php
$footerLinkHref = 'tickets.php';
$footerLinkText = 'Browse tickets →';
require __DIR__ . '/footer-simple.php';
?>

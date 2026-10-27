<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    $doing = (string) ($_POST['do'] ?? '');

    if ($doing === 'toggle_read') {
        $mark = ((string) ($_POST['mark'] ?? '1')) === '1' ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE messages SET is_read = :mark WHERE id = :id');
        $stmt->execute([':mark' => $mark, ':id' => $messageId]);
    } elseif ($doing === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
        $stmt->execute([':id' => $messageId]);
        flash_set('success', 'Message deleted.');
    }

    header('Location: index.php?page=messages');
    exit;
}

$messages = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h2>Contact Messages (<?= count($messages) ?>)</h2>
  </div>

  <?php if (empty($messages)): ?>
    <p class="admin-empty">No messages yet.</p>
  <?php else: ?>
    <div class="admin-message-list">
      <?php foreach ($messages as $m): ?>
        <div class="admin-message-card<?= $m['is_read'] ? '' : ' unread' ?>">
          <div class="admin-message-top">
            <div>
              <strong><?= h($m['name']) ?></strong>
              <span class="admin-sub"><?= h($m['email']) ?></span>
            </div>
            <span class="admin-sub"><?= h(date('M d, Y g:i A', strtotime($m['created_at']))) ?></span>
          </div>
          <div class="admin-message-subject"><?= h($m['subject']) ?><?php if (!$m['is_read']): ?> <span class="status-pill upcoming">New</span><?php endif; ?></div>
          <p class="admin-message-body"><?= nl2br(h($m['message'])) ?></p>
          <div class="admin-row-actions">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
              <input type="hidden" name="do" value="toggle_read">
              <input type="hidden" name="mark" value="<?= $m['is_read'] ? '0' : '1' ?>">
              <button type="submit" class="btn btn-sm btn-ghost"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this message?');">
              <?= csrf_field() ?>
              <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
              <input type="hidden" name="do" value="delete">
              <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--coral-dark);">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

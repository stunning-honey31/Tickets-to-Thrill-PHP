<?php
require_once __DIR__ . '/data/tickets.php';

$pageTitle = 'Tickets — Tickets to Thrill';
$activePage = 'tickets.php';
require __DIR__ . '/header.php';

$catalog = ticket_catalog();
$topClass = ['individual' => 'c-individual', 'combo' => 'c-combo', 'offer' => 'c-offer'];
?>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb">HOME / TICKETS</div>
    <h1>Find your pass</h1>
    <p>Individual entries, combo bundles and limited-time offers — priced per person unless noted.</p>
  </div>
</div>

<section style="padding-bottom:0;">
  <div class="container">
    <div class="filter-tabs" role="tablist">
      <button class="active" data-filter="all">All Tickets</button>
      <button data-filter="individual">Individual</button>
      <button data-filter="combo">Combo</button>
      <button data-filter="offer">Offers</button>
    </div>
  </div>
</section>

<?php foreach ($catalog as $key => $group): ?>
<section id="<?= h($key) ?>" style="<?= $key === 'individual' ? 'padding-top:24px;' : ($key === 'combo' ? 'background:var(--paper); border-top:1px solid var(--line); border-bottom:1px solid var(--line);' : '') ?>">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow"><?= h($group['eyebrow']) ?></span>
      <h2><?= h($group['title']) ?></h2>
    </div>
    <div class="ticket-grid" data-group="<?= h($key) ?>">
      <?php foreach ($group['tickets'] as $t): ?>
      <div class="ticket-card" data-cat="<?= h($t['category']) ?>">
        <div class="ticket-top <?= h($topClass[$t['category']]) ?>">
          <?php if (!empty($t['badge'])): ?><span class="badge"><?= h($t['badge']) ?></span><?php endif; ?>
          <span class="cat"><?= h($t['label']) ?></span>
          <h3><?= h($t['name']) ?></h3>
        </div>
        <div class="ticket-body">
          <p style="color:var(--ink-soft); font-size:.9rem;"><?= h($t['blurb']) ?></p>
          <ul>
            <?php foreach ($t['bullets'] as $b): ?><li><?= h($b) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <div class="ticket-divider"></div>
        <form action="actions/cart_add.php" method="post" class="ticket-footer" style="flex-direction:column; align-items:stretch; gap:12px;">
          <?= csrf_field() ?>
          <input type="hidden" name="ticket_id" value="<?= h($t['id']) ?>">
          <input type="hidden" name="redirect" value="../tickets.php#<?= h($key) ?>">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="price">
              <?php if (!empty($t['was'])): ?><span class="was"><?= money($t['was']) ?></span><?php endif; ?>
              <span class="amount"><?= money0($t['price']) ?></span>
              <span class="per"><?= h($t['per']) ?></span>
            </div>
            <div class="qty-stepper" data-qty>
              <button type="button" data-step="-1">−</button>
              <input type="text" name="qty" value="1" readonly data-qty-input>
              <button type="button" data-step="1">+</button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block add-cart-btn">Add to Cart</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>

<script>
  // filter tabs
  const tabs = document.querySelectorAll(".filter-tabs button");
  const groups = document.querySelectorAll("[data-group]");
  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      tabs.forEach(t => t.classList.remove("active"));
      tab.classList.add("active");
      const f = tab.dataset.filter;
      groups.forEach(g => {
        const parentSection = g.closest("section");
        parentSection.style.display = (f === "all" || g.dataset.group === f) ? "" : "none";
      });
    });
  });
</script>

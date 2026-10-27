<?php
require_once __DIR__ . '/data/tickets.php';

$pageTitle = 'Tickets to Thrill — Book Your Theme Park Adventure';
$activePage = 'index.php';
require __DIR__ . '/header.php';

$picks = ticket_popular_picks();
$topClass = ['individual' => 'c-individual', 'combo' => 'c-combo', 'offer' => 'c-offer'];
?>

<div class="band">
  <div class="band-track">
    <span>🎉</span> Monsoon Splash Sale — 20% off Combo Tickets
    <span>🎡</span> New: Nightfall Fireworks Package
    <span>🎫</span> Kids under 5 enter free every day
    <span>⚡</span> Skip-the-line Fast Pass now available
    <span>🎉</span> Monsoon Splash Sale — 20% off Combo Tickets
    <span>🎡</span> New: Nightfall Fireworks Package
    <span>🎫</span> Kids under 5 enter free every day
    <span>⚡</span> Skip-the-line Fast Pass now available
  </div>
</div>

<section class="hero">
  <div class="container hero-grid">
    <div>
      <h1>Your next scream<br>starts with <span>one ticket.</span></h1>
      <p class="lead">Book same-day entry, combo passes and season deals to Thrill Valley — Gujarat's tallest coasters, splash zones and after-dark shows, all in one checkout.</p>
      <div class="hero-ctas">
        <a href="tickets.php" class="btn btn-primary">Browse Tickets 🎫</a>
        <a href="tickets.php#combo" class="btn btn-outline" style="border-color:#fff;color:#fff;">See Combo Deals</a>
      </div>
      <div class="hero-stats">
        <div><strong>32</strong><span>Rides & Attractions</span></div>
        <div><strong>4.8★</strong><span>12,400 Reviews</span></div>
        <div><strong>#1</strong><span>Rated Park in Gujarat</span></div>
      </div>
    </div>
    <div class="ticket-hero">
      <div class="stub-row">
        <span class="stub-brand">🎢 T2T</span>
        <span class="stub-badge">ADMIT ONE</span>
      </div>
      <div class="stub-main">Full Day Combo Pass</div>
      <div class="stub-divider"></div>
      <div class="stub-meta">
        <span>DATE: ANY DAY</span>
        <span>GATE: A1–A6</span>
      </div>
      <div class="stub-price">₹949.00</div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">🎠 What's Waiting Inside</span>
      <h2>A park built for every kind of thrill-seeker</h2>
      <p>From gentle carousels to record-breaking drops — pick your pace.</p>
    </div>
    <div class="rides-strip">
      <div class="ride-tile"><span class="emoji">🎢</span><h4>Vortex Coasters</h4><p>4 record-breaking tracks</p></div>
      <div class="ride-tile"><span class="emoji">🌊</span><h4>Splash Zone</h4><p>Wave pools & water slides</p></div>
      <div class="ride-tile"><span class="emoji">🎡</span><h4>Skyline Wheel</h4><p>180ft panoramic views</p></div>
      <div class="ride-tile"><span class="emoji">👻</span><h4>Haunted Manor</h4><p>After-dark scare house</p></div>
    </div>
  </div>
</section>

<section style="background:var(--paper); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">🎟 Popular Picks</span>
      <h2>Grab today's best-selling tickets</h2>
    </div>
    <div class="ticket-grid">
      <?php foreach ($picks as $t): ?>
      <div class="ticket-card">
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
        <div class="ticket-footer">
          <div class="price">
            <?php if (!empty($t['was'])): ?><span class="was"><?= money($t['was']) ?></span><?php endif; ?>
            <span class="amount"><?= money0($t['price']) ?></span>
            <span class="per"><?= h($t['per']) ?></span>
          </div>
          <a href="tickets.php" class="btn btn-secondary btn-sm">View</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center; margin-top:40px;">
      <a href="tickets.php" class="btn btn-outline">See All Tickets</a>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">🧾 How Booking Works</span>
      <h2>Three steps to your ticket stub</h2>
    </div>
    <div class="steps">
      <div class="step">
        <div class="num">1</div>
        <h4>Pick your pass</h4>
        <p style="color:var(--ink-soft); font-size:.92rem;">Choose individual, combo or a limited-time offer that fits your crew.</p>
      </div>
      <div class="step">
        <div class="num">2</div>
        <h4>Add to cart & checkout</h4>
        <p style="color:var(--ink-soft); font-size:.92rem;">Create an account, review your cart, apply a promo code and confirm.</p>
      </div>
      <div class="step">
        <div class="num">3</div>
        <h4>Show up & scan in</h4>
        <p style="color:var(--ink-soft); font-size:.92rem;">Your e-ticket lands in My Account — just scan at the gate and go.</p>
      </div>
    </div>
  </div>
</section>

<section style="background:var(--paper); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">💬 Rider Reviews</span>
      <h2>What thrill-seekers are saying</h2>
    </div>
    <div class="testimonials">
      <div class="testi-card">
        <div class="stars">★★★★★</div>
        <p>Booked our combo passes in two minutes and skipped the ticket line entirely. The kids ran straight for the Skyline Wheel.</p>
        <div class="who">— Priya M.</div>
      </div>
      <div class="testi-card">
        <div class="stars">★★★★★</div>
        <p>The Family 4-Pack saved us real money and the meal voucher covered lunch for everyone. Easy checkout too.</p>
        <div class="who">— Arjun & Family</div>
      </div>
      <div class="testi-card">
        <div class="stars">★★★★☆</div>
        <p>Haunted Manor after dark was worth the whole trip. My Account made re-booking for next month painless.</p>
        <div class="who">— Sneha R.</div>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="cta-band">
    <h2>Gates open at 10AM. Your seat on the Vortex doesn't hold itself.</h2>
    <p>Lock in today's rate before the weekend surcharge kicks in.</p>
    <a href="tickets.php" class="btn" style="background:#fff; color:var(--purple-dark); box-shadow:0 8px 0 #d9d2f0;">Book Now</a>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>

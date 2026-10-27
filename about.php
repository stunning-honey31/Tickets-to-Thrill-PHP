<?php
$pageTitle = 'About — Tickets to Thrill';
$activePage = 'about.php';
require __DIR__ . '/header.php';
?>

<div class="about-hero">
  <div class="container">
    <h1>We build the moment before the drop.</h1>
    <p>Tickets to Thrill is the official booking site for Thrill Valley — three decades of rides, splashes and scares, now bookable in under a minute.</p>
  </div>
</div>

<section>
  <div class="container split">
    <div>
      <span class="eyebrow">🎡 Our Story</span>
      <h2>From one carousel to 32 attractions</h2>
      <p style="color:var(--ink-soft);">Thrill Valley opened in 1994 with a single wooden carousel and a dream. Today the park spans 60 acres, six coasters, a full water park and an after-dark show circuit — and this site exists so booking your visit is as fun as the visit itself.</p>
      <p style="color:var(--ink-soft);">We're an independent, family-run park. Every ticket you buy here goes straight to keeping the rides running and the popcorn popping.</p>
    </div>
    <div class="art">🎢</div>
  </div>
</section>

<section style="background:var(--paper); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">💛 What We Stand For</span>
      <h2>Our values, park-wide</h2>
    </div>
    <div class="values-grid">
      <div class="value-card"><span class="emoji">🛡️</span><h4>Safety First</h4><p style="color:var(--ink-soft); font-size:.88rem;">Every ride inspected daily, no exceptions.</p></div>
      <div class="value-card"><span class="emoji">🌈</span><h4>Fun For All</h4><p style="color:var(--ink-soft); font-size:.88rem;">Rides and passes for every age and budget.</p></div>
      <div class="value-card"><span class="emoji">🌱</span><h4>Sustainable Park</h4><p style="color:var(--ink-soft); font-size:.88rem;">Solar-powered pavilions, zero single-use plastic.</p></div>
      <div class="value-card"><span class="emoji">🤝</span><h4>Community First</h4><p style="color:var(--ink-soft); font-size:.88rem;">Free days for local schools every spring.</p></div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">📈 Thrill Valley in Numbers</span>
      <h2>Three decades, one park</h2>
    </div>
    <div class="stat-tiles" style="grid-template-columns:repeat(4,1fr);">
      <div class="stat-tile"><strong>1994</strong><span>Year Founded</span></div>
      <div class="stat-tile"><strong>32</strong><span>Rides & Attractions</span></div>
      <div class="stat-tile"><strong>2.1M</strong><span>Guests Per Year</span></div>
      <div class="stat-tile"><strong>4.8★</strong><span>Average Rating</span></div>
    </div>
  </div>
</section>

<section>
  <div class="cta-band">
    <h2>Come see what three decades of thrill-building looks like.</h2>
    <p>Book a pass and find out why guests come back year after year.</p>
    <a href="tickets.php" class="btn" style="background:#fff; color:var(--purple-dark); box-shadow:0 8px 0 #d9d2f0;">Browse Tickets</a>
  </div>
</section>

<?php
$footerLinkHref = 'contact.php';
$footerLinkText = 'Get in touch →';
require __DIR__ . '/footer-simple.php';
?>

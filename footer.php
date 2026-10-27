<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <span class="footer-logo" style="display:flex; align-items:center; gap:10px; white-space:nowrap;">
          <svg viewBox="0 0 64 64" width="30" height="30" role="img" aria-hidden="true" style="flex-shrink:0;">
            <defs>
              <linearGradient id="footerLogoGrad" x1="10%" y1="0%" x2="90%" y2="100%">
                <stop offset="0%" stop-color="#33206E"/>
                <stop offset="55%" stop-color="#241454"/>
                <stop offset="100%" stop-color="#5E2CC0"/>
              </linearGradient>
              <clipPath id="footerLogoClip">
                <rect x="1" y="1" width="62" height="62" rx="17"/>
              </clipPath>
            </defs>
            <g clip-path="url(#footerLogoClip)">
              <rect x="1" y="1" width="62" height="62" fill="url(#footerLogoGrad)"/>
              <path d="M8 38 C 13 21, 20 21, 24 32 C 27 40, 30 40, 33 32 C 37 21, 44 21, 49 30 C 52 35, 54 37, 57 38"
                    fill="none" stroke="#FFFFFF" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="24.5" cy="31.5" r="6.4" fill="none" stroke="#FFFFFF" stroke-width="2.6"/>
              <g transform="translate(38,23) rotate(-24)">
                <rect x="-5" y="-3.6" width="10" height="7.2" rx="1.8" fill="#FF4D5E"/>
                <circle cx="-2.8" cy="4" r="1.5" fill="#1F1435"/>
                <circle cx="2.8" cy="4" r="1.5" fill="#1F1435"/>
              </g>
              <g transform="translate(50,14)">
                <path d="M0 -6.5 L1.7 -1.7 L6.5 0 L1.7 1.7 L0 6.5 L-1.7 1.7 L-6.5 0 L-1.7 -1.7 Z" fill="#FFC93C"/>
              </g>
              <rect x="1" y="55" width="62" height="8" fill="#FFC93C"/>
            </g>
          </svg>
          Tickets to Thrill
        </span>
        <p style="font-size:.88rem; color:#B7ADD9;">Gujarat's favorite theme park — rides, splashes and scares for every age, booked in minutes.</p>
      </div>
      <div>
        <h4>Explore</h4>
        <ul>
          <li><a href="tickets.php">All Tickets</a></li>
          <li><a href="about.php">About Us</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4>Account</h4>
        <ul>
          <li><a href="login.php">Login</a></li>
          <li><a href="register.php">Register</a></li>
          <li><a href="myaccount.php">My Account</a></li>
          <li><a href="cart.php">My Cart</a></li>
        </ul>
      </div>
      <div>
        <h4>Stay in the loop</h4>
        <p style="font-size:.85rem; color:#B7ADD9;">Offers, new rides & event dates — no spam.</p>
        <form class="newsletter" onsubmit="event.preventDefault(); this.reset(); alert('Subscribed! Watch your inbox for offers.');">
          <input type="email" placeholder="you@email.com" required>
          <button type="submit">Join</button>
        </form>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> Tickets to Thrill. All rights reserved.</span>
      <span>Made for thrill-seekers, by thrill-seekers.</span>
    </div>
  </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>

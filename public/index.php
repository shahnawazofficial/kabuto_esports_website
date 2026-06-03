<?php
/**
 * Kabuto Esports — Home Page (Production)
 * Include path: __DIR__/includes/ (files are in public_html root)
 */
require_once __DIR__ . '/includes/functions.php';

$tournaments = getUpcomingTournaments(6);
$featuredTournament = count($tournaments) > 0 ? $tournaments[0] : null;

$stats = Database::fetchOne(
    "SELECT COUNT(*) as total_tournaments,
            SUM(registered_slots) as total_registrations,
            SUM(prize_pool) as total_prize_pool FROM tournaments WHERE status != 'cancelled'"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kabuto Esports – India's Premier BGMI Tournament Platform</title>
<meta name="description" content="Join Kabuto Esports tournaments and compete for massive prize pools. Register your team for BGMI Solo, Duo and Squad tournaments. Fast registration, instant confirmation.">
<meta name="keywords" content="BGMI tournament, esports India, kabuto esports, pubg mobile tournament, gaming competition">
<meta property="og:title" content="Kabuto Esports – India's Premier BGMI Tournament Platform">
<meta property="og:description" content="Compete in BGMI tournaments with massive prize pools. Register now!">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= APP_URL ?>">
<link rel="canonical" href="<?= APP_URL ?>">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.how-step{text-align:center;padding:28px 20px}
.how-step .step-num{width:56px;height:56px;border-radius:50%;background:var(--primary-glow);border:2px solid var(--primary);display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:20px;font-weight:700;color:var(--primary);margin:0 auto 16px}
.how-step h3{font-size:18px;margin-bottom:8px}
.how-step p{color:var(--text-muted);font-size:14px}
.prize-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;text-align:center;position:relative;overflow:hidden;transition:var(--transition)}
.prize-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--primary),var(--accent))}
.prize-card:hover{border-color:var(--border-bright);transform:translateY(-4px);box-shadow:var(--shadow-glow)}
.prize-icon{font-size:40px;margin-bottom:12px}
.prize-amount{font-family:'Orbitron',sans-serif;font-size:28px;font-weight:700;color:var(--primary);margin-bottom:4px}
.prize-label{color:var(--text-muted);font-size:14px}
.contact-card{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;display:flex;gap:16px;align-items:flex-start}
.contact-icon{width:44px;height:44px;border-radius:var(--radius-sm);background:var(--primary-glow);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:18px;flex-shrink:0}
</style>
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<section class="hero">
  <div class="container" style="position:relative;z-index:1;padding-top:40px;padding-bottom:80px">
    <div class="hero-content">
      <div class="hero-eyebrow">&#127918; India's Premier BGMI Tournament Platform</div>
      <h1>BATTLE FOR <span class="highlight">GLORY &</span> PRIZE POOLS</h1>
      <p>Join thousands of players competing in BGMI tournaments. Register your squad, showcase your skills, and win real prizes.</p>
      <div class="hero-actions">
        <a href="/tournaments" class="btn btn-primary btn-lg">
          <i class="fas fa-trophy"></i> Browse Tournaments
        </a>
        <a href="#how-to" class="btn btn-outline btn-lg">How It Works</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="num font-orbitron"><?= number_format($stats['total_tournaments'] ?? 0) ?>+</div>
          <div class="lbl">Tournaments Hosted</div>
        </div>
        <div class="hero-stat">
          <div class="num font-orbitron"><?= number_format($stats['total_registrations'] ?? 0) ?>+</div>
          <div class="lbl">Teams Registered</div>
        </div>
        <div class="hero-stat">
          <div class="num font-orbitron"><?= formatCurrency((float)($stats['total_prize_pool'] ?? 0)) ?></div>
          <div class="lbl">Total Prize Pool</div>
        </div>
      </div>
    </div>
  </div>
  <div style="position:absolute;right:0;top:68px;bottom:0;width:50%;background:radial-gradient(ellipse at 80% 50%,rgba(124,58,237,0.12),transparent 70%);pointer-events:none"></div>
</section>

<section class="section" id="tournaments">
  <div class="container">
    <div class="section-heading fade-up">
      <p style="color:var(--primary);font-size:13px;text-transform:uppercase;letter-spacing:2px;font-weight:700;margin-bottom:8px">&#128293; Live &amp; Upcoming</p>
      <h2>Featured Tournaments</h2>
      <div class="section-divider"></div>
      <p>Choose your battleground. Register before slots fill up!</p>
    </div>

    <?php if (empty($tournaments)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
      <div style="font-size:48px;margin-bottom:16px">&#127942;</div>
      <p>No tournaments available right now. Check back soon!</p>
    </div>
    <?php else: ?>
    <div class="grid-3">
      <?php foreach ($tournaments as $i => $t):
        $pct       = $t['total_slots'] > 0 ? ($t['registered_slots'] / $t['total_slots']) * 100 : 0;
        $fillClass = $pct >= 90 ? 'full' : ($pct >= 70 ? 'warn' : '');
        $avail     = max(0, $t['total_slots'] - $t['registered_slots']);
        $isFree    = (float)$t['entry_fee'] == 0;
        $bannerUrl = !empty($t['banner']) ? '/uploads/banners/' . htmlspecialchars($t['banner']) : '/assets/img/default-banner.svg';
      ?>
      <div class="card tournament-card fade-up" style="animation-delay:<?= $i * 0.1 ?>s">
        <div class="card-banner">
          <img src="<?= $bannerUrl ?>" alt="<?= Security::sanitize($t['name']) ?>" loading="<?= $i < 3 ? 'eager' : 'lazy' ?>">
          <div class="badge-overlay">
            <?php if ($isFree): ?><span class="badge badge-success">FREE</span><?php endif; ?>
            <span class="badge badge-<?= $t['mode'] ?>"><?= strtoupper($t['mode']) ?></span>
          </div>
        </div>
        <div class="card-body">
          <h3><?= Security::sanitize($t['name']) ?></h3>
          <div class="tc-stats">
            <div class="tc-stat"><div class="label">Entry Fee</div><div class="value"><?= $isFree ? 'FREE' : formatCurrency((float)$t['entry_fee']) ?></div></div>
            <div class="tc-stat"><div class="label">Prize Pool</div><div class="value"><?= formatCurrency((float)$t['prize_pool']) ?></div></div>
          </div>
          <div class="slots-bar"><div class="slots-fill <?= $fillClass ?>" style="width:<?= min(100,$pct) ?>%"></div></div>
          <div class="slots-text"><?= $avail ?> slots remaining of <?= $t['total_slots'] ?></div>
          <div class="deadline-text" style="margin-top:8px">
            <i class="far fa-clock"></i> Deadline: <?= date('d M Y', strtotime($t['registration_deadline'])) ?>
          </div>
        </div>
        <div class="card-footer" style="display:flex;gap:10px">
          <a href="/tournament/<?= Security::sanitize($t['slug']) ?>" class="btn btn-outline btn-sm" style="flex:1">Details</a>
          <?php if ($avail > 0 && strtotime($t['registration_deadline']) > time()): ?>
          <a href="/register/<?= $t['id'] ?>" class="btn btn-primary btn-sm" style="flex:1">Register</a>
          <?php else: ?>
          <span class="btn btn-sm" style="flex:1;background:var(--bg-surface);color:var(--text-dim);cursor:not-allowed">Closed</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:40px">
      <a href="/tournaments" class="btn btn-outline btn-lg">View All Tournaments <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="background:linear-gradient(180deg,transparent,rgba(245,166,35,0.03),transparent)">
  <div class="container">
    <div class="section-heading fade-up">
      <p style="color:var(--primary);font-size:13px;text-transform:uppercase;letter-spacing:2px;font-weight:700;margin-bottom:8px">&#128176; Win Big</p>
      <h2>Prize Pool Highlights</h2>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4 fade-up">
      <div class="prize-card"><div class="prize-icon">&#129351;</div><div class="prize-amount">&#8377;25,000</div><div class="prize-label">1st Place &ndash; Pro League S1</div></div>
      <div class="prize-card"><div class="prize-icon">&#129352;</div><div class="prize-amount">&#8377;12,000</div><div class="prize-label">2nd Place &ndash; Pro League S1</div></div>
      <div class="prize-card"><div class="prize-icon">&#129353;</div><div class="prize-amount">&#8377;7,000</div><div class="prize-label">3rd Place &ndash; Pro League S1</div></div>
      <div class="prize-card"><div class="prize-icon">&#127919;</div><div class="prize-amount">&#8377;50,000+</div><div class="prize-label">Total Prize Pool Season 1</div></div>
    </div>
  </div>
</section>

<section class="section" id="how-to">
  <div class="container">
    <div class="section-heading fade-up">
      <p style="color:var(--primary);font-size:13px;text-transform:uppercase;letter-spacing:2px;font-weight:700;margin-bottom:8px">&#128640; Get Started</p>
      <h2>How To Participate</h2>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4 fade-up">
      <div class="how-step"><div class="step-num">01</div><h3>Choose Tournament</h3><p>Browse active tournaments, review rules, prize pool, and entry fee.</p></div>
      <div class="how-step"><div class="step-num">02</div><h3>Fill Registration</h3><p>Enter your team details, player UIDs, and contact information.</p></div>
      <div class="how-step"><div class="step-num">03</div><h3>Make Payment</h3><p>Pay the entry fee securely via PayU. Free tournaments skip this step.</p></div>
      <div class="how-step"><div class="step-num">04</div><h3>Get Confirmation</h3><p>Receive email confirmation with your unique Registration ID.</p></div>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container" style="max-width:800px">
    <div class="section-heading fade-up">
      <h2>Frequently Asked Questions</h2>
      <div class="section-divider"></div>
    </div>
    <div class="fade-up">
      <?php
      $faqs = [
        ['Q'=>'How do I register for a tournament?','A'=>'Click on any tournament, hit "Register Now", fill in your team details, and pay the entry fee (if any). You\'ll get a confirmation email instantly.'],
        ['Q'=>'Are my UIDs safe?','A'=>'Absolutely. Your BGMI UIDs are only used to verify your participation and are never shared publicly.'],
        ['Q'=>'Can I cancel my registration?','A'=>'Once registered and payment is made, cancellations are not supported as per our policy. Please read tournament rules carefully before registering.'],
        ['Q'=>'How do I receive the room ID?','A'=>'Room IDs are shared exclusively via our Discord server before each match. Make sure to join the Discord link provided on the tournament page.'],
        ['Q'=>'What payment methods are accepted?','A'=>'We accept UPI, credit/debit cards, net banking, and all major wallets via PayU India.'],
        ['Q'=>'How are results and prizes distributed?','A'=>'Results are announced within 24 hours. Prize money is transferred directly to winners\' bank accounts within 7 working days.'],
      ];
      foreach ($faqs as $f): ?>
      <div class="faq-item">
        <button class="faq-question" onclick="toggleFaq(this)">
          <?= htmlspecialchars($f['Q']) ?>
          <span class="icon">+</span>
        </button>
        <div class="faq-answer">
          <p><?= htmlspecialchars($f['A']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="contact" style="background:var(--bg-card);border-top:1px solid var(--border)">
  <div class="container">
    <div class="section-heading fade-up">
      <h2>Get In Touch</h2>
      <div class="section-divider"></div>
      <p>Have questions? We're here to help you!</p>
    </div>
    <div class="grid-3 fade-up">
      <div class="contact-card">
        <div class="contact-icon"><i class="fab fa-whatsapp"></i></div>
        <div>
          <h4 style="margin-bottom:4px">WhatsApp Support</h4>
          <p style="color:var(--text-muted);font-size:14px">+91 98765 43210</p>
          <a href="https://wa.me/919876543210" target="_blank" class="btn btn-sm btn-success" style="margin-top:10px">Chat Now</a>
        </div>
      </div>
      <div class="contact-card">
        <div class="contact-icon"><i class="fab fa-discord"></i></div>
        <div>
          <h4 style="margin-bottom:4px">Discord Community</h4>
          <p style="color:var(--text-muted);font-size:14px">Get room IDs &amp; announcements</p>
          <a href="https://discord.gg/kabutoesports" target="_blank" class="btn btn-sm btn-info" style="margin-top:10px">Join Server</a>
        </div>
      </div>
      <div class="contact-card">
        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
        <div>
          <h4 style="margin-bottom:4px">Email Us</h4>
          <p style="color:var(--text-muted);font-size:14px">support@kabutoesports.com</p>
          <a href="mailto:support@kabutoesports.com" class="btn btn-sm btn-outline" style="margin-top:10px">Send Email</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">&#9876; KABUTO ESPORTS</div>
        <p class="footer-desc">India's premier BGMI tournament platform. Compete, win, and dominate the esports scene with Kabuto Esports.</p>
        <div class="social-links">
          <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
          <a href="#" class="social-link" title="Discord"><i class="fab fa-discord"></i></a>
          <a href="#" class="social-link" title="Twitter"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
      <div>
        <div class="footer-heading">Quick Links</div>
        <div class="footer-links">
          <a href="/">Home</a>
          <a href="/tournaments">Tournaments</a>
          <a href="#how-to">How To Play</a>
          <a href="#faq">FAQ</a>
        </div>
      </div>
      <div>
        <div class="footer-heading">Support</div>
        <div class="footer-links">
          <a href="mailto:support@kabutoesports.com">Email Support</a>
          <a href="https://discord.gg/kabutoesports">Discord</a>
          <a href="/check-status">Check Registration</a>
        </div>
      </div>
      <div>
        <div class="footer-heading">Legal</div>
        <div class="footer-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms of Service</a>
          <a href="#">Refund Policy</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 Kabuto Esports. All rights reserved. | kabutoesports.com</p>
      <p>Made with &#10084; for Indian Gamers</p>
    </div>
  </div>
</footer>

<script>
window.addEventListener('scroll',()=>{
  document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50);
});
function toggleNav(){
  document.getElementById('navLinks').classList.toggle('open');
}
function toggleFaq(btn){
  const item=btn.closest('.faq-item');
  const answer=item.querySelector('.faq-answer');
  const isOpen=item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(el=>{
    el.classList.remove('open');
    el.querySelector('.faq-answer').classList.remove('open');
  });
  if(!isOpen){item.classList.add('open');answer.classList.add('open');}
}
const observer=new IntersectionObserver((entries)=>{
  entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible')});
},{threshold:0.1});
document.querySelectorAll('.fade-up').forEach(el=>observer.observe(el));
</script>
</body>
</html>

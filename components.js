/* ============================================================
   AMS'SERVICES — Composants partagés
   Injecte topbar, navbar, urgence, footer, boutons flottants
   ============================================================ */

const AMS = {
  phone1: '07 67 91 51 32',
  phone2: '07 43 69 22 80',
  tel1:   '0767915132',
  tel2:   '0743692280',
  email:  'ams.chambery73@gmail.com',
  wa:     '33767915132',
  services: [
    { name: 'Plomberie',            icon: '🔧', slug: 'plomberie.html' },
    { name: 'Électricité',          icon: '⚡', slug: 'electricite.html' },
    { name: 'Serrurerie',           icon: '🔐', slug: 'serrurerie.html' },
    { name: 'Volets Roulants',      icon: '🪟', slug: 'volets-roulants.html' },
    { name: 'Bricolage',            icon: '🛠️', slug: 'bricolage.html' },
    { name: 'Nettoyage de Toiture', icon: '🏠', slug: 'nettoyage-toiture.html' },
  ]
};

/* ---- SVG icons helpers ---- */
const ICON_PHONE = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>`;
const ICON_MAIL  = `<svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>`;
const ICON_LOC   = `<svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9z" clip-rule="evenodd"/></svg>`;
const ICON_CHECK = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>`;
const ICON_CHV   = `<svg class="nav-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>`;
const ICON_UP    = `<svg width="22" height="22" fill="white" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>`;
const ICON_WA    = `<svg width="26" height="26" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`;

/* ---- Renders ---- */

function renderTopbar() {
  return `
  <div class="topbar">
    <div class="container">
      <span class="topbar-item">${ICON_LOC} Chambéry &amp; Savoie / Haute-Savoie</span>
      <div class="topbar-right">
        <a href="mailto:${AMS.email}" class="topbar-item">${ICON_MAIL} ${AMS.email}</a>
        <a href="tel:${AMS.tel1}" class="topbar-item">${ICON_PHONE.replace('16','14')} ${AMS.phone1}</a>
      </div>
    </div>
  </div>`;
}

function renderNavbar() {
  const dropItems = AMS.services.map(s =>
    `<li><a href="${s.slug}" class="dropdown-item"><span class="dropdown-item-icon">${s.icon}</span>${s.name}</a></li>`
  ).join('');

  return `
  <nav class="navbar">
    <div class="container nav-inner">
      <a href="index.html" class="logo" aria-label="AMS Services accueil">
        <img src="images/logo.png" alt="Logo AMS Services" class="logo-img" />
        <div>
          <div class="logo-text">AMS<span>'</span>SERVICES</div>
          <div class="logo-sub">Dépannage &amp; Réparation</div>
        </div>
      </a>
      <ul class="nav-links" id="nav-links" role="navigation">
        <li><a href="index.html">Accueil</a></li>
        <li class="nav-dropdown nav-item">
          <a href="index.html#services" class="dropdown-toggle">Services ${ICON_CHV}</a>
          <ul class="dropdown-menu">${dropItems}</ul>
        </li>
        <li><a href="index.html#tarifs">Tarifs</a></li>
        <li><a href="portfolio.html">Portfolio</a></li>
        <li><a href="index.html#about">À Propos</a></li>
        <li><a href="index.html#contact">Contact</a></li>
      </ul>
      <a href="tel:${AMS.tel1}" class="btn btn-primary nav-cta">
        ${ICON_PHONE} Appeler maintenant
      </a>
      <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>`;
}

function renderUrgence() {
  return `
  <div class="urgence-band">
    🚨 Urgence 24h/24 &mdash; 7j/7 &mdash; Appelez le <a href="tel:${AMS.tel1}">${AMS.phone1}</a> ou le <a href="tel:${AMS.tel2}">${AMS.phone2}</a>
  </div>`;
}

function renderFooter() {
  const serviceLinks = AMS.services.map(s =>
    `<li><a href="${s.slug}">${s.icon} ${s.name}</a></li>`
  ).join('');

  return `
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo">
            <img src="images/logo.png" alt="Logo AMS Services" class="logo-img" />
            <div>
              <div class="logo-text">AMS<span style="color:var(--orange)">'</span>SERVICES</div>
              <div class="logo-sub" style="color:#6B7280">Dépannage &amp; Réparation</div>
            </div>
          </div>
          <p class="footer-desc">Votre partenaire de confiance à Chambéry pour tous vos besoins de dépannage. Disponible 24h/24 et 7j/7.</p>
          <div class="footer-social">
            <a href="#" aria-label="Facebook">f</a>
            <a href="#" aria-label="Instagram">in</a>
            <a href="#" aria-label="Twitter">t</a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Services</h4>
          <ul class="footer-links">${serviceLinks}</ul>
        </div>
        <div class="footer-col">
          <h4>Navigation</h4>
          <ul class="footer-links">
            <li><a href="index.html">Accueil</a></li>
            <li><a href="index.html#services">Nos Services</a></li>
            <li><a href="index.html#tarifs">Tarifs</a></li>
            <li><a href="portfolio.html">Portfolio</a></li>
            <li><a href="index.html#about">À Propos</a></li>
            <li><a href="index.html#contact">Contact</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Contact</h4>
          <ul class="footer-links">
            <li><a href="tel:${AMS.tel1}">${AMS.phone1}</a></li>
            <li><a href="tel:${AMS.tel2}">${AMS.phone2}</a></li>
            <li><a href="mailto:${AMS.email}">${AMS.email}</a></li>
            <li style="color:#6B7280;font-size:.82rem;margin-top:8px;">Chambéry &amp; Savoie</li>
            <li style="color:#6B7280;font-size:.82rem;">24h/24 — 7j/7</li>
          </ul>
        </div>
      </div>
      <!-- Zone d'intervention SEO -->
      <div class="footer-zones">
        <div class="footer-zones-title">📍 Zone d'intervention</div>
        <div class="footer-zones-list">
          <span>Chambéry</span><span>Aix-les-Bains</span><span>La Motte-Servolex</span>
          <span>Cognin</span><span>Barberaz</span><span>Bassens</span>
          <span>La Ravoire</span><span>Saint-Alban-Leysse</span><span>Challes-les-Eaux</span>
          <span>Sonnaz</span><span>Voglans</span><span>Méry</span>
          <span>Savoie (73)</span><span>Haute-Savoie (74)</span>
        </div>
        <p class="footer-zones-note">Disponible dans toute la région Auvergne-Rhône-Alpes — contactez-nous pour vérifier votre commune.</p>
      </div>
      <div class="footer-bottom">
        <span>© 2026 AMS'SERVICES — Tous droits réservés</span>
        <div style="display:flex;gap:20px">
          <a href="#">Mentions légales</a>
          <a href="#">Politique de confidentialité</a>
          <a href="#">Cookies</a>
        </div>
      </div>
    </div>
  </footer>`;
}

function renderFloatingBtns() {
  return `
  <div class="float-btns">
    <a href="#" class="float-btn float-up" aria-label="Haut de page" id="scroll-top" style="display:none">${ICON_UP}</a>
    <a href="https://wa.me/${AMS.wa}" class="float-btn float-wa" aria-label="WhatsApp" target="_blank" rel="noopener">${ICON_WA}</a>
    <a href="tel:${AMS.tel1}" class="float-btn float-phone" aria-label="Appeler">
      <svg width="24" height="24" fill="white" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
    </a>
  </div>`;
}

function renderCookieBanner() {
  return `
  <div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="Cookies">
    <p class="cookie-text">Nous utilisons des cookies pour améliorer votre expérience. <a href="#">Politique de confidentialité</a>.</p>
    <div class="cookie-btns">
      <button class="cookie-accept" id="cookie-accept">Accepter</button>
      <button class="cookie-refuse" id="cookie-refuse">Refuser</button>
    </div>
  </div>`;
}

/* ---- Init common JS behaviors ---- */
function initCommon() {
  // Hamburger menu
  const burger = document.getElementById('hamburger');
  const navLinks = document.getElementById('nav-links');
  if (burger && navLinks) {
    burger.addEventListener('click', () => {
      const open = navLinks.classList.toggle('open');
      burger.setAttribute('aria-expanded', open);
    });
    navLinks.querySelectorAll('a:not(.dropdown-toggle)').forEach(a => {
      a.addEventListener('click', () => { navLinks.classList.remove('open'); burger.setAttribute('aria-expanded', false); });
    });
    // Mobile dropdown toggle
    const dropToggle = navLinks.querySelector('.dropdown-toggle');
    if (dropToggle) {
      dropToggle.addEventListener('click', e => {
        if (window.innerWidth <= 640) {
          e.preventDefault();
          dropToggle.closest('.nav-dropdown').classList.toggle('mobile-open');
        }
      });
    }
  }

  // Scroll-top button
  const scrollBtn = document.getElementById('scroll-top');
  if (scrollBtn) {
    window.addEventListener('scroll', () => {
      scrollBtn.style.display = window.scrollY > 400 ? 'flex' : 'none';
    }, { passive: true });
    scrollBtn.addEventListener('click', e => { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  // Reveal on scroll
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // Cookie banner
  if (!localStorage.getItem('cookie-consent')) {
    setTimeout(() => { const b = document.getElementById('cookie-banner'); if (b) b.classList.add('show'); }, 1500);
  }
  document.getElementById('cookie-accept')?.addEventListener('click', () => { localStorage.setItem('cookie-consent','accepted'); document.getElementById('cookie-banner').classList.remove('show'); });
  document.getElementById('cookie-refuse')?.addEventListener('click', () => { localStorage.setItem('cookie-consent','refused'); document.getElementById('cookie-banner').classList.remove('show'); });

  // Contact form (if present)
  document.getElementById('contact-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const required = this.querySelectorAll('[required]');
    let valid = true;
    required.forEach(f => { if (!f.value.trim()) { f.style.borderColor = '#EF4444'; valid = false; } else f.style.borderColor = ''; });
    if (!valid) return;
    const btn = this.querySelector('.form-submit');
    btn.textContent = 'Envoi en cours…'; btn.disabled = true;
    setTimeout(() => {
      document.getElementById('form-success').style.display = 'block';
      btn.style.display = 'none';
      this.querySelectorAll('input,select,textarea').forEach(f => f.value = '');
    }, 1200);
  });
}

/* ---- Bootstrap ---- */
document.addEventListener('DOMContentLoaded', () => {
  // Inject into slots
  const slots = {
    'topbar-slot':  renderTopbar(),
    'navbar-slot':  renderNavbar(),
    'urgence-slot': renderUrgence(),
    'footer-slot':  renderFooter(),
  };
  Object.entries(slots).forEach(([id, html]) => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html;
  });

  // Append floating + cookie to body
  document.body.insertAdjacentHTML('beforeend', renderFloatingBtns() + renderCookieBanner());

  // Init behaviors
  initCommon();
});

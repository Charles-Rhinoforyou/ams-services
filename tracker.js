/* ============================================================
   tracker.js — Mesure d'audience AMS'SERVICES
   • Ne collecte RIEN tant que le consentement ≠ "accepted"
   • Aucune donnée de formulaire, aucune donnée personnelle
   • Envoi non bloquant (sendBeacon), erreurs ignorées
   ============================================================ */
(function () {
  'use strict';

  var ENDPOINT = 'api/track.php';

  // Pages de service → type d'intervention (pour enrichir page_view)
  var INTERVENTION_MAP = {
    'plomberie.html': 'plomberie',
    'electricite.html': 'electricite',
    'serrurerie.html': 'serrurerie',
    'volets-roulants.html': 'volets',
    'bricolage.html': 'bricolage',
    'nettoyage-toiture.html': 'toiture',
    'nettoyage-terrasse.html': 'terrasse',
    'urgence.html': 'urgence',
    'portfolio.html': 'portfolio',
    'index.html': 'accueil',
    '': 'accueil'
  };

  function consentState() {
    try { return localStorage.getItem('cookie-consent') || 'unknown'; } catch (e) { return 'unknown'; }
  }
  function enabled() { return consentState() === 'accepted'; }

  function fileName() {
    var p = location.pathname.split('/').pop();
    return p || 'index.html';
  }
  function pageId() {
    var meta = document.querySelector('meta[name="ams-page-id"]');
    if (meta && meta.content) return meta.content;
    var f = fileName();
    return (f.replace(/\.html?$/, '') || 'accueil');
  }
  function interventionType() {
    var meta = document.querySelector('meta[name="ams-intervention"]');
    if (meta && meta.content) return meta.content;
    return INTERVENTION_MAP[fileName()] || '';
  }

  function send(event, params) {
    if (!enabled()) return;
    var payload = Object.assign({
      event: event,
      consent: 'accepted',
      page_id: pageId(),
      page_path: location.pathname,
      intervention_type: interventionType(),
      referrer: document.referrer || ''
    }, params || {});
    var body = JSON.stringify(payload);
    try {
      if (navigator.sendBeacon) {
        navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }));
        return;
      }
    } catch (e) { /* ignore */ }
    // Fallback
    try {
      fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true }).catch(function () {});
    } catch (e) { /* ignore */ }
  }
  window.amsTrack = send;

  var pageViewSent = false;
  function trackPageView() {
    if (pageViewSent || !enabled()) return;
    pageViewSent = true;
    send('page_view');
  }

  /* --- Auto-instrumentation des clics (identifiants stables, pas de texte) --- */
  function onClick(e) {
    if (!enabled()) return;
    var a = e.target.closest ? e.target.closest('a,button,[data-analytics-action]') : null;
    if (!a) return;

    var explicit = a.getAttribute('data-analytics-action');
    var params = {
      element_id: a.getAttribute('data-analytics-id') || a.id || '',
      section_id: a.getAttribute('data-analytics-section') || '',
      theme: a.getAttribute('data-analytics-theme') || ''
    };
    if (a.getAttribute('data-intervention-type')) params.intervention_type = a.getAttribute('data-intervention-type');

    if (explicit) { send(explicit, params); return; }

    var href = (a.getAttribute && a.getAttribute('href')) || '';
    if (/^tel:/i.test(href))            { send('phone_click', params); return; }
    if (/^mailto:/i.test(href))         { send('email_click', params); return; }
    if (/wa\.me|whatsapp/i.test(href))  { send('whatsapp_click', params); return; }
    if (/^https?:\/\//i.test(href) && href.indexOf(location.host) === -1) { send('external_link_click', params); return; }
  }

  /* --- Formulaires marqués (sans lire leur contenu) --- */
  function onSubmit(e) {
    if (!enabled()) return;
    var f = e.target;
    if (!f || !f.getAttribute || f.getAttribute('data-analytics-form') === null) return;
    var type = f.getAttribute('data-analytics-type');
    send('form_submit', { element_id: f.id || '' });
    if (type === 'quote') send('quote_request', { element_id: f.id || '' });
  }

  /* --- Profondeur de défilement (25/50/75/100), une fois chacune --- */
  var depthSent = {};
  function onScroll() {
    if (!enabled()) return;
    var h = document.documentElement;
    var scrolled = (h.scrollTop || document.body.scrollTop);
    var max = (h.scrollHeight - h.clientHeight);
    if (max <= 0) return;
    var pct = Math.round(scrolled / max * 100);
    [25, 50, 75, 100].forEach(function (t) {
      if (pct >= t && !depthSent[t]) { depthSent[t] = 1; send('scroll_depth', { value: t }); }
    });
  }

  function init() {
    trackPageView();
    document.addEventListener('click', onClick, true);
    document.addEventListener('submit', onSubmit, true);
    var st;
    window.addEventListener('scroll', function () { clearTimeout(st); st = setTimeout(onScroll, 250); }, { passive: true });
    // Démarre la mesure dès que l'utilisateur accepte les cookies (sans rechargement)
    window.addEventListener('ams-consent-accepted', trackPageView);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

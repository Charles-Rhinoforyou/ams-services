<?php
/* ============================================================
   api/track.php — collecteur d'événements (PUBLIC, sans secret)
   N'enregistre QUE si le consentement client vaut "accepted".
   Ne stocke jamais : IP en clair, contenu de formulaire, données
   personnelles. IP → hash journalier salé uniquement.
   ============================================================ */

require __DIR__ . '/_common.php';
require __DIR__ . '/geoip.php';

// CORS : même origine uniquement (pas d'en-tête permissif)
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  ams_json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

$raw = file_get_contents('php://input');
if (strlen($raw) > 8192) {
  ams_json(['success' => false, 'error' => 'Payload trop grand'], 413);
}
$body = json_decode($raw, true);
if (!is_array($body)) {
  ams_json(['success' => false, 'error' => 'Requête invalide'], 400);
}

/* Registre central des événements autorisés (noms normalisés) */
$ALLOWED = [
  'page_view', 'section_view', 'scroll_depth', 'phone_click', 'email_click',
  'contact_click', 'quote_request', 'form_start', 'form_submit', 'form_error',
  'file_download', 'gallery_open', 'external_link_click', 'navigation_click',
  'service_click', 'whatsapp_click',
];
$event = isset($body['event']) ? (string)$body['event'] : '';
if (!in_array($event, $ALLOWED, true)) {
  ams_json(['success' => false, 'error' => 'Événement inconnu'], 400);
}

/* Respect du consentement : sans "accepted", on ignore silencieusement */
$consent = isset($body['consent']) ? (string)$body['consent'] : '';
if ($consent !== 'accepted') {
  ams_json(['success' => true, 'ignored' => 'consent']);
}

/* Nettoyage : uniquement des valeurs courtes et non sensibles */
function ams_clean($v, $max = 120) {
  if (!is_string($v)) $v = '';
  $v = preg_replace('/[\x00-\x1F\x7F]/u', '', $v);
  return substr(trim($v), 0, $max);
}

$rec = [
  't'     => gmdate('c'),
  'e'     => $event,
  'pid'   => ams_clean($body['page_id'] ?? '', 60),
  'path'  => ams_clean($body['page_path'] ?? '', 200),
  'sid'   => ams_clean($body['section_id'] ?? '', 60),
  'eid'   => ams_clean($body['element_id'] ?? '', 60),
  'itype' => ams_clean($body['intervention_type'] ?? '', 40),
  'theme' => ams_clean($body['theme'] ?? '', 40),
  'val'   => is_numeric($body['value'] ?? null) ? (float)$body['value'] : null,
  'dev'   => ams_device_type(),
  'ref'   => ams_clean(parse_url((string)($body['referrer'] ?? ''), PHP_URL_HOST) ?: '', 80),
  'vh'    => ams_visitor_hash(),
];

/* Géolocalisation approximative : l'IP est utilisée ici de façon
   TRANSITOIRE puis abandonnée. Seuls pays/région agrégés sont conservés. */
if (($geo = ams_geo_lookup(ams_client_ip())) !== null) {
  $rec['cc']  = ams_clean($geo['cc'], 4);
  $rec['reg'] = ams_clean($geo['region'], 60);
  $rec['ctr'] = ams_clean($geo['country'], 60);
}

$file = ams_data_dir() . '/events-' . gmdate('Y-m') . '.ndjson';
@file_put_contents(
  $file,
  json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
  FILE_APPEND | LOCK_EX
);

ams_json(['success' => true]);

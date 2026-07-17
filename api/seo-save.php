<?php
/* ============================================================
   api/seo-save.php — Édition des métadonnées SEO (PROTÉGÉE)
   Modifie le <head> des pages HTML publiques : title, description,
   canonical, robots, Open Graph. Backup + historique à chaque écriture.
   Ne touche JAMAIS au contenu visible (body/H1) — sécurité.
   ============================================================ */

require __DIR__ . '/_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  ams_json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}
$body   = ams_require_admin(); // 403 si mauvais mot de passe
$action = isset($body['action']) ? (string)$body['action'] : 'save';

$root    = dirname(__DIR__); // www/
$exclude = ['gestion-realisations-9e0ac25fbcfa23f2.html'];

/* Valide et résout un nom de page (anti-traversée) */
function ams_seo_page($root, $exclude, $name) {
  $name = basename((string)$name);
  if (!preg_match('/^[a-z0-9][a-z0-9\-]*\.html$/i', $name)) return null;
  if (in_array($name, $exclude, true)) return null;
  $path = $root . '/' . $name;
  return is_file($path) ? $path : null;
}

/* ---- Historique ---- */
if ($action === 'history') {
  $f = ams_data_dir() . '/seo-history.ndjson';
  $rows = [];
  if (is_file($f) && ($fh = fopen($f, 'r'))) {
    while (($l = fgets($fh)) !== false) { $r = json_decode($l, true); if (is_array($r)) $rows[] = $r; }
    fclose($fh);
  }
  $rows = array_slice(array_reverse($rows), 0, 100);
  ams_json(['success' => true, 'history' => $rows]);
}

/* ---- Sauvegarde ---- */
if ($action !== 'save') ams_json(['success' => false, 'error' => 'Action inconnue'], 400);

$path = ams_seo_page($root, $exclude, $body['file'] ?? '');
if (!$path) ams_json(['success' => false, 'error' => 'Page inconnue'], 400);

$fields = is_array($body['fields'] ?? null) ? $body['fields'] : [];
$html   = @file_get_contents($path);
if ($html === false) ams_json(['success' => false, 'error' => 'Lecture impossible'], 500);

$orig = $html;
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$changes = [];

/* Remplace un <meta>/<link> existant, sinon l'insère avant </head>.
   Si la valeur est vide, supprime la balise si elle existe. */
function ams_set_tag(&$html, $findRe, $newTag, $value, &$changes, $field) {
  $has = preg_match($findRe, $html, $m);
  $old = $has ? $m[0] : '';
  if ($value === '') {
    if ($has) { $html = preg_replace($findRe, '', $html, 1); $changes[] = [$field, $old, '']; }
    return;
  }
  if ($has) {
    if ($m[0] !== $newTag) { $html = preg_replace($findRe, addcslashes($newTag, '\\$'), $html, 1); $changes[] = [$field, $old, $newTag]; }
  } else {
    $html = preg_replace('/<\/head>/i', '  ' . addcslashes($newTag, '\\$') . "\n</head>", $html, 1);
    $changes[] = [$field, '', $newTag];
  }
}

/* Title (contenu de la balise) */
if (array_key_exists('title', $fields)) {
  $t = trim((string)$fields['title']);
  if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
    if (trim($m[1]) !== $t) {
      $html = preg_replace('/(<title[^>]*>).*?(<\/title>)/is', '${1}' . addcslashes($e($t), '\\$') . '${2}', $html, 1);
      $changes[] = ['title', trim($m[1]), $t];
    }
  }
}

/* Métadonnées <head> */
if (array_key_exists('description', $fields))
  ams_set_tag($html, '/<meta\s+name=["\']description["\'][^>]*>/i', '<meta name="description" content="' . $e($fields['description']) . '" />', trim((string)$fields['description']), $changes, 'description');

if (array_key_exists('canonical', $fields))
  ams_set_tag($html, '/<link\s+rel=["\']canonical["\'][^>]*>/i', '<link rel="canonical" href="' . $e($fields['canonical']) . '" />', trim((string)$fields['canonical']), $changes, 'canonical');

if (array_key_exists('robots', $fields))
  ams_set_tag($html, '/<meta\s+name=["\']robots["\'][^>]*>/i', '<meta name="robots" content="' . $e($fields['robots']) . '" />', trim((string)$fields['robots']), $changes, 'robots');

if (array_key_exists('ogTitle', $fields))
  ams_set_tag($html, '/<meta\s+property=["\']og:title["\'][^>]*>/i', '<meta property="og:title" content="' . $e($fields['ogTitle']) . '" />', trim((string)$fields['ogTitle']), $changes, 'og:title');

if (array_key_exists('ogDescription', $fields))
  ams_set_tag($html, '/<meta\s+property=["\']og:description["\'][^>]*>/i', '<meta property="og:description" content="' . $e($fields['ogDescription']) . '" />', trim((string)$fields['ogDescription']), $changes, 'og:description');

if (array_key_exists('ogImage', $fields))
  ams_set_tag($html, '/<meta\s+property=["\']og:image["\'][^>]*>/i', '<meta property="og:image" content="' . $e($fields['ogImage']) . '" />', trim((string)$fields['ogImage']), $changes, 'og:image');

if (empty($changes)) {
  ams_json(['success' => true, 'changed' => 0, 'message' => 'Aucune modification']);
}

/* Backup de la version précédente (dossier privé) */
$bdir = ams_data_dir() . '/seo-backups';
if (!is_dir($bdir)) @mkdir($bdir, 0755, true);
@file_put_contents($bdir . '/' . basename($path) . '-' . gmdate('Ymd-His') . '.html', $orig);

/* Écriture */
if (@file_put_contents($path, $html, LOCK_EX) === false) {
  ams_json(['success' => false, 'error' => "Écriture impossible (droits du fichier ?)"], 500);
}

/* Historique */
$hf = ams_data_dir() . '/seo-history.ndjson';
foreach ($changes as $c) {
  @file_put_contents($hf, json_encode([
    't' => gmdate('c'), 'file' => basename($path), 'field' => $c[0],
    'old' => mb_substr((string)$c[1], 0, 300), 'new' => mb_substr((string)$c[2], 0, 300),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
}

ams_json(['success' => true, 'changed' => count($changes), 'fields' => array_map(fn($c) => $c[0], $changes)]);

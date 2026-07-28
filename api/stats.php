<?php
/* ============================================================
   api/stats.php — API d'agrégation (PROTÉGÉE par mot de passe)
   Lecture seule des données analytiques + scan SEO des pages.
   Aucune donnée privée n'est renvoyée sans authentification.
   ============================================================ */

require __DIR__ . '/_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  ams_json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}
$body   = ams_require_admin(); // 403 si mauvais mot de passe
$action = isset($body['action']) ? (string)$body['action'] : 'overview';

/* --- Période --- */
function ams_valid_date($d, $fallback) {
  return (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) ? $d : $fallback;
}
$to   = ams_valid_date($body['to']   ?? '', gmdate('Y-m-d'));
$from = ams_valid_date($body['from'] ?? '', gmdate('Y-m-d', time() - 29 * 86400));
if ($from > $to) { $tmp = $from; $from = $to; $to = $tmp; }

$filters = is_array($body['filters'] ?? null) ? $body['filters'] : [];

/* --- Chargement des événements (uniquement les mois concernés) --- */
function ams_load_events($from, $to) {
  $dir = ams_data_dir();
  $out = [];
  try {
    $cur = new DateTime(substr($from, 0, 7) . '-01');
    $end = new DateTime(substr($to, 0, 7) . '-01');
  } catch (Exception $e) { return $out; }
  while ($cur <= $end) {
    $file = $dir . '/events-' . $cur->format('Y-m') . '.ndjson';
    if (is_file($file) && ($fh = fopen($file, 'r'))) {
      while (($line = fgets($fh)) !== false) {
        $r = json_decode($line, true);
        if (!is_array($r)) continue;
        $d = substr($r['t'] ?? '', 0, 10);
        if ($d >= $from && $d <= $to) $out[] = $r;
      }
      fclose($fh);
    }
    $cur->modify('+1 month');
  }
  return $out;
}

function ams_apply_filters($events, $filters) {
  if (empty($filters)) return $events;
  return array_values(array_filter($events, function ($r) use ($filters) {
    if (!empty($filters['page'])   && ($r['pid']   ?? '') !== $filters['page'])   return false;
    if (!empty($filters['device']) && ($r['dev']   ?? '') !== $filters['device']) return false;
    if (!empty($filters['itype'])  && ($r['itype'] ?? '') !== $filters['itype'])  return false;
    return true;
  }));
}

const AMS_CONVERSIONS = ['form_submit', 'phone_click', 'email_click', 'quote_request'];

function ams_summary($events) {
  $pv = 0; $visitors = []; $conv = 0; $phone = 0; $email = 0; $forms = 0; $quote = 0; $contact = 0;
  foreach ($events as $r) {
    $e = $r['e'] ?? '';
    if ($e === 'page_view') { $pv++; $visitors[$r['vh'] ?? ''] = 1; }
    if (in_array($e, AMS_CONVERSIONS, true)) $conv++;
    if ($e === 'phone_click') $phone++;
    if ($e === 'email_click') $email++;
    if ($e === 'form_submit') $forms++;
    if ($e === 'quote_request') $quote++;
    if ($e === 'contact_click') $contact++;
  }
  $v = count($visitors);
  return [
    'pageviews'   => $pv,
    'visitors'    => $v,
    'conversions' => $conv,
    'phone'       => $phone,
    'email'       => $email,
    'forms'       => $forms,
    'quote'       => $quote,
    'contact'     => $contact,
    'convRate'    => $pv > 0 ? round($conv / $pv * 100, 1) : 0,
  ];
}

function ams_top($events, $key, $filterEvent, $limit = 5) {
  $agg = [];
  foreach ($events as $r) {
    if ($filterEvent && ($r['e'] ?? '') !== $filterEvent) continue;
    $k = $r[$key] ?? '';
    if ($k === '') $k = '(non défini)';
    $agg[$k] = ($agg[$k] ?? 0) + 1;
  }
  arsort($agg);
  $out = [];
  foreach (array_slice($agg, 0, $limit, true) as $k => $v) $out[] = ['label' => $k, 'value' => $v];
  return $out;
}

/* ============================================================
   Actions
   ============================================================ */
switch ($action) {

  case 'overview': {
    $ev  = ams_apply_filters(ams_load_events($from, $to), $filters);
    $cur = ams_summary($ev);
    // Période précédente (même durée) pour comparaison
    $days = (strtotime($to) - strtotime($from)) / 86400 + 1;
    $pTo   = gmdate('Y-m-d', strtotime($from) - 86400);
    $pFrom = gmdate('Y-m-d', strtotime($from) - $days * 86400);
    $prev  = ams_summary(ams_apply_filters(ams_load_events($pFrom, $pTo), $filters));
    ams_json([
      'success'  => true,
      'current'  => $cur,
      'previous' => $prev,
      'topPages'        => ams_top($ev, 'pid', 'page_view'),
      'topInterventions'=> ams_top($ev, 'itype', 'page_view'),
      'topEvents'       => ams_top($ev, 'e', null, 8),
      'devices'         => ams_top($ev, 'dev', 'page_view', 5),
      'sources'         => ams_top($ev, 'ref', 'page_view', 6),
      'hasData'  => count($ev) > 0,
    ]);
    break;
  }

  case 'timeseries': {
    $ev = ams_apply_filters(ams_load_events($from, $to), $filters);
    $days = [];
    for ($t = strtotime($from); $t <= strtotime($to); $t += 86400) $days[gmdate('Y-m-d', $t)] = ['pv'=>0,'vis'=>[],'conv'=>0];
    foreach ($ev as $r) {
      $d = substr($r['t'] ?? '', 0, 10);
      if (!isset($days[$d])) continue;
      $e = $r['e'] ?? '';
      if ($e === 'page_view') { $days[$d]['pv']++; $days[$d]['vis'][$r['vh'] ?? ''] = 1; }
      if (in_array($e, AMS_CONVERSIONS, true)) $days[$d]['conv']++;
    }
    $series = [];
    foreach ($days as $d => $v) {
      $series[] = ['date' => $d, 'pageviews' => $v['pv'], 'visitors' => count($v['vis']), 'conversions' => $v['conv']];
    }
    ams_json(['success' => true, 'series' => $series]);
    break;
  }

  case 'interventions': {
    $ev = ams_apply_filters(ams_load_events($from, $to), $filters);
    $agg = [];
    foreach ($ev as $r) {
      $it = $r['itype'] ?? '';
      if ($it === '') continue;
      if (!isset($agg[$it])) $agg[$it] = ['itype'=>$it,'pageviews'=>0,'visitors'=>[],'conversions'=>0];
      if (($r['e'] ?? '') === 'page_view') { $agg[$it]['pageviews']++; $agg[$it]['visitors'][$r['vh'] ?? ''] = 1; }
      if (in_array($r['e'] ?? '', AMS_CONVERSIONS, true)) $agg[$it]['conversions']++;
    }
    $rows = [];
    foreach ($agg as $a) {
      $rows[] = ['itype'=>$a['itype'],'pageviews'=>$a['pageviews'],'visitors'=>count($a['visitors']),
                 'conversions'=>$a['conversions'],'convRate'=>$a['pageviews']>0?round($a['conversions']/$a['pageviews']*100,1):0];
    }
    usort($rows, fn($x,$y)=>$y['pageviews']-$x['pageviews']);
    ams_json(['success'=>true,'rows'=>$rows]);
    break;
  }

  case 'conversions': {
    $ev = ams_apply_filters(ams_load_events($from, $to), $filters);
    $byType = []; $byPage = [];
    foreach ($ev as $r) {
      $e = $r['e'] ?? '';
      if (!in_array($e, AMS_CONVERSIONS, true)) continue;
      $byType[$e] = ($byType[$e] ?? 0) + 1;
      $p = $r['pid'] ?? '(non défini)';
      $byPage[$p] = ($byPage[$p] ?? 0) + 1;
    }
    arsort($byPage);
    ams_json(['success'=>true,'byType'=>$byType,'byPage'=>array_slice($byPage,0,10,true),'summary'=>ams_summary($ev)]);
    break;
  }

  case 'geo': {
    require_once __DIR__ . '/geoip.php';
    $ev  = ams_apply_filters(ams_load_events($from, $to), $filters);
    $cfg = ams_config();
    $threshold = max(1, (int)$cfg['geo_threshold']);

    $byCountry = []; $byRegion = []; $withGeo = 0;
    foreach ($ev as $r) {
      $cc = $r['cc'] ?? '';
      if ($cc === '') continue;
      $withGeo++;
      $ctr = $r['ctr'] ?? $cc;
      if (!isset($byCountry[$ctr])) $byCountry[$ctr] = ['label'=>$ctr,'cc'=>$cc,'visitors'=>[],'pageviews'=>0,'conversions'=>0];
      if (($r['e'] ?? '') === 'page_view') { $byCountry[$ctr]['pageviews']++; $byCountry[$ctr]['visitors'][$r['vh'] ?? ''] = 1; }
      if (in_array($r['e'] ?? '', AMS_CONVERSIONS, true)) $byCountry[$ctr]['conversions']++;

      if ($cc === 'FR') {
        $reg = ($r['reg'] ?? '') !== '' ? $r['reg'] : '(région inconnue)';
        if (!isset($byRegion[$reg])) $byRegion[$reg] = ['label'=>$reg,'visitors'=>[],'pageviews'=>0,'conversions'=>0];
        if (($r['e'] ?? '') === 'page_view') { $byRegion[$reg]['pageviews']++; $byRegion[$reg]['visitors'][$r['vh'] ?? ''] = 1; }
        if (in_array($r['e'] ?? '', AMS_CONVERSIONS, true)) $byRegion[$reg]['conversions']++;
      }
    }

    // Applique le seuil de confidentialité : les zones trop peu fréquentées sont masquées
    $shape = function ($agg) use ($threshold) {
      $rows = []; $maskedZones = 0; $maskedVisitors = 0;
      foreach ($agg as $a) {
        $v = count($a['visitors']);
        if ($v < $threshold) { $maskedZones++; $maskedVisitors += $v; continue; }
        $rows[] = [
          'label' => $a['label'], 'visitors' => $v, 'pageviews' => $a['pageviews'],
          'conversions' => $a['conversions'],
          'convRate' => $a['pageviews'] > 0 ? round($a['conversions'] / $a['pageviews'] * 100, 1) : 0,
        ];
      }
      usort($rows, fn($x, $y) => $y['visitors'] - $x['visitors']);
      return [$rows, $maskedZones, $maskedVisitors];
    };
    [$countries, $mcZones, $mcVis] = $shape($byCountry);
    [$regions,   $mrZones, $mrVis] = $shape($byRegion);

    ams_json([
      'success'   => true,
      'dbInstalled' => ams_geoip_available(),
      'hasGeo'    => $withGeo > 0,
      'threshold' => $threshold,
      'countries' => $countries,
      'regions'   => $regions,
      'masked'    => ['countryZones'=>$mcZones,'countryVisitors'=>$mcVis,'regionZones'=>$mrZones,'regionVisitors'=>$mrVis],
    ]);
    break;
  }

  case 'seo': {
    ams_json(['success' => true, 'pages' => ams_seo_scan()]);
    break;
  }

  case 'aigeo': {
    ams_json(ams_geo_scan());
    break;
  }

  case 'config-get': {
    ams_json(['success' => true, 'config' => ams_config()]);
    break;
  }

  case 'config-set': {
    $c = ams_config();
    $in = is_array($body['config'] ?? null) ? $body['config'] : [];
    if (isset($in['retention_days'])) $c['retention_days'] = max(30, min(3650, (int)$in['retention_days']));
    if (isset($in['geo_threshold']))  $c['geo_threshold']  = max(1, min(1000, (int)$in['geo_threshold']));
    if (isset($in['exclude_admin']))  $c['exclude_admin']  = (bool)$in['exclude_admin'];
    ams_save_config($c);
    ams_json(['success' => true, 'config' => $c]);
    break;
  }

  case 'purge': {
    // Applique la rétention : supprime les fichiers mensuels trop anciens
    $c = ams_config();
    $limit = gmdate('Y-m', time() - $c['retention_days'] * 86400);
    $dir = ams_data_dir();
    $deleted = [];
    foreach (glob($dir . '/events-*.ndjson') as $f) {
      if (preg_match('/events-(\d{4}-\d{2})\.ndjson$/', $f, $m) && $m[1] < $limit) {
        @unlink($f);
        $deleted[] = $m[1];
      }
    }
    ams_json(['success' => true, 'deleted' => $deleted]);
    break;
  }

  default:
    ams_json(['success' => false, 'error' => 'Action inconnue'], 400);
}

/* ============================================================
   Scan SEO des pages HTML publiques (lecture seule)
   ============================================================ */
function ams_seo_scan() {
  $root = dirname(__DIR__); // www/
  $exclude = ['gestion-realisations-9e0ac25fbcfa23f2.html']; // admin exclue
  $pages = [];
  foreach (glob($root . '/*.html') as $file) {
    $name = basename($file);
    if (in_array($name, $exclude, true)) continue;
    $html = @file_get_contents($file);
    if ($html === false) continue;

    $get = function ($re) use ($html) { return preg_match($re, $html, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES)) : ''; };

    $title  = $get('/<title[^>]*>(.*?)<\/title>/is');
    $desc   = $get('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is');
    $canon  = $get('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\'](.*?)["\']/is');
    $robots = $get('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'](.*?)["\']/is');
    $ogT    = $get('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\'](.*?)["\']/is');
    $ogD    = $get('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/is');
    $ogI    = $get('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/is');
    preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $h1m);
    $h1count = count($h1m[1]);
    $h1 = $h1count ? trim(preg_replace('/\s+/', ' ', strip_tags($h1m[1][0]))) : '';
    $noindex = stripos($robots, 'noindex') !== false;

    // Détection d'anomalies (indicateur interne, PAS une vérité Google)
    $issues = [];
    if ($title === '')                 $issues[] = ['lvl'=>'err','msg'=>'Titre manquant'];
    elseif (mb_strlen($title) < 30)    $issues[] = ['lvl'=>'warn','msg'=>'Titre court (<30)'];
    elseif (mb_strlen($title) > 65)    $issues[] = ['lvl'=>'warn','msg'=>'Titre long (>65)'];
    if ($desc === '')                  $issues[] = ['lvl'=>'err','msg'=>'Méta-description manquante'];
    elseif (mb_strlen($desc) < 70)     $issues[] = ['lvl'=>'warn','msg'=>'Description courte (<70)'];
    elseif (mb_strlen($desc) > 160)    $issues[] = ['lvl'=>'warn','msg'=>'Description longue (>160)'];
    if ($h1count === 0)                $issues[] = ['lvl'=>'err','msg'=>'H1 manquant'];
    elseif ($h1count > 1)              $issues[] = ['lvl'=>'warn','msg'=>$h1count.' H1 (idéal : 1)'];
    if ($canon === '')                 $issues[] = ['lvl'=>'warn','msg'=>'Canonical absent'];
    if ($ogI === '')                   $issues[] = ['lvl'=>'warn','msg'=>'Image Open Graph absente'];

    $score = max(0, 100 - array_reduce($issues, fn($c,$i)=>$c + ($i['lvl']==='err'?20:8), 0));

    $pages[] = [
      'file' => $name, 'url' => '/' . $name,
      'title' => $title, 'titleLen' => mb_strlen($title),
      'description' => $desc, 'descLen' => mb_strlen($desc),
      'h1' => $h1, 'h1count' => $h1count,
      'canonical' => $canon, 'robots' => $robots ?: '(défaut)',
      'ogTitle' => $ogT, 'ogDesc' => $ogD, 'ogImage' => $ogI,
      'indexable' => !$noindex,
      'issues' => $issues, 'score' => $score,
      'modified' => gmdate('Y-m-d', @filemtime($file) ?: time()),
    ];
  }
  // Détection des titres/descriptions dupliqués
  $titles = []; $descs = [];
  foreach ($pages as $p) { if ($p['title']!=='') $titles[$p['title']][]=1; if ($p['description']!=='') $descs[$p['description']][]=1; }
  foreach ($pages as &$p) {
    if ($p['title']!=='' && count($titles[$p['title']])>1)       { $p['issues'][]=['lvl'=>'warn','msg'=>'Titre dupliqué']; $p['score']=max(0,$p['score']-8); }
    if ($p['description']!=='' && count($descs[$p['description']])>1){ $p['issues'][]=['lvl'=>'warn','msg'=>'Description dupliquée']; $p['score']=max(0,$p['score']-8); }
  }
  unset($p);
  usort($pages, fn($a,$b)=>$a['score']-$b['score']);
  return $pages;
}

/* ============================================================
   Scan GEO (Generative Engine Optimization) : dans quelle mesure
   chaque page est prête à être comprise et citée par les IA
   (ChatGPT, Google AI Overviews, Perplexity, Copilot...).
   Règles transparentes, indicateur interne d'aide.
   ============================================================ */
function ams_geo_scan() {
  $root = dirname(__DIR__);
  $exclude = ['gestion-realisations-9e0ac25fbcfa23f2.html'];
  $pages = [];
  foreach (glob($root . '/*.html') as $file) {
    $name = basename($file);
    if (in_array($name, $exclude, true)) continue;
    $html = @file_get_contents($file);
    if ($html === false) continue;

    // Corps sans <script>/<style> pour l'analyse de contenu
    $body = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
    $body = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $body);
    $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($body), ENT_QUOTES)));
    $words = $text === '' ? 0 : count(preg_split('/\s+/', $text));

    $hasJsonLd = stripos($html, 'application/ld+json') !== false;
    $hasFaqSchema = stripos($html, '"FAQPage"') !== false || stripos($html, "'FAQPage'") !== false;
    preg_match_all('/<h2\b/i', $html, $h2m); $h2 = count($h2m[0]);
    preg_match_all('/<h3\b/i', $html, $h3m); $h3 = count($h3m[0]);
    preg_match_all('/<(ul|ol)\b/i', $body, $lm); $lists = count($lm[0]);
    // Questions détectées (FAQ visible)
    preg_match_all('/<(?:summary|h2|h3)[^>]*>[^<]*\?[^<]*<\/(?:summary|h2|h3)>/i', $body, $qm); $questions = count($qm[0]);
    $hasPhone = stripos($html, 'tel:') !== false;
    // Meta description (gestion correcte des apostrophes)
    $desc = preg_match('/<meta[^>]+name=["\']description["\'][^>]*content=(["\'])(.*?)\1/is', $html, $dm) ? trim($dm[2]) : '';
    $descLen = mb_strlen($desc);
    $hasOgImage = (bool)preg_match('/<meta[^>]+property=["\']og:image["\']/i', $html);

    // Score GEO (règles transparentes)
    $score = 0;
    if ($hasJsonLd)        $score += 22;
    if ($hasFaqSchema)     $score += 12;
    if ($questions >= 3)   $score += 14; elseif ($questions >= 1) $score += 6;
    if ($words >= 350)     $score += 16; elseif ($words >= 150) $score += 8;
    if ($lists >= 2)       $score += 10; elseif ($lists >= 1) $score += 5;
    if ($h2 >= 2)          $score += 10;
    if ($descLen >= 70 && $descLen <= 170) $score += 8;
    if ($hasPhone)         $score += 8;
    $score = min(100, $score);

    // Recommandations GEO
    $recos = [];
    if (!$hasJsonLd)      $recos[] = 'Ajouter des données structurées Schema.org (JSON-LD) pour que les IA identifient l\'entité et les services.';
    if (!$hasFaqSchema && $questions < 1) $recos[] = 'Ajouter une FAQ (questions/réponses) : les IA reprennent volontiers ce format.';
    if ($questions >= 1 && !$hasFaqSchema) $recos[] = 'Baliser la FAQ existante en Schema.org FAQPage.';
    if ($words < 150)     $recos[] = 'Enrichir le contenu (au moins 150–350 mots) avec des faits concrets et citables.';
    if ($lists < 1)       $recos[] = 'Structurer avec des listes à puces (les IA extraient mieux les listes).';
    if ($h2 < 2)          $recos[] = 'Hiérarchiser avec des sous-titres H2/H3 clairs et explicites.';
    if (!$hasPhone)       $recos[] = 'Afficher des faits d\'entité citables (téléphone, zone, horaires).';
    if ($descLen < 70 || $descLen > 170) $recos[] = 'Rédiger une méta-description factuelle de 70–170 caractères.';

    $pages[] = [
      'file' => $name, 'url' => '/' . $name, 'score' => $score,
      'jsonld' => $hasJsonLd, 'faqSchema' => $hasFaqSchema, 'questions' => $questions,
      'words' => $words, 'lists' => $lists, 'h2' => $h2, 'h3' => $h3,
      'phone' => $hasPhone, 'descLen' => $descLen, 'ogImage' => $hasOgImage,
      'recos' => $recos,
    ];
  }
  usort($pages, fn($a, $b) => $a['score'] - $b['score']);

  // Fichiers destinés aux IA / moteurs
  $robots = @file_get_contents($root . '/robots.txt');
  $site = [
    'llmsTxt'    => is_file($root . '/llms.txt'),
    'robotsTxt'  => is_file($root . '/robots.txt'),
    'robotsAI'   => $robots !== false && (stripos($robots, 'GPTBot') !== false || stripos($robots, 'PerplexityBot') !== false),
    'sitemap'    => is_file($root . '/sitemap.xml'),
    'jsonldPages'=> count(array_filter($pages, fn($p) => $p['jsonld'])),
    'faqPages'   => count(array_filter($pages, fn($p) => $p['faqSchema'])),
    'total'      => count($pages),
  ];

  return ['success' => true, 'pages' => $pages, 'site' => $site];
}

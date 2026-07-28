<?php
/* ============================================================
   api/search-console.php — Google Search Console (PROTÉGÉ)
   Actions : status | set-config | clear | test | query
   Authentification Google par compte de service (JWT RS256).
   Les identifiants Google restent CÔTÉ SERVEUR (dossier privé),
   jamais renvoyés au navigateur.
   ============================================================ */

require __DIR__ . '/_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  ams_json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}
$body   = ams_require_admin();
$action = isset($body['action']) ? (string)$body['action'] : 'status';

function ams_gsc_dir() { $d = ams_data_dir() . '/gsc'; if (!is_dir($d)) @mkdir($d, 0755, true); return $d; }
function ams_gsc_cfg_file() { return ams_gsc_dir() . '/config.json'; }
function ams_gsc_config() {
  $f = ams_gsc_cfg_file();
  if (!is_file($f)) return null;
  $c = json_decode((string)@file_get_contents($f), true);
  return is_array($c) ? $c : null;
}

/* ---------- STATUS ---------- */
if ($action === 'status') {
  $c = ams_gsc_config();
  ams_json([
    'success'   => true,
    'configured'=> $c !== null,
    'clientEmail' => $c['client_email'] ?? '',
    'siteUrl'   => $c['site_url'] ?? '',
    'hasOpenssl'=> function_exists('openssl_sign'),
    'hasCurl'   => function_exists('curl_init'),
  ]);
}

/* ---------- SET CONFIG ---------- */
if ($action === 'set-config') {
  $raw = (string)($body['serviceAccount'] ?? '');
  $site = trim((string)($body['siteUrl'] ?? ''));
  $sa = json_decode($raw, true);
  if (!is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
    ams_json(['success' => false, 'error' => 'JSON de compte de service invalide (client_email / private_key manquants)'], 400);
  }
  if ($site === '') ams_json(['success' => false, 'error' => "URL de la propriété manquante"], 400);
  $cfg = ['client_email' => $sa['client_email'], 'private_key' => $sa['private_key'], 'site_url' => $site];
  if (@file_put_contents(ams_gsc_cfg_file(), json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
    ams_json(['success' => false, 'error' => "Écriture impossible (droits du dossier stats-data/gsc ?)"], 500);
  }
  @chmod(ams_gsc_cfg_file(), 0600);
  @unlink(ams_gsc_dir() . '/token.json');
  ams_json(['success' => true, 'clientEmail' => $sa['client_email'], 'siteUrl' => $site]);
}

/* ---------- CLEAR ---------- */
if ($action === 'clear') {
  @unlink(ams_gsc_cfg_file());
  @unlink(ams_gsc_dir() . '/token.json');
  ams_json(['success' => true]);
}

/* ===== Helpers OAuth (compte de service → access token) ===== */
function ams_b64url($d) { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }

function ams_gsc_access_token($cfg) {
  // Cache du jeton (~55 min)
  $tf = ams_gsc_dir() . '/token.json';
  if (is_file($tf)) {
    $t = json_decode((string)@file_get_contents($tf), true);
    if (is_array($t) && ($t['exp'] ?? 0) > time() + 60) return $t['token'];
  }
  if (!function_exists('openssl_sign')) throw new RuntimeException('openssl indisponible sur le serveur');

  $now = time();
  $header = ams_b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
  $claim  = ams_b64url(json_encode([
    'iss'   => $cfg['client_email'],
    'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
    'aud'   => 'https://oauth2.googleapis.com/token',
    'exp'   => $now + 3600,
    'iat'   => $now,
  ]));
  $signingInput = $header . '.' . $claim;
  $sig = '';
  if (!openssl_sign($signingInput, $sig, $cfg['private_key'], 'SHA256')) {
    throw new RuntimeException('Signature JWT impossible (clé privée invalide ?)');
  }
  $jwt = $signingInput . '.' . ams_b64url($sig);

  $post = http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion'  => $jwt,
  ]);
  [$code, $resp] = ams_gsc_http('https://oauth2.googleapis.com/token', $post, []);
  $j = json_decode($resp, true);
  if ($code !== 200 || empty($j['access_token'])) {
    throw new RuntimeException('Échec OAuth Google (' . $code . ') : ' . ($j['error_description'] ?? $j['error'] ?? 'inconnu'));
  }
  @file_put_contents($tf, json_encode(['token' => $j['access_token'], 'exp' => $now + (int)($j['expires_in'] ?? 3600)]));
  @chmod($tf, 0600);
  return $j['access_token'];
}

function ams_gsc_http($url, $body, $headers) {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
      CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
      CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, (string)$resp];
  }
  $ctx = stream_context_create(['http' => [
    'method' => 'POST', 'header' => implode("\r\n", array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers)),
    'content' => $body, 'ignore_errors' => true, 'timeout' => 30,
  ]]);
  $resp = @file_get_contents($url, false, $ctx);
  $code = 0;
  if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
  return [$code, (string)$resp];
}

function ams_gsc_api($cfg, $token, $path, $payload) {
  $url = 'https://searchconsole.googleapis.com/webmasters/v3/sites/'
       . rawurlencode($cfg['site_url']) . $path;
  [$code, $resp] = ams_gsc_http($url, json_encode($payload), [
    'Authorization: Bearer ' . $token, 'Content-Type: application/json',
  ]);
  $j = json_decode($resp, true);
  if ($code !== 200) throw new RuntimeException('API Search Console (' . $code . ') : ' . ($j['error']['message'] ?? 'erreur'));
  return is_array($j) ? $j : [];
}

/* ---------- TEST ---------- */
if ($action === 'test') {
  $cfg = ams_gsc_config();
  if (!$cfg) ams_json(['success' => false, 'error' => 'Non configuré'], 400);
  try {
    $token = ams_gsc_access_token($cfg);
    // Petite requête sur 7 jours pour valider l'accès à la propriété
    $r = ams_gsc_api($cfg, $token, '/searchAnalytics/query', [
      'startDate' => gmdate('Y-m-d', time() - 10 * 86400),
      'endDate'   => gmdate('Y-m-d', time() - 3 * 86400),
      'rowLimit'  => 1,
    ]);
    ams_json(['success' => true, 'message' => 'Connexion Google OK', 'rows' => count($r['rows'] ?? [])]);
  } catch (Throwable $e) {
    ams_json(['success' => false, 'error' => $e->getMessage()], 502);
  }
}

/* ---------- MOTS-CLÉS & POSITIONS ---------- */
function ams_gsc_kw_file() { return ams_gsc_dir() . '/keywords.json'; }
function ams_gsc_default_keywords() {
  return [
    'plombier chambéry','dépannage plomberie chambéry','plombier savoie',
    'électricien chambéry','dépannage électrique chambéry','électricien savoie',
    'serrurier chambéry','ouverture de porte chambéry','serrurier urgence chambéry',
    'dépannage urgence chambéry','dépannage 24h/24 chambéry','dépannage savoie',
    'volet roulant chambéry','réparation volet roulant chambéry',
    'plombier annecy','serrurier annecy','dépannage annecy',
    'nettoyage toiture chambéry','nettoyage terrasse chambéry','bricolage à domicile chambéry',
  ];
}
function ams_gsc_keywords_list() {
  $f = ams_gsc_kw_file();
  if (is_file($f)) { $j = json_decode(@file_get_contents($f), true); if (is_array($j) && $j) return $j; }
  return ams_gsc_default_keywords();
}

if ($action === 'get-keywords') {
  ams_json(['success' => true, 'keywords' => ams_gsc_keywords_list()]);
}
if ($action === 'set-keywords') {
  $kw = $body['keywords'] ?? [];
  if (!is_array($kw)) ams_json(['success' => false, 'error' => 'Liste invalide'], 400);
  $clean = [];
  foreach ($kw as $k) { $k = trim(mb_strtolower((string)$k)); if ($k !== '' && mb_strlen($k) <= 80) $clean[] = $k; }
  $clean = array_values(array_unique($clean));
  @file_put_contents(ams_gsc_kw_file(), json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  ams_json(['success' => true, 'keywords' => $clean]);
}
if ($action === 'keywords') {
  $cfg = ams_gsc_config();
  if (!$cfg) ams_json(['success' => false, 'error' => 'Non configuré'], 400);
  $to   = ams_gsc_date($body['to']   ?? '', gmdate('Y-m-d', time() - 3 * 86400));
  $from = ams_gsc_date($body['from'] ?? '', gmdate('Y-m-d', time() - 90 * 86400));
  try {
    $token = ams_gsc_access_token($cfg);
    $res = ams_gsc_api($cfg, $token, '/searchAnalytics/query', ['startDate'=>$from,'endDate'=>$to,'dimensions'=>['query'],'rowLimit'=>250]);
    $rows = array_map(function ($r) {
      return ['query'=>$r['keys'][0]??'','clicks'=>$r['clicks']??0,'impressions'=>$r['impressions']??0,
              'ctr'=>round(($r['ctr']??0)*100,1),'position'=>round($r['position']??0,1)];
    }, $res['rows'] ?? []);
    $b = ['top3'=>0,'top10'=>0,'top20'=>0,'beyond'=>0];
    foreach ($rows as $r) { $p=$r['position']; if($p<=3)$b['top3']++; elseif($p<=10)$b['top10']++; elseif($p<=20)$b['top20']++; else $b['beyond']++; }
    $avg = count($rows) ? round(array_sum(array_column($rows,'position'))/count($rows),1) : 0;

    $byq = []; foreach ($rows as $r) $byq[mb_strtolower($r['query'])] = $r;
    $matched = [];
    foreach (ams_gsc_keywords_list() as $t) {
      $tl = mb_strtolower($t);
      $hit = $byq[$tl] ?? null;
      if (!$hit) {
        $words = array_filter(preg_split('/\s+/', $tl)); $best = null;
        foreach ($rows as $r) {
          $q = mb_strtolower($r['query']); $ok = true;
          foreach ($words as $w) { if (mb_strpos($q, $w) === false) { $ok = false; break; } }
          if ($ok && ($best === null || $r['position'] < $best['position'])) $best = $r;
        }
        $hit = $best;
      }
      $matched[] = ['keyword'=>$t,'found'=>$hit!==null,'position'=>$hit?$hit['position']:null,
        'clicks'=>$hit?$hit['clicks']:0,'impressions'=>$hit?$hit['impressions']:0,'ctr'=>$hit?$hit['ctr']:0,
        'matchedQuery'=>$hit?$hit['query']:''];
    }
    usort($matched, function ($a,$b) { if ($a['found']!==$b['found']) return $a['found']?-1:1; return ($a['position']??999)<=>($b['position']??999); });
    usort($rows, fn($a,$b) => $a['position'] <=> $b['position']);

    ams_json(['success'=>true,'range'=>['from'=>$from,'to'=>$to],'avg'=>$avg,'buckets'=>$b,
      'total'=>count($rows),'targets'=>$matched,'queries'=>array_slice($rows,0,100)]);
  } catch (Throwable $e) { ams_json(['success'=>false,'error'=>$e->getMessage()], 502); }
}

/* ---------- QUERY ---------- */
if ($action !== 'query') ams_json(['success' => false, 'error' => 'Action inconnue'], 400);

$cfg = ams_gsc_config();
if (!$cfg) ams_json(['success' => false, 'error' => 'Non configuré'], 400);

function ams_gsc_date($d, $fb) { return (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) ? $d : $fb; }
// GSC a ~3 jours de latence
$to   = ams_gsc_date($body['to']   ?? '', gmdate('Y-m-d', time() - 3 * 86400));
$from = ams_gsc_date($body['from'] ?? '', gmdate('Y-m-d', time() - 32 * 86400));

try {
  $token = ams_gsc_access_token($cfg);
  $totalsRaw = ams_gsc_api($cfg, $token, '/searchAnalytics/query', ['startDate'=>$from,'endDate'=>$to,'rowLimit'=>1]);
  $tot = $totalsRaw['rows'][0] ?? null;
  $series = ams_gsc_api($cfg, $token, '/searchAnalytics/query', ['startDate'=>$from,'endDate'=>$to,'dimensions'=>['date'],'rowLimit'=>500]);
  $queries = ams_gsc_api($cfg, $token, '/searchAnalytics/query', ['startDate'=>$from,'endDate'=>$to,'dimensions'=>['query'],'rowLimit'=>25]);
  $pages   = ams_gsc_api($cfg, $token, '/searchAnalytics/query', ['startDate'=>$from,'endDate'=>$to,'dimensions'=>['page'],'rowLimit'=>25]);

  $map = fn($rows) => array_map(fn($r) => [
    'key' => $r['keys'][0] ?? '', 'clicks' => $r['clicks'] ?? 0, 'impressions' => $r['impressions'] ?? 0,
    'ctr' => round(($r['ctr'] ?? 0) * 100, 1), 'position' => round($r['position'] ?? 0, 1),
  ], $rows['rows'] ?? []);

  // Opportunités : beaucoup d'impressions, peu de clics (CTR faible) ou position 8-20
  $opps = [];
  foreach ($map($pages) as $p) {
    if ($p['impressions'] >= 50 && $p['ctr'] < 2) $opps[] = ['page'=>$p['key'],'reason'=>$p['impressions'].' impressions mais CTR '.$p['ctr'].' % — retravailler titre/description.'];
    elseif ($p['position'] >= 8 && $p['position'] <= 20 && $p['impressions'] >= 30) $opps[] = ['page'=>$p['key'],'reason'=>'Position moyenne '.$p['position'].' (proche de la 1re page) — un petit effort peut la faire remonter.'];
  }

  ams_json([
    'success' => true,
    'range'   => ['from'=>$from,'to'=>$to],
    'totals'  => $tot ? ['clicks'=>$tot['clicks']??0,'impressions'=>$tot['impressions']??0,'ctr'=>round(($tot['ctr']??0)*100,1),'position'=>round($tot['position']??0,1)] : ['clicks'=>0,'impressions'=>0,'ctr'=>0,'position'=>0],
    'series'  => $map($series),
    'queries' => $map($queries),
    'pages'   => $map($pages),
    'opportunities' => array_slice($opps, 0, 12),
  ]);
} catch (Throwable $e) {
  ams_json(['success' => false, 'error' => $e->getMessage()], 502);
}

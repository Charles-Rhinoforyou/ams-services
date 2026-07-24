<?php
/* ============================================================
   api/geoip.php — Lecteur MaxMind GeoLite2 (.mmdb) 100% PHP
   Aucune extension ni dépendance externe. Utilisé côté serveur
   uniquement, au moment de la collecte : l'IP sert à déduire
   pays/région PUIS est jetée (jamais stockée).
   Format MMDB : https://maxmind.github.io/MaxMind-DB/
   ============================================================ */

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'geoip.php') { http_response_code(404); exit; }

function ams_geoip_db_path() {
  if (function_exists('ams_data_dir')) return ams_data_dir() . '/geoip/GeoLite2-City.mmdb';
  return __DIR__ . '/../stats-data/geoip/GeoLite2-City.mmdb';
}
function ams_geoip_available() { return is_file(ams_geoip_db_path()); }

/* Recherche pays + région pour une IP. Renvoie ['cc','country','region'] ou null. */
function ams_geo_lookup($ip) {
  try {
    $path = ams_geoip_db_path();
    if ($ip === null || !is_file($path)) return null;
    $rec = (new AmsMmdb($path))->get($ip);
    if (!is_array($rec)) return null;
    $cc      = $rec['country']['iso_code'] ?? '';
    $country = $rec['country']['names']['fr'] ?? ($rec['country']['names']['en'] ?? $cc);
    $region  = '';
    if (!empty($rec['subdivisions'][0]['names'])) {
      $region = $rec['subdivisions'][0]['names']['fr'] ?? ($rec['subdivisions'][0]['names']['en'] ?? '');
    }
    return ['cc' => $cc, 'country' => $country, 'region' => $region];
  } catch (Throwable $e) {
    return null; // dégradation silencieuse : ne jamais bloquer la collecte
  }
}

/* -------- Lecteur MMDB minimal -------- */
class AmsMmdb {
  private $fh; private $nodeCount; private $recordSize; private $ipVersion;
  private $searchTreeSize; private $dataStart; private $decoder;

  public function __construct($file) {
    $this->fh = fopen($file, 'rb');
    if (!$this->fh) throw new RuntimeException('open failed');
    $this->readMetadata();
  }
  public function __destruct() { if ($this->fh) @fclose($this->fh); }

  private function readMetadata() {
    $marker = "\xAB\xCD\xEFMaxMind.com";
    $size = fstat($this->fh)['size'];
    $chunk = min($size, 128 * 1024);
    fseek($this->fh, $size - $chunk);
    $tail = fread($this->fh, $chunk);
    $pos = strrpos($tail, $marker);
    if ($pos === false) throw new RuntimeException('metadata marker not found');
    $metaOffset = ($size - $chunk) + $pos + strlen($marker);
    $dec = new AmsMmdbDecoder($this->fh, 0);
    [$meta] = $dec->decode($metaOffset);
    $this->nodeCount  = $meta['node_count'];
    $this->recordSize = $meta['record_size'];
    $this->ipVersion  = $meta['ip_version'];
    $this->searchTreeSize = intval(($this->recordSize / 4) * $this->nodeCount);
    $this->dataStart  = $this->searchTreeSize + 16;
    $this->decoder    = new AmsMmdbDecoder($this->fh, $this->dataStart);
  }

  public function get($ip) {
    $bits = $this->ipToBits($ip);
    if ($bits === null) return null;
    $node = 0;
    for ($i = 0, $len = strlen($bits); $i < $len; $i++) {
      if ($node >= $this->nodeCount) break;
      $node = $this->readNode($node, (int)$bits[$i]);
    }
    if ($node === $this->nodeCount) return null;
    if ($node > $this->nodeCount) {
      $offset = $node - $this->nodeCount + $this->searchTreeSize;
      [$data] = $this->decoder->decode($offset);
      return $data;
    }
    return null;
  }

  private function readNode($node, $index) {
    $baseOffset = $node * ($this->recordSize / 4);
    if ($this->recordSize === 24) {
      fseek($this->fh, $baseOffset + $index * 3);
      $b = fread($this->fh, 3);
      return (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
    } elseif ($this->recordSize === 28) {
      fseek($this->fh, $baseOffset);
      $b = fread($this->fh, 7);
      if ($index === 0) {
        return (((ord($b[3]) & 0xF0) >> 4) << 24) | (ord($b[0]) << 16) | (ord($b[1]) << 8) | ord($b[2]);
      }
      return ((ord($b[3]) & 0x0F) << 24) | (ord($b[4]) << 16) | (ord($b[5]) << 8) | ord($b[6]);
    } else {
      fseek($this->fh, $baseOffset + $index * 4);
      $b = fread($this->fh, 4);
      return (ord($b[0]) << 24) | (ord($b[1]) << 16) | (ord($b[2]) << 8) | ord($b[3]);
    }
  }

  private function ipToBits($ip) {
    $packed = @inet_pton($ip);
    if ($packed === false) return null;
    if (strlen($packed) === 4 && $this->ipVersion === 6) $packed = str_repeat("\x00", 12) . $packed;
    $bits = '';
    for ($i = 0, $n = strlen($packed); $i < $n; $i++) {
      $bits .= str_pad(decbin(ord($packed[$i])), 8, '0', STR_PAD_LEFT);
    }
    return $bits;
  }
}

/* -------- Décodeur de données MMDB -------- */
class AmsMmdbDecoder {
  private $fh; private $pointerBase;
  public function __construct($fh, $pointerBase) { $this->fh = $fh; $this->pointerBase = $pointerBase; }

  private function read($offset, $n) { if ($n <= 0) return ''; fseek($this->fh, $offset); return fread($this->fh, $n); }
  private function uint($bytes) { $v = 0; for ($i = 0, $n = strlen($bytes); $i < $n; $i++) $v = ($v << 8) | ord($bytes[$i]); return $v; }

  /* Renvoie [valeur, nouvelOffset] */
  public function decode($offset) {
    $ctrl = ord($this->read($offset, 1)); $offset++;
    $type = $ctrl >> 5;
    if ($type === 1) { // pointer
      $psize = ($ctrl >> 3) & 0x3;
      $buf = $this->read($offset, $psize + 1); $offset += $psize + 1;
      $packed = ($psize === 3) ? $buf : chr($ctrl & 0x7) . $buf;
      $val = $this->uint($packed);
      if ($psize === 1) $val += 2048; elseif ($psize === 2) $val += 526336;
      [$data] = $this->decode($this->pointerBase + $val);
      return [$data, $offset];
    }
    $size = $ctrl & 0x1F;
    if ($size >= 29) {
      if ($size === 29) { $size = 29 + ord($this->read($offset, 1)); $offset += 1; }
      elseif ($size === 30) { $size = 285 + $this->uint($this->read($offset, 2)); $offset += 2; }
      else { $size = 65821 + $this->uint($this->read($offset, 3)); $offset += 3; }
    }
    if ($type === 0) { $type = 7 + ord($this->read($offset, 1)); $offset += 1; }
    switch ($type) {
      case 2: $s = $this->read($offset, $size); return [$s, $offset + $size];
      case 5: case 6: case 9: case 10:
        return [$this->uint($this->read($offset, $size)), $offset + $size];
      case 8:
        return [$this->uint($this->read($offset, $size)), $offset + $size];
      case 7:
        $map = [];
        for ($i = 0; $i < $size; $i++) {
          [$k, $offset] = $this->decode($offset);
          [$v, $offset] = $this->decode($offset);
          $map[$k] = $v;
        }
        return [$map, $offset];
      case 11:
        $arr = [];
        for ($i = 0; $i < $size; $i++) { [$v, $offset] = $this->decode($offset); $arr[] = $v; }
        return [$arr, $offset];
      case 14: return [$size !== 0, $offset];
      case 3:  return [unpack('E', $this->read($offset, 8))[1] ?? 0, $offset + 8];
      case 15: return [unpack('G', $this->read($offset, 4))[1] ?? 0, $offset + 4];
      case 4:  $b = $this->read($offset, $size); return [$b, $offset + $size];
      default: return [null, $offset + $size];
    }
  }
}

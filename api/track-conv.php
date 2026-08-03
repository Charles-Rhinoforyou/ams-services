<?php
/* ============================================================
   api/track-conv.php — Comptage de conversions STRICTEMENT ANONYME
   -----------------------------------------------------------
   Compte les clics sur les boutons de contact (téléphone, WhatsApp,
   e-mail) et l'envoi du formulaire de devis, SANS consentement requis
   car AUCUNE donnée personnelle n'est traitée :
     • pas de cookie, pas de localStorage
     • pas d'adresse IP (ni en clair ni hachée)
     • pas d'identifiant de visiteur, pas de device, pas de referrer
   On n'enregistre que : le JOUR, le type d'action, et la page.
   → mesure agrégée et anonyme (non identifiante).
   ============================================================ */

require __DIR__ . '/_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  ams_json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

$raw = file_get_contents('php://input');
if (strlen($raw) > 2048) {
  ams_json(['success' => false, 'error' => 'Payload trop grand'], 413);
}
$body = json_decode($raw, true);
if (!is_array($body)) {
  ams_json(['success' => false, 'error' => 'Requête invalide'], 400);
}

/* Seules les actions de contact / conversion sont acceptées */
$ALLOWED = ['phone_click', 'whatsapp_click', 'email_click', 'quote_request', 'form_submit'];
$event = isset($body['event']) ? (string)$body['event'] : '';
if (!in_array($event, $ALLOWED, true)) {
  ams_json(['success' => false, 'error' => 'Événement inconnu'], 400);
}

/* Page : identifiant court non sensible (ex. "plomberie", "urgence") */
$pid = preg_replace('/[^A-Za-z0-9_\-]/', '', substr((string)($body['page_id'] ?? ''), 0, 40));

/* Enregistrement anonyme : jour + action + page, rien d'autre. */
$rec = ['d' => gmdate('Y-m-d'), 'e' => $event, 'pid' => $pid];

$file = ams_data_dir() . '/conv-' . gmdate('Y-m') . '.ndjson';
@file_put_contents(
  $file,
  json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
  FILE_APPEND | LOCK_EX
);

ams_json(['success' => true]);

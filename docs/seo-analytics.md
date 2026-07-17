# Tableau de bord « SEO & Statistiques » — Documentation

Onglet ajouté à l'administration existante
(`gestion-realisations-9e0ac25fbcfa23f2.html`) pour suivre l'audience,
le SEO et les conversions du site **ams-services-depannage.fr**, dans le
respect du RGPD et sans base de données.

## 1. Architecture retenue

Site **statique HTML/JS + PHP sur OVH mutualisé** (pas de Node, pas de
base SQL). La mesure d'audience est donc **maison, en PHP + fichiers plats**,
avec une **interface d'abstraction** permettant de brancher plus tard
Matomo ou GA4 sans réécrire le tableau de bord.

```
Navigateur (public)                 Serveur OVH (www/)
─────────────────                   ──────────────────
tracker.js  ──POST──►  api/track.php  ──►  stats-data/events-AAAA-MM.ndjson
(si consent)                                stats-data/salt.txt  (hash IP)
                                            stats-data/config.json

Admin (gestion-…html)                Serveur OVH
─────────────────                   ──────────────────
onglet SEO & Stats ──POST(mdp)──►  api/stats.php  ──►  lit stats-data/ + scanne *.html
```

## 2. Fichiers créés

| Fichier | Rôle |
|---|---|
| `api/_common.php` | Utilitaires : auth SHA-256, dossier de données protégé, hash IP salé, type d'appareil, config. |
| `api/track.php` | **Public.** Reçoit les événements. N'enregistre **que si `consent = accepted`**. Whitelist d'événements, valeurs tronquées, IP hachée (jour), aucun contenu de formulaire. |
| `api/stats.php` | **Protégé (mot de passe).** Agrégations : overview, timeseries, interventions, conversions, seo, config, purge. |
| `tracker.js` | Mesure côté navigateur, chargée par `components.js`. Auto-instrumente tel/mailto/WhatsApp/liens externes, page_view, scroll, formulaires marqués. Ne s'active qu'après consentement. |
| `docs/seo-analytics.md` | Ce document. |
| `.env.example` | Variables serveur futures (Search Console, Matomo). Aucun secret. |
| `.gitignore` | Exclut `stats-data/` du dépôt. |

## 3. Fichiers modifiés

- `gestion-realisations-9e0ac25fbcfa23f2.html` : onglets « Réalisations / SEO & Statistiques », sous-sections, KPIs, graphe canvas, tableau SEO, recommandations, config RGPD. **L'éditeur de réalisations existant est inchangé.**
- `components.js` : chargement de `tracker.js`, signal `ams-consent-accepted`, marquage du formulaire de devis.
- `index.html` : marquage du formulaire de contact (`data-analytics-form`).
- `.github/workflows/deploy.yml` : exclut `stats-data/**`, `images/**`, `portfolio-data.js` du déploiement FTP (données gérées côté serveur, jamais écrasées).

## 4. Stockage & confidentialité des données

- **Dossier `stats-data/`** créé automatiquement à la première collecte, **hors du dépôt** et **protégé par `.htaccess`** (`Require all denied`). Non déployé par git.
- **IP** : jamais stockée en clair. On stocke `sha256(IP + User-Agent + sel + date_du_jour)` tronqué → estimation des visiteurs uniques **par jour**, impossible à relier d'un jour à l'autre.
- **Aucune donnée personnelle**, aucun contenu de formulaire, aucune coordonnée. Seuls des identifiants d'éléments stables (`data-analytics-id`) et des paramètres non sensibles.
- **Rétention** configurable (défaut 400 j) + bouton de purge.

## 5. Sécurité

- L'API d'écriture/lecture privée (`stats.php`, `publier`, `uploader`) exige le **mot de passe admin vérifié en SHA-256 côté serveur** (`hash_equals`). Le mot de passe en clair n'est jamais dans le code servi.
- `track.php` est public **par nécessité** (collecte) mais n'accepte qu'une whitelist d'événements, refuse les payloads > 8 Ko, et ignore tout sans consentement.
- ⚠️ **Limite connue** : l'écran de connexion admin reste un **gate côté navigateur** (l'URL secrète + JS). Les écritures sont protégées côté serveur, mais pour durcir l'accès, voir §8 (migration session serveur recommandée).

## 6. Ajouter un type d'intervention / un événement

- **Type d'intervention** : ajouter une entrée dans `INTERVENTION_MAP` de `tracker.js` (fichier → type), ou poser `<meta name="ams-intervention" content="…">` sur la page.
- **Nouvel événement** : l'ajouter à la whitelist `$ALLOWED` de `api/track.php`, puis émettre `window.amsTrack('mon_event', {element_id:'…'})` ou poser `data-analytics-action="mon_event"` sur l'élément.

## 7. Search Console / Géographie (Phase suivante, « Non configuré »)

- **Search Console** : prévoir un `api/search-console.php` (compte de service Google, variables `GSC_*` de `.env.example`) interrogé **uniquement côté serveur**. L'onglet affiche un état « Non configuré » propre tant que ce n'est pas branché.
- **Géographie** : nécessite une base GeoIP (ou Matomo). Prévu avec seuil de confidentialité (masquage des zones < N visiteurs). Onglet en « Non configuré » pour l'instant — **aucune fausse donnée** n'est affichée.

## 8. Recommandation sécurité — migration vers une vraie session serveur

Pour une authentification robuste (au-delà du gate JS actuel) :
1. `api/auth.php` : POST identifiant+mot de passe → vérifie le hash → crée une session PHP.
2. Cookie de session **HttpOnly + Secure + SameSite=Strict**.
3. Jeton **CSRF** exigé pour toute écriture.
4. **Limitation des tentatives** (ex. 5/min par IP).
5. Les pages/API admin vérifient la session au lieu du mot de passe par requête.

Cette migration n'est pas encore appliquée pour **ne pas casser l'accès actuel** ; elle est documentée ici comme prochaine étape.

## 9. Déploiement OVH

Inchangé : `git push origin master` → GitHub Actions FTP → `www/`. Les
fichiers PHP (`api/…`) partent dans `www/api/`. Le dossier `stats-data/`
est créé par PHP sur le serveur et **exclu du déploiement** (jamais écrasé).

⚠️ **Cette fonctionnalité est sur la branche `feature/admin-seo-analytics`.**
Elle ne sera en ligne qu'après fusion dans `master`.

## 10. Test

- **Local** : `tracker.js` et l'interface se testent, mais les API PHP
  nécessitent un serveur PHP (donc OVH). En aperçu local, les onglets
  affichent proprement un état d'erreur/vide.
- **En ligne** : ouvrir l'admin → onglet SEO & Stats. Naviguer sur le site
  public en acceptant les cookies, puis actualiser le tableau de bord.

## 11. Limites connues

- Estimation « visiteurs » = unique **par jour** (choix de confidentialité).
- Stockage fichiers plats : adapté à un site vitrine, pas à un très gros trafic (prévoir SQLite/MySQL au besoin).
- Édition des métadonnées SEO depuis l'interface : **lecture seule** en Phase 1 (écriture des `.html` à faire en Phase 3, avec historique + redirections 301 si changement d'URL).
- Search Console & Géographie : non branchés (états « Non configuré »).

// ============================================================
//  portfolio-data.js — Données du portfolio AMS'SERVICES
//  Éditer via admin.html  →  Exporter  →  remplacer ce fichier  →  git push
// ============================================================
const PORTFOLIO_DATA = {
  version: "1.0",
  lastUpdated: "2026-06-12",

  hero: {
    titre: "Nos <em>Réalisations</em> en Savoie",
    description: "Plomberie, électricité, volets roulants, bricolage, décoration… Retrouvez ici nos chantiers réalisés chez des particuliers et professionnels à Chambéry et en Savoie.",
    stats: [
      { num: "7",     label: "Types de services" },
      { num: "100%",  label: "Clients satisfaits" },
      { num: "24h/24",label: "Disponibilité urgences" }
    ]
  },

  sections: [
    {
      id: "toiture",
      icon: "🏠",
      tag: "Nettoyage de Toiture",
      titreSection: "Démoussage & Traitement de Toiture",
      descriptionSection: "Nettoyage haute pression, démoussage et traitement anti-végétation. Résultat visible immédiatement, protection longue durée.",
      lienService: "nettoyage-toiture.html",
      nomService: "Nettoyage de Toiture",
      realisations: [
        {
          id: "toiture-1",
          titrePrincipal: "Démoussage de toiture — Avant / Après",
          titreSecondaire: "Nettoyage haute pression & traitement hydrofuge",
          descriptif: "Toiture tuiles entièrement démoussée, nettoyée à haute pression et traitée avec un produit hydrofuge. Résultat immédiat et protection longue durée contre la végétation.",
          dateRealisation: "2024-03",
          ville: "Chambéry",
          type: "avant-apres",
          imagePrincipale: { src: "images/toiture-avant-apres.png", alt: "Avant/après nettoyage toiture Chambéry – démoussage haute pression", label: "✨ Avant / Après — Démoussage haute pression & traitement anti-mousse" },
          imagesSecondaires: []
        }
      ]
    },
    {
      id: "plomberie",
      icon: "🔧",
      tag: "Plomberie & Sanitaire",
      titreSection: "Installations & Rénovations Plomberie",
      descriptionSection: "Remplacement de chauffe-eau, rénovation de salle de bain, installation de douches et sanitaires, réparation de réseaux.",
      lienService: "plomberie.html",
      nomService: "Plomberie",
      realisations: [
        {
          id: "plomberie-1",
          titrePrincipal: "Rénovation complète de douche",
          titreSecondaire: "Pose carrelage & plomberie complète",
          descriptif: "Démolition de l'ancienne douche, pose de carrelage, installation de la robinetterie et du receveur. Résultat clé en main avec douche à l'italienne.",
          dateRealisation: "2024-01",
          ville: "Chambéry",
          type: "avant-apres",
          imagePrincipale: { src: "images/plomberie-douche-avant-apres.jpg", alt: "Rénovation douche avant après – AMS Services Chambéry", label: "🚿 Rénovation complète de douche — Avant / Après" },
          imagesSecondaires: [
            { src: "images/plomberie-douche-italienne.jpg",   alt: "Douche à l'italienne avec carrelage", label: "🚿 Douche italienne — Carrelage" },
            { src: "images/plomberie-douche-italienne-2.jpg", alt: "Douche italienne vue de dessus",       label: "🚿 Vue de dessus" }
          ]
        },
        {
          id: "plomberie-2",
          titrePrincipal: "Remplacement réducteur de pression immeuble",
          titreSecondaire: "Réseaux d'eau collective",
          descriptif: "Remplacement du réducteur de pression vétuste sur colonne d'immeuble. Intervention propre et rapide avec remise en eau le jour même.",
          dateRealisation: "2024-02",
          ville: "Chambéry",
          type: "avant-apres",
          imagePrincipale: { src: "images/plomberie-reducteur-avant-apres.jpg", alt: "Remplacement réducteur de pression avant après", label: "💧 Réducteur de pression — Avant / Après" },
          imagesSecondaires: [
            { src: "images/plomberie-reseau-avant.jpg",    alt: "Réseau plomberie avant rénovation",       label: "🔧 Réseau vétuste — Avant" },
            { src: "images/plomberie-chaudiere-apres.jpg", alt: "Raccordements chaudière après rénovation", label: "♨️ Raccordements — Après" }
          ]
        },
        {
          id: "plomberie-3",
          titrePrincipal: "Installation chauffe-eau Thermor 100L",
          titreSecondaire: "Pose et raccordement complet",
          descriptif: "Remplacement de l'ancien chauffe-eau par un modèle Thermor 100L. Pose, raccordements eau et électricité, mise en service et essais.",
          dateRealisation: "2024-04",
          ville: "La Motte-Servolex",
          type: "standard",
          imagePrincipale: { src: "images/plomberie-chauffe-eau.jpg", alt: "Installation chauffe-eau Thermor Chambéry", label: "🔥 Installation chauffe-eau — Avant / Après" },
          imagesSecondaires: []
        },
        {
          id: "plomberie-4",
          titrePrincipal: "Installation WC suspendu",
          titreSecondaire: "Salle de bain carrelée",
          descriptif: "Pose d'un WC suspendu avec bâti-support, carrelage mural et revêtement de sol. Finition soignée.",
          dateRealisation: "2024-05",
          ville: "Cognin",
          type: "standard",
          imagePrincipale: { src: "images/plomberie-wc.jpg", alt: "Installation WC suspendu salle de bain carrelée", label: "🚽 Installation WC suspendu" },
          imagesSecondaires: []
        },
        {
          id: "plomberie-5",
          titrePrincipal: "Pose de baignoire encastrée",
          titreSecondaire: "Salle de bain rénovée",
          descriptif: "Installation complète d'une baignoire encastrée avec robinetterie et carrelage coordonné. Travail soigné dans le respect du délai convenu.",
          dateRealisation: "2024-03",
          ville: "Aix-les-Bains",
          type: "standard",
          imagePrincipale: { src: "images/plomberie-baignoire.jpg", alt: "Pose baignoire dans salle de bain carrelée", label: "🛁 Pose de baignoire" },
          imagesSecondaires: []
        }
      ]
    },
    {
      id: "electricite",
      icon: "⚡",
      tag: "Électricité",
      titreSection: "Tableaux Électriques & Installations",
      descriptionSection: "Remplacement de tableaux vétustes, mise aux normes NFC 15-100, installation de prises et câblage. Travail propre et sécurisé.",
      lienService: "electricite.html",
      nomService: "Électricité",
      realisations: [
        {
          id: "elec-1",
          titrePrincipal: "Tableau électrique Hager — Mise aux normes",
          titreSecondaire: "NFC 15-100 — Rénovation complète",
          descriptif: "Remplacement complet du tableau électrique par un Hager avec étiquetage, protection différentielle et disjoncteurs adaptés à chaque circuit.",
          dateRealisation: "2023-11",
          ville: "Chambéry",
          type: "standard",
          imagePrincipale: { src: "images/electricite-tableau-1.jpg", alt: "Tableau électrique Hager avec étiquettes – mise aux normes", label: "📋 Tableau Hager — Mise aux normes NFC 15-100" },
          imagesSecondaires: [
            { src: "images/electricite-tableau-2.jpg", alt: "Section chauffage tableau électrique", label: "🌡️ Section chauffage — Circuits dédiés" }
          ]
        },
        {
          id: "elec-2",
          titrePrincipal: "Tableau Merlin Gerin Legrand",
          titreSecondaire: "Câblage professionnel & boîte encastrée",
          descriptif: "Pose d'un nouveau tableau Merlin Gerin avec câblage professionnel propre, et pose d'une boîte électrique encastrée dans la cloison.",
          dateRealisation: "2024-01",
          ville: "Barberaz",
          type: "standard",
          imagePrincipale: { src: "images/electricite-tableau-3.jpg", alt: "Tableau Merlin Gerin Legrand – câblage professionnel", label: "⚡ Tableau Merlin Gerin — Câblage propre" },
          imagesSecondaires: [
            { src: "images/electricite-boite.jpg", alt: "Pose boîte électrique encastrée dans plaque de plâtre", label: "🔌 Pose boîte encastrée" }
          ]
        }
      ]
    },
    {
      id: "volets",
      icon: "🪟",
      tag: "Volets Roulants & Boiseries",
      titreSection: "Réparation, Rénovation & Installation",
      descriptionSection: "Réparation de volets bloqués, remplacement de lames, motorisation, et rénovation de stores et boiseries bois.",
      lienService: "volets-roulants.html",
      nomService: "Volets Roulants",
      realisations: [
        {
          id: "volets-1",
          titrePrincipal: "Rénovation stores & portes bois",
          titreSecondaire: "Nettoyage, traitement & lasure",
          descriptif: "Rénovation complète de boiseries extérieures : nettoyage haute pression, traitement fongicide, ponçage et lasure de protection. Aspect neuf garanti.",
          dateRealisation: "2024-02",
          ville: "Chambéry",
          type: "avant-apres",
          imagePrincipale: { src: "images/volets-boiseries-avant-apres.png", alt: "Rénovation store bois avant après", label: "🪵 Rénovation stores & portes bois — Avant / Après" },
          imagesSecondaires: [
            { src: "images/volets-roulants-ferme.jpg",  alt: "Volet roulant bois lames orangées fermé",  label: "🪟 Volet fermé" },
            { src: "images/volets-roulants-ouvert.jpg", alt: "Volet roulant en cours d'ouverture",       label: "🪟 En ouverture" },
            { src: "images/volets-pro-serrure.jpg",     alt: "Serrure volet roulant commercial",         label: "🔐 Serrure pro" }
          ]
        }
      ]
    },
    {
      id: "cuisine",
      icon: "🍳",
      tag: "Bricolage — Montage Cuisine",
      titreSection: "Montage & Installation de Cuisine",
      descriptionSection: "Montage complet de cuisine équipée : meubles bas, meubles hauts, plan de travail, électroménager encastré. Chantier propre du début à la fin.",
      lienService: "bricolage.html",
      nomService: "Bricolage",
      realisations: [
        {
          id: "cuisine-1",
          titrePrincipal: "Montage cuisine noire & bois",
          titreSecondaire: "Cuisine équipée avec électroménager encastré",
          descriptif: "Cuisine complète montée en une journée : meubles noirs laqués & bois naturel, plan de travail stratifié, électroménager encastré. Résultat clé en main.",
          dateRealisation: "2024-04",
          ville: "Chambéry",
          type: "standard",
          imagePrincipale: { src: "images/bricolage-cuisine-4.jpg", alt: "Cuisine terminée avec portes noires et bois – résultat final", label: "🍳 Cuisine terminée — Résultat final" },
          imagesSecondaires: [
            { src: "images/bricolage-cuisine-1.jpg", alt: "Montage cuisine en cours",     label: "🍳 Début d'installation" },
            { src: "images/bricolage-cuisine-2.jpg", alt: "Vue dessus cuisine en cours",  label: "🍳 Vue de dessus" },
            { src: "images/bricolage-cuisine-3.jpg", alt: "Cuisine presque terminée",     label: "🍳 Finition en cours" }
          ]
        }
      ]
    },
    {
      id: "meubles",
      icon: "🪑",
      tag: "Bricolage — Meubles & Chambre",
      titreSection: "Montage Meubles & Aménagement Chambre",
      descriptionSection: "Montage d'armoires, lits, dressings et mobilier de chambre. Nous venons avec le matériel, vous profitez du résultat.",
      lienService: "bricolage.html",
      nomService: "Bricolage",
      realisations: [
        {
          id: "meubles-1",
          titrePrincipal: "Aménagement chambre complète",
          titreSecondaire: "Lambris bois, placard coulissant & literie",
          descriptif: "Aménagement complet d'une chambre : pose de lambris bois, installation d'un placard coulissant, montage du lit coffre et de la grande armoire coulissante.",
          dateRealisation: "2024-03",
          ville: "La Ravoire",
          type: "standard",
          imagePrincipale: { src: "images/bricolage-chambre.jpg", alt: "Chambre aménagée avec lit sommier et parquet stratifié", label: "🛏️ Chambre complète aménagée" },
          imagesSecondaires: [
            { src: "images/bricolage-chambre-2.jpg", alt: "Chambre avec lambris bois et placard",   label: "🛏️ Lambris & placard coulissant" },
            { src: "images/bricolage-lit.jpg",       alt: "Montage lit coffre gris",                label: "🛏️ Lit coffre monté" },
            { src: "images/bricolage-armoire.jpg",   alt: "Grande armoire coulissante montée",      label: "🗄️ Armoire coulissante" }
          ]
        }
      ]
    },
    {
      id: "decoration",
      icon: "🎨",
      tag: "Décoration & Rénovation",
      titreSection: "Papier Peint, Revêtements & Rénovation",
      descriptionSection: "Pose de papier peint, fresques murales, revêtements de sol, revêtements muraux et rénovation complète d'espaces intérieurs.",
      lienService: "bricolage.html",
      nomService: "Bricolage",
      realisations: [
        {
          id: "deco-1",
          titrePrincipal: "Fresque murale tropicale",
          titreSecondaire: "Papier peint panoramique jungle",
          descriptif: "Pose d'une fresque murale panoramique tropicale dans une chambre. Préparation du mur, découpe précise et pose au millimètre. Résultat spectaculaire.",
          dateRealisation: "2024-01",
          ville: "Chambéry",
          type: "standard",
          imagePrincipale: { src: "images/deco-fresque-apres.jpg", alt: "Fresque murale jungle tropicale terminée", label: "🌴 Fresque tropicale — Résultat final" },
          imagesSecondaires: [
            { src: "images/deco-fresque-avant.jpg", alt: "Pose fresque murale en cours", label: "🪜 Pendant la pose" }
          ]
        },
        {
          id: "deco-2",
          titrePrincipal: "Papier peint géométrique & Revêtements",
          titreSecondaire: "Décoration intérieure sur mesure",
          descriptif: "Pose de papier peint géométrique couleur miel, revêtement pierre naturelle en parement mural et installation d'une fenêtre coulissante aluminium noir anthracite.",
          dateRealisation: "2023-12",
          ville: "Aix-les-Bains",
          type: "standard",
          imagePrincipale: { src: "images/deco-papier-peint.jpg", alt: "Papier peint géométrique couleur miel", label: "🎨 Papier peint géométrique" },
          imagesSecondaires: [
            { src: "images/deco-pierre.jpg",       alt: "Revêtement mural pierre naturelle",             label: "🪨 Pierre naturelle" },
            { src: "images/bricolage-fenetre.jpg", alt: "Pose fenêtre coulissante aluminium noir",        label: "🪟 Fenêtre alu noir" }
          ]
        },
        {
          id: "deco-3",
          titrePrincipal: "Rénovation plancher stratifié",
          titreSecondaire: "Dépose & pose parquet flottant",
          descriptif: "Dépose de l'ancien revêtement de sol et pose d'un nouveau plancher stratifié avec plinthes assorties. Finition soignée, nettoyage compris.",
          dateRealisation: "2024-02",
          ville: "Cognin",
          type: "standard",
          imagePrincipale: { src: "images/renovation-plancher-1.jpg", alt: "Chambre rénovée avec nouveau plancher stratifié", label: "🏠 Nouveau plancher stratifié" },
          imagesSecondaires: [
            { src: "images/renovation-plancher-2.jpg", alt: "Plancher stratifié posé", label: "✨ Finition propre" }
          ]
        }
      ]
    }
  ]
};

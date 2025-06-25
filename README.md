
# 🗳️ E-Vote ENSAE | Application de Gestion Numérique des Élections

Bienvenue dans le dépôt GitHub de notre projet réalisé dans le cadre du **Hackathon 2025** organisé par le **Club Informatique de l’ENSAE**.

![Hackathon ENSAE](https://img.shields.io/badge/Hackathon-ENSAE%202025-blue)  
![Statut](https://img.shields.io/badge/Statut-En%20développement-yellow)  
![Licence](https://img.shields.io/badge/Licence-MIT-green)

---

## 🧠 Contexte du Hackathon

Le Club Informatique de l’ENSAE a lancé son premier Hackathon pour stimuler l'**innovation technologique** dans la gestion académique.  
Notre équipe a choisi de relever le défi du **Thème 3 : Application de gestion numérique des élections**.

---

## 🎯 Objectif du Projet

Créer une **application sécurisée, simple et intuitive** permettant d'organiser des élections électroniques pour l’amicale, les clubs ou les délégués de classe.

---

## ✨ Fonctionnalités principales

- **Authentification** (étudiant, comité, admin)
- **Inscription** avec validation d'email autorisé
- **Gestion des élections** (création, modification, statut, comité)
- **Candidatures** (dépôt, validation/rejet par comité)
- **Vote électronique** sécurisé (1 vote/poste/utilisateur)
- **Résultats et statistiques** (par poste, par élection, graphiques)
- **Gestion des comités** (nomination, recherche, droits)
- **Tableau de bord administrateur** (API, gestion utilisateurs, élections, types, postes, comités)
- **Responsive** (adapté mobile/desktop)

---


## 📂 Structure du projet

```
/ (racine)
│
├── admin/                # Interface et API d'administration
│   ├── dashboard.php     # Tableau de bord admin (HTML/PHP)
│   ├── dashboard.js      # Logique JS du dashboard
│   └── api.php           # API RESTful pour l'admin
│
├── assets/               # Ressources statiques
│   ├── css/              # Feuilles de style CSS
│   ├── js/               # Scripts JS (front)
│   ├── img/              # Images (logo, photos...)
│   └── docs/             # Documents PDF (programmes...)
│
├── components/           # Composants réutilisables (header/footer)
│   ├── header.php
│   ├── footer.php
│   ├── header_home.php
│   └── footer_home.php
│
├── config/               # Configuration
│   └── database.php      # Connexion et gestion DB (PDO, managers)
│
├── pages/                # Pages principales de l'application
│   ├── index.php         # Accueil connecté
│   ├── vote.php          # Page de vote
│   ├── campagnes.php     # Liste des campagnes/candidats
│   ├── candidature.php   # Dépôt de candidature
│   ├── profil.php        # Profil utilisateur
│   ├── resultat.php      # Résultats officiels
│   ├── statistique.php   # Statistiques détaillées
│   └── role_comite.php   # Interface comité d'organisation
│
├── uploads/              # Fichiers uploadés (photos, PDF...)
│
├── index.php             # Accueil public
├── login.php             # Connexion
├── inscription.php       # Inscription
├── avant-propos.php      # Présentation
├── logout.php            # Déconnexion
├── auth_check.php        # Fonctions d'authentification
└── README.md             # (ce fichier)
```

---

## ⚙️ Installation

1. **Prérequis**
   - PHP >= 7.4
   - MySQL/MariaDB
   - Serveur web (Apache, Nginx, XAMPP...)

2. **Cloner le projet**
   ```bash
   git clone <repo-url>
   ```

3. **Base de données**
   - Importer le fichier SQL fourni (`e_ensae.sql`) dans votre SGBD.
   - Adapter les identifiants dans `config/database.php` si besoin.

4. **Droits d'écriture**
   - Le dossier `uploads/` doit être accessible en écriture par le serveur web.

5. **Lancer le serveur**
   - Placer le projet dans le dossier web (ex: `htdocs` pour XAMPP).
   - Accéder à `http://localhost/e_ensae/` dans votre navigateur.

---

## 🛠️ Configuration

- **Base de données** : Modifier les constantes dans `config/database.php` si besoin.
- **Emails autorisés** : Ajouter les emails dans la table `gmail` pour permettre l'inscription.
- **Personnalisation** : Modifier les images dans `assets/img/`, les couleurs dans `assets/css/styles.css`.

---

## 🚀 Utilisation

- **Étudiant** :
  - S'inscrire (si email autorisé)
  - Se connecter, candidater, voter, consulter résultats/statistiques

- **Membre de comité** :
  - Valider/rejeter candidatures, modifier les élections, ajouter des postes

- **Administrateur** :
  - Gérer utilisateurs, élections, types, comités, voir toutes les statistiques

- **Upload** :
  - Les photos et programmes PDF sont stockés dans `uploads/`

---

## 🛡️ Sécurité

- Sessions sécurisées, vérification des rôles à chaque action
- Uploads filtrés (type, taille)
- Préparation des requêtes SQL (PDO)
- Accès API protégé (admin uniquement)
- Redirections en cas d'accès non autorisé

---

## 🧑‍🤝‍🧑 Équipe du Projet

👩‍💻 **Josée Clémence JEAZE NGUEMEZI**  
📚 *Étudiante en deuxième année en Analyse Statistique (AS)*

👨‍💻 **Marc MARE**  
🎨 *Étudiant en deuxième année en Analyse Statistique (AS)*

👨‍💻 **Gandwende Judicaël Oscar KAFANDO**  
📊 *Étudiant en première année d'ingéniorat en statistique économie (ISE)*

---

## 🙏 Crédits

- Plateforme développée pour l'ENSAE Dakar
- Technologies : PHP, MySQL, HTML5, CSS3, JavaScript (vanilla)
- Icônes : FontAwesome
- Charting : Chart.js

---

## 🚀 Déploiement

Vous pouvez tester l'application en ligne via ce lien (⚠️ à ajouter une fois déployé) :  
🔗 [https://aliceblue-locust-950953.hostingersite.com/](#)

---

Pour toute question ou contribution, contactez l'équipe projet ou ouvrez une issue sur le dépôt.


# Guide Technique – E-election ENSAE Dakar

Ce guide décrit les étapes d’installation et les modalités d’utilisation de la plateforme **E-election ENSAE Dakar**.

## 1. Prérequis

- PHP ≥ 7.4
- Serveur web (Apache, Nginx, XAMPP…)
- MySQL ou MariaDB

## 2. Installation

1. **Clonage du projet**
   ```bash
   git clone <repo-url>
   ```
2. **Base de données**
   - Importer le fichier `e_ensae.sql` dans votre SGBD.
   - Adapter les identifiants dans `config/database.php` si besoin.
3. **Droits d’écriture**
   - Le dossier `uploads/` doit être accessible en écriture pour enregistrer photos et PDF.
4. **Lancer le serveur**
   - Placer le projet dans le répertoire web de votre serveur, puis accéder à `http://localhost/e_ensae/`.

## 3. Configuration

- **Base de données** : éditer `config/database.php` pour renseigner hôte, base, utilisateur et mot de passe.
- **Emails autorisés** : la table `gmail` contient la liste des adresses pouvant s’inscrire.
- **Personnalisation** : images dans `assets/img/`, couleurs dans `assets/css/styles.css`.

## 4. Fonctionnement général

### 4.1 Rôles utilisateurs
- **Etudiant** : peut s’inscrire, se connecter, déposer sa candidature, voter et consulter les résultats.
- **Comité** : valide ou rejette les candidatures, gère les élections dont il est responsable.
- **Administrateur** : accès complet au tableau de bord (API, gestion des utilisateurs, élections, postes et comités).

### 4.2 Processus principal
1. **Inscription**
   - L’étudiant remplit le formulaire `inscription.php`. L’email doit être pré-approuvé.
2. **Connexion**
   - Via `login.php`. L’utilisateur est redirigé selon son rôle (site public ou dashboard admin).
3. **Création d’une élection** (admin)
   - Depuis le dashboard, définir titre, type, portée (générale ou spécifique) et dates.
4. **Ajout des postes**
   - Chaque élection peut comporter plusieurs postes.
5. **Dépôt de candidatures** (étudiants)
   - Depuis `candidature.php`, sélection du poste et téléversement du programme et de la photo.
6. **Validation des candidatures** (comité)
   - Le comité accepte ou rejette les candidatures depuis `role_comite.php` ou le dashboard.
7. **Vote**
   - Les étudiants se connectent et votent via `vote.php`. Un seul vote est possible par poste.
8. **Consultation des résultats et statistiques**
   - Pages `resultat.php` et `statistique.php`, graphiques interactifs (Chart.js).

## 5. Tableau de bord administrateur

Le fichier `admin/dashboard.php` associé à `dashboard.js` offre des sections pour :
- Les statistiques globales (utilisateurs, élections, votes).
- La gestion des emails autorisés.
- La création des types d’élection, des élections et des postes.
- La nomination des comités d’organisation.
- La revue et la validation des candidatures.
- La recherche et le filtrage des utilisateurs.

L’API `admin/api.php` permet d’effectuer ces opérations en AJAX pour une interface réactive.

## 6. Bonnes pratiques de sécurité

- Utiliser des mots de passe forts et les stocker de manière sûre.
- Veiller à la mise à jour de PHP et du serveur.
- Restreindre l’accès au dossier `uploads/` si nécessaire.
- Sauvegarder régulièrement la base de données.

Ce guide fournit les repères essentiels pour installer, configurer et exploiter efficacement la plateforme **E-election ENSAE Dakar**.


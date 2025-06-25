<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $classe = $_POST['classe'] ?? '';

    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($classe)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Création de l'utilisateur via UserManager
        $userData = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'mot_de_passe' => $password,
            'classe' => $classe,
            'role' => 'etudiant'
        ];

        try {
            $userId = $userManager->createUser($userData);
            $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            // Redirection après 3 secondes
            header("refresh:3;url=login.php");
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "n'est pas autorisé") !== false) {
                $error = "❌ Inscription impossible : Cet email n'est pas dans la liste des emails autorisés. Veuillez contacter l'administration pour demander l'autorisation.";
            } else {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Vote ENSAE</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/inscription.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .error-message,
    .success-message {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .error-message {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .success-message {
        background-color: #dcfce7;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .form-group small {
        color: var(--gray);
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .form-group small i {
        color: var(--primary);
    }

    .btn i {
        margin-right: 0.5rem;
    }

    input::placeholder {
        color: #9ca3af;
        font-style: italic;
    }

    select {
        color: var(--text);
    }

    select option {
        color: var(--text);
    }

    /* Amélioration du sélecteur */
    select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    }

    .auth-container h1 {
        text-align: center;
        margin-bottom: 2rem;
        color: var(--primary);
    }
    </style>
</head>

<body>
    <?php include 'components/header_home.php'; ?>

    <div class="container">
        <main class="page-content">
            <div class="auth-container">
                <h1>Créer un compte</h1>

                <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="nom">Nom de famille</label>
                        <input type="text" id="nom" name="nom" placeholder="Ex: Diallo, Traoré, Diop..."
                            value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" placeholder="Ex: Mamadou, Fatou, Amadou..."
                            value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse email ENSAE</label>
                        <input type="email" id="email" name="email" placeholder="prenom.nom@ensae.sn"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <small><i class="fas fa-exclamation-triangle"></i> Seuls les emails pré-approuvés peuvent
                            s'inscrire</small>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Minimum 6 caractères"
                            required>
                        <small><i class="fas fa-shield-alt"></i> Minimum 6 caractères pour la sécurité</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm-password" name="confirm-password"
                            placeholder="Répétez votre mot de passe" required>
                    </div>

                    <div class="form-group">
                        <label for="classe">Votre classe actuelle</label>
                        <select id="classe" name="classe" required>
                            <option value="">-- Choisissez votre classe --</option>
                            <option value="AS1" <?php echo ($_POST['classe'] ?? '') === 'AS1' ? 'selected' : ''; ?>>AS1
                            </option>
                            <option value="AS2" <?php echo ($_POST['classe'] ?? '') === 'AS2' ? 'selected' : ''; ?>>AS2
                            </option>
                            <option value="AS3" <?php echo ($_POST['classe'] ?? '') === 'AS3' ? 'selected' : ''; ?>>AS3
                            </option>
                            <option value="ISEP1" <?php echo ($_POST['classe'] ?? '') === 'ISEP1' ? 'selected' : ''; ?>>
                                ISEP1</option>
                            <option value="ISEP2" <?php echo ($_POST['classe'] ?? '') === 'ISEP2' ? 'selected' : ''; ?>>
                                ISEP2</option>
                            <option value="ISEP3" <?php echo ($_POST['classe'] ?? '') === 'ISEP3' ? 'selected' : ''; ?>>
                                ISEP3</option>
                            <option value="ISE1 math"
                                <?php echo ($_POST['classe'] ?? '') === 'ISE1 math' ? 'selected' : ''; ?>>ISE1 math
                            </option>
                            <option value="ISE1 eco"
                                <?php echo ($_POST['classe'] ?? '') === 'ISE1 eco' ? 'selected' : ''; ?>>ISE1 eco
                            </option>
                            <option value="ISE2" <?php echo ($_POST['classe'] ?? '') === 'ISE2' ? 'selected' : ''; ?>>
                                ISE2</option>
                            <option value="ISE3" <?php echo ($_POST['classe'] ?? '') === 'ISE3' ? 'selected' : ''; ?>>
                                ISE3</option>
                        </select>
                        <small><i class="fas fa-graduation-cap"></i> Sélectionnez votre classe actuelle</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Créer mon compte
                        </button>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-sign-in-alt"></i>
                            Déjà un compte ? Se connecter
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>


    <?php include 'components/footer_home.php'; ?>

    <!-- Inclusion dynamique du header/footer -->
    <script src="assets/js/include.js"></script>


</body>

</html>
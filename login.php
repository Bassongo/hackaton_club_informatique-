<?php
session_start();
require_once 'config/database.php';

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation des données
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } else {
        // Authentification via UserManager
        $user = $userManager->authenticate($email, $password);
        
        if ($user) {
            // Création de la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_classe'] = $user['classe'];
            
            // Redirection selon le rôle
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: pages/index.php');
            }
            exit;
        } else {
            $error = "❌ Email ou mot de passe incorrect. Veuillez vérifier vos identifiants.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Vote ENSAE</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .error-message {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .login-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .login-footer a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .login-footer a:hover {
        color: var(--secondary);
    }

    .login-footer small {
        color: var(--gray);
        font-size: 0.875rem;
    }

    .btn i {
        margin-right: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--primary);
    }

    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color var(--transition), box-shadow var(--transition);
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    }

    .form-group input::placeholder {
        color: #9ca3af;
        font-style: italic;
    }

    .login-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }



    .login-actions .btn {
        flex: 1;
        text-align: center;
    }

    @media (max-width: 768px) {
        .login-actions {
            flex-direction: column;
        }
    }
    </style>
</head>

<body class="login-bg">
    <?php include 'components/header_home.php'; ?>

    <div class="login-container">
        <div class="login-box">
            <h2>Se connecter</h2>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="email">Adresse email ENSAE</label>
                    <input type="email" id="email" name="email" placeholder="prenom.nom@ensae.sn"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Votre mot de passe" required>
                </div>

                <div class="login-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </button>
                    <a href="index.php" class="btn btn-link">

                        <div class="ret">
                            <i class=" fas fa-arrow-left"></i>
                            Retour à l'accueil
                        </div>

                    </a>
                </div>
            </form>

            <div class="login-footer">
                <p>Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
                <p><small><i class="fas fa-info-circle"></i> Utilisez l'email avec lequel vous vous êtes inscrit</small>
                </p>
            </div>
        </div>
    </div>

    <?php include 'components/footer_home.php'; ?>


</body>

</html>
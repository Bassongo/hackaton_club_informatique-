<?php
// Gestion des sessions au tout début, avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../auth_check.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    // Afficher une page d'authentification au lieu de rediriger
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentification requise - Vote ENSAE</title>
        <link rel="stylesheet" href="../assets/css/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .auth-required {
                text-align: center;
                padding: 4rem 2rem;
                max-width: 600px;
                margin: 0 auto;
            }
            .auth-required h1 {
                color: var(--primary);
                margin-bottom: 1rem;
            }
            .auth-required p {
                color: var(--gray);
                margin-bottom: 2rem;
            }
            .auth-buttons {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn {
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .btn-primary {
                background: var(--primary);
                color: white;
            }
            .btn-secondary {
                background: var(--secondary);
                color: white;
            }
        </style>
    </head>
    <body>
        <?php include '../components/header_simple.php'; ?>
        
        <div class="container">
            <div class="auth-required">
                <h1><i class="fas fa-lock"></i> Authentification requise</h1>
                <p>Vous devez être connecté pour accéder à cette page.</p>
                <div class="auth-buttons">
                    <a href="../login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                    <a href="../inscription.php" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i> S'inscrire
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
        
        <?php include '../components/footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

$currentUser = getCurrentUser();
$db = Database::getInstance();

// Traitement des formulaires
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Mise à jour des informations du profil
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $classe = trim($_POST['classe']);
        
        // Validations
        if (empty($nom) || empty($prenom) || empty($email)) {
            $message = "Tous les champs obligatoires doivent être remplis.";
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "L'adresse email n'est pas valide.";
            $messageType = 'error';
        } else {
            try {
                // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
                $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = :email AND id != :user_id", 
                    ['email' => $email, 'user_id' => $currentUser['id']]);
                
                if ($existingUser) {
                    $message = "Cette adresse email est déjà utilisée par un autre utilisateur.";
                    $messageType = 'error';
                } else {
                    // Mettre à jour les informations
                    $updateData = [
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email,
                        'classe' => $classe
                    ];
                    
                    $db->update('users', $updateData, 'id = :id', ['id' => $currentUser['id']]);
                    
                    // Mettre à jour la session
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_classe'] = $classe;
                    
                    $message = "Votre profil a été mis à jour avec succès !";
                    $messageType = 'success';
                    
                    // Recharger les données utilisateur
                    $currentUser = getCurrentUser();
                }
            } catch (Exception $e) {
                $message = "Erreur lors de la mise à jour : " . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Changement de mot de passe
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validations
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = "Tous les champs du mot de passe doivent être remplis.";
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Les nouveaux mots de passe ne correspondent pas.";
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
            $messageType = 'error';
        } else {
            try {
                // Vérifier l'ancien mot de passe
                $user = $db->fetchOne("SELECT password FROM users WHERE id = :id", ['id' => $currentUser['id']]);
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    $message = "Le mot de passe actuel est incorrect.";
                    $messageType = 'error';
                } else {
                    // Mettre à jour le mot de passe
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->update('users', ['password' => $hashedPassword], 'id = :id', ['id' => $currentUser['id']]);
                    
                    $message = "Votre mot de passe a été modifié avec succès !";
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = "Erreur lors du changement de mot de passe : " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Récupérer les informations à jour de l'utilisateur
$userInfo = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $currentUser['id']]);

// Classes disponibles
$classes = [
    'AS1', 'AS2', 'AS3',
    'ISEP1', 'ISEP2', 'ISEP3',
    'ISE1 math', 'ISE1 eco', 'ISE2', 'ISE3'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/profil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-section h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-1px);
        }

        .user-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .user-info h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--text);
        }

        .info-value {
            color: var(--gray);
        }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.admin {
            background: #fee2e2;
            color: #dc2626;
        }

        .role-badge.comite {
            background: #dbeafe;
            color: #2563eb;
        }

        .role-badge.etudiant {
            background: #d1fae5;
            color: #059669;
        }

        @media (max-width: 768px) {
            .profile-sections {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .profile-container {
                padding: 1rem;
            }

            .profile-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
            <p>Gérez vos informations personnelles et votre mot de passe</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-sections">
            <!-- Informations actuelles -->
            <div class="profile-section">
                <h2><i class="fas fa-info-circle"></i> Mes Informations</h2>
                
                <div class="user-info">
                    <h3>Informations actuelles</h3>
                    <div class="info-item">
                        <span class="info-label">Nom :</span>
                        <span class="info-value"><?php echo htmlspecialchars($userInfo['nom']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Prénom :</span>
                        <span class="info-value"><?php echo htmlspecialchars($userInfo['prenom']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email :</span>
                        <span class="info-value"><?php echo htmlspecialchars($userInfo['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Classe :</span>
                        <span class="info-value"><?php echo htmlspecialchars($userInfo['classe']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rôle :</span>
                        <span class="info-value">
                            <span class="role-badge <?php echo $userInfo['role']; ?>">
                                <?php echo ucfirst($userInfo['role']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Inscrit le :</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($userInfo['date_inscription'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Modification du profil -->
            <div class="profile-section">
                <h2><i class="fas fa-edit"></i> Modifier mes informations</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($userInfo['nom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($userInfo['prenom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userInfo['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="classe">Classe</label>
                        <select id="classe" name="classe">
                            <option value="">Sélectionner une classe</option>
                            <?php foreach ($classes as $classe): ?>
                                <option value="<?php echo $classe; ?>" <?php echo $userInfo['classe'] === $classe ? 'selected' : ''; ?>>
                                    <?php echo $classe; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </form>
            </div>

            <!-- Changement de mot de passe -->
            <div class="profile-section">
                <h2><i class="fas fa-lock"></i> Changer mon mot de passe</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" name="change_password" class="btn btn-secondary">
                        <i class="fas fa-key"></i> Changer le mot de passe
                    </button>
                </form>
            </div>

            <!-- Actions rapides -->
            <div class="profile-section">
                <h2><i class="fas fa-cogs"></i> Actions rapides</h2>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Retour à l'accueil
                    </a>
                    
                    <a href="campagnes.php" class="btn btn-secondary">
                        <i class="fas fa-vote-yea"></i> Voir les campagnes
                    </a>
                    
                    <a href="candidature.php" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i> Ma candidature
                    </a>
                    
                    <a href="../logout.php" class="btn btn-secondary" style="background: #dc2626;">
                        <i class="fas fa-sign-out-alt"></i> Se déconnecter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>

    <script>
        // Validation côté client pour la confirmation du mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            newPassword.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>
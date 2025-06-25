<?php
// Les sessions sont maintenant gérées dans le header
// Pas besoin de session_start() ici pour éviter les conflits

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string $role Le rôle à vérifier
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Vérifie si l'utilisateur est admin
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Vérifie si l'utilisateur est membre du comité
 * @return bool
 */
function isCommittee() {
    return hasRole('comite');
}

/**
 * Vérifie si l'utilisateur est étudiant
 * @return bool
 */
function isStudent() {
    return hasRole('etudiant');
}

/**
 * Vérifie si l'utilisateur est membre d'un comité d'organisation
 * @return bool
 */
function isCommitteeMember() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Si l'utilisateur a le rôle 'comite', il est automatiquement membre
    if (hasRole('comite')) {
        return true;
    }
    
    // Sinon, vérifier dans la base de données
    try {
        require_once __DIR__ . '/config/database.php';
        $db = Database::getInstance();
        $count = $db->count('comites', 'user_id = :user_id', ['user_id' => $_SESSION['user_id']]);
        return $count > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Redirige vers la page de connexion si non connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Déterminer le bon chemin selon l'emplacement du fichier
        $currentPath = $_SERVER['REQUEST_URI'];
        if (strpos($currentPath, '/pages/') !== false) {
            header('Location: ../login.php');
        } else {
            header('Location: login.php');
        }
        exit;
    }
}

/**
 * Redirige vers la page d'accueil si connecté
 */
function requireGuest() {
    if (isLoggedIn()) {
        // Déterminer le bon chemin selon l'emplacement du fichier
        $currentPath = $_SERVER['REQUEST_URI'];
        if (strpos($currentPath, '/pages/') !== false) {
            header('Location: index.php');
        } else {
            header('Location: pages/index.php');
        }
        exit;
    }
}

/**
 * Redirige si l'utilisateur n'a pas le rôle requis
 * @param string $role Le rôle requis
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Déterminer le bon chemin selon l'emplacement du fichier
        $currentPath = $_SERVER['REQUEST_URI'];
        if (strpos($currentPath, '/pages/') !== false) {
            header('Location: index.php');
        } else {
            header('Location: pages/index.php');
        }
        exit;
    }
}

/**
 * Redirige si l'utilisateur n'est pas admin
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Redirige si l'utilisateur n'est pas membre du comité
 */
function requireCommittee() {
    requireRole('comite');
}

/**
 * Obtient les informations de l'utilisateur connecté
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nom' => $_SESSION['user_nom'],
        'prenom' => $_SESSION['user_prenom'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'classe' => $_SESSION['user_classe']
    ];
}

/**
 * Obtient l'ID de l'utilisateur connecté
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtient le nom complet de l'utilisateur connecté
 * @return string
 */
function getCurrentUserName() {
    if (!isLoggedIn()) {
        return '';
    }
    return $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];
}

/**
 * Obtient l'email de l'utilisateur connecté
 * @return string
 */
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? '';
}

/**
 * Obtient le rôle de l'utilisateur connecté
 * @return string
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? '';
}

/**
 * Obtient la classe de l'utilisateur connecté
 * @return string
 */
function getCurrentUserClasse() {
    return $_SESSION['user_classe'] ?? '';
}
?>
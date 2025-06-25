<?php
// Gestion des sessions directement
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure auth_check.php seulement si on a besoin des fonctions d'authentification
if (!function_exists('isLoggedIn')) {
    require_once '../auth_check.php';
}

// Vérification du CSS header
$headerCssPath = '../assets/css/header.css';
?>
<style>
<?php if (file_exists($headerCssPath)) {
    echo "@import url('$headerCssPath');";
}

else {
    echo '/* CSS header manquant */';
}

?>
</style>


<header>
    <div class="header-container">
        <!-- Logo -->
        <div class="logo">
            <?php 
            $logoPath = '../assets/img/logo_ensae.png';
            if (file_exists($logoPath)) {
                echo '<img src="' . $logoPath . '" alt="Logo ENSAE" class="logo-img">';
            } else {
                echo '<span style="color:red;font-weight:bold">Logo manquant</span>';
            }
            ?>
            <div class="logo-text">
                <h1>E-election</h1>
                <h2>ENSAE Dakar</h2>
            </div>
        </div>

        <!-- Menu Toggle for Mobile -->
        <button class="menu-toggle" aria-label="Ouvrir le menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Navigation -->
        <nav class="main-nav">
            <ul class="nav-menu">

                <li><a href="../pages/index.php" class="nav-link"><span>Accueil</span></a></li>

                <?php if (isCommitteeMember()): ?>
                <li><a href="role_comite.php" class="nav-link"><span>Mon Rôle</span></a></li>
                <?php endif; ?>

                <li class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle"><span>Elections ▼</span></a>
                    <ul class="dropdown-menu">
                        <li><a href="campagnes.php" class="nav-link"><span>Campagne</span></a></li>
                        <li><a href="vote.php" class="nav-link"><span>Voter</span></a></li>
                    </ul>
                </li>


                <li class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle"><span>Candidatures ▼</span></a>
                    <ul class="dropdown-menu">
                        <li><a href="candidature.php" class="nav-link"><span>Candidater</span></a></li>
                </li>
            </ul>
            </li>



            <li class="dropdown">
                <a href="#" class="nav-link dropdown-toggle"><span>Bilan ▼</span></a>
                <ul class="dropdown-menu">
                    <li><a href="statistique.php" class="nav-link"><span>Statistiques</span></a></li>
                    <li><a href="resultat.php" class="nav-link"><span>Résultats</span></a></li>
                </ul>
            </li>

            <li class="dropdown">
                <a href="#" class="nav-link dropdown-toggle"><span>Mon profil▼</span></a>
                <ul class="dropdown-menu">
                    <li><a href="profil.php" class="nav-link"><span>Moi</span></a></li>
                    <li><a href="../logout.php" class="nav-link"><span>Déconnexion</span></a></li>
                </ul>
            </li>
            </ul>
        </nav>
    </div>


</header>


<script src="../assets/js/state.js"></script>
<script src="../assets/js/modal.js"></script>
<script src="../assets/js/header.js"></script>
<script src="../assets/js/include.js"></script>

<div id="modals"></div>
<script>
// Vérification dynamique du chemin du composant modals
const modalsPath = '../components/modals.html';
fetch(modalsPath).then(r => {
    if (r.ok) includeComponent('#modals', modalsPath);
});
</script>
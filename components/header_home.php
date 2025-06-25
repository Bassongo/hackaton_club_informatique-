<style>
@import "./assets/css/header.css";
</style>



<header>
    <div class="header-container">
        <!-- Logo ENSAE et Titre -->
        <div class="logo">
            <img src="./assets/img/logo_ensae.png" alt="Logo ENSAE" class="logo-img">
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

        <!-- Navigation principale -->
        <nav class="main-nav">
            <ul class="nav-menu">
                <li id="accueilBtn"><a href="login.php" class="nav-link">Se connecter</a></li>
                <li id="candidaterBtn"><a href="inscription.php" class="nav-link">S'inscrire</a></li>
                <li><a href="avant-propos.php" class="nav-link">Avant-Propos</a></li>
            </ul>
        </nav>
    </div>

    <!-- Script de mise en surbrillance du lien actif -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const current = location.pathname.split('/').pop();
        document.querySelectorAll('.main-nav .nav-link').forEach(link => {
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('highlight');
            } else {
                link.classList.remove('highlight');
            }
        });
    });
    </script>
</header>

<script src="./assets/js/state.js"></script>
<script src="./assets/js/header.js"></script>
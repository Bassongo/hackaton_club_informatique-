<?php
// Gestion des sessions au tout début, avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../auth_check.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Vote ENSAE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/acceuils.css">
    <style>
    /* Effets futuristes subtils */
    .hero-section {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--header) 0%, #1e3a8a 50%, var(--primary) 100%);
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        opacity: 0.3;
        animation: gridMove 20s linear infinite;
    }

    @keyframes gridMove {
        0% {
            transform: translate(0, 0);
        }

        100% {
            transform: translate(10px, 10px);
        }
    }

    .hero-content {
        text-align: center;
        z-index: 2;
        position: relative;
        max-width: 800px;
        padding: 2rem;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        background: linear-gradient(45deg, #ffffff, #a7d4ff, #ffffff);
        background-size: 200% 200%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: gradientShift 3s ease-in-out infinite;
        text-shadow: 0 0 30px rgba(167, 212, 255, 0.5);
    }

    @keyframes gradientShift {

        0%,
        100% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }
    }

    .hero-subtitle {
        font-size: 1.3rem;
        color: #a7d4ff;
        margin-bottom: 2rem;
        font-weight: 300;
        letter-spacing: 1px;
    }

    .hero-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin: 3rem 0;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        border-color: rgba(167, 212, 255, 0.5);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.9rem;
        color: #a7d4ff;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .floating-elements {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }

    .floating-element {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(167, 212, 255, 0.6);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
    }

    .floating-element:nth-child(1) {
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }

    .floating-element:nth-child(2) {
        top: 60%;
        left: 80%;
        animation-delay: 1s;
    }

    .floating-element:nth-child(3) {
        top: 80%;
        left: 20%;
        animation-delay: 2s;
    }

    .floating-element:nth-child(4) {
        top: 30%;
        left: 70%;
        animation-delay: 3s;
    }

    .floating-element:nth-child(5) {
        top: 70%;
        left: 30%;
        animation-delay: 4s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.6;
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
            opacity: 1;
        }
    }

    /* Carousel amélioré */
    .carousel-container {
        position: relative;
        margin: 3rem 0;
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        padding: 2rem;
    }

    .carousel-slides {
        position: relative;
        height: 400px;
        border-radius: 20px;
        overflow: hidden;
    }

    .carousel-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        transform: scale(0.9);
    }

    .carousel-slide.active {
        opacity: 1;
        transform: scale(1);
    }

    .carousel-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 15px;
    }

    .carousel-slide::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(37, 99, 235, 0.4),
                rgba(30, 58, 138, 0.4),
                rgba(124, 58, 237, 0.3));
        border-radius: 15px;
        z-index: 1;
    }

    .carousel-text {
        position: absolute;
        bottom: 2rem;
        left: 2rem;
        z-index: 2;
        font-size: 1.8rem;
        font-weight: 700;
        color: white;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
        background: rgba(255, 255, 255, 0.15);
        padding: 1rem 1.5rem;
        border-radius: 15px;
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transform: translateY(20px);
        opacity: 0;
        transition: all 0.6s ease 0.3s;
    }

    .carousel-slide.active .carousel-text {
        transform: translateY(0);
        opacity: 1;
    }

    .carousel-text i {
        margin-right: 0.5rem;
        color: #a7d4ff;
        font-size: 1.2em;
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        font-size: 1.5rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }

    .carousel-btn:active {
        transform: translateY(-50%) scale(0.95);
    }

    .carousel-btn.prev {
        left: 2rem;
    }

    .carousel-btn.next {
        right: 2rem;
    }

    /* Indicateurs de navigation */
    .carousel-indicators {
        position: absolute;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 0.5rem;
        z-index: 10;
    }

    .carousel-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .carousel-indicator.active {
        background: white;
        transform: scale(1.2);
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    .carousel-indicator:hover {
        background: rgba(255, 255, 255, 0.7);
        transform: scale(1.1);
    }

    /* Animation d'entrée pour les slides */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateX(50px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateX(0);
        }
    }

    .carousel-slide.active {
        animation: slideIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Responsive pour le carousel */
    @media (max-width: 768px) {
        .carousel-container {
            margin: 2rem 1rem;
            padding: 1rem;
        }

        .carousel-slides {
            height: 300px;
        }

        .carousel-text {
            font-size: 1.3rem;
            left: 1rem;
            bottom: 1rem;
            padding: 0.8rem 1.2rem;
        }

        .carousel-btn {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
        }

        .carousel-btn.prev {
            left: 1rem;
        }

        .carousel-btn.next {
            right: 1rem;
        }

        .carousel-indicators {
            bottom: 0.5rem;
        }

        .carousel-indicator {
            width: 10px;
            height: 10px;
        }
    }

    @media (max-width: 480px) {
        .carousel-slides {
            height: 250px;
        }

        .carousel-text {
            font-size: 1.1rem;
            padding: 0.6rem 1rem;
        }

        .carousel-btn {
            width: 35px;
            height: 35px;
            font-size: 1rem;
        }
    }

    /* Sections améliorées */
    .features-section {
        padding: 4rem 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .feature-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: 1px solid rgba(37, 99, 235, 0.1);
        position: relative;
        overflow: hidden;
    }

    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), #7c3aed);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .feature-icon {
        font-size: 3rem;
        color: var(--primary);
        margin-bottom: 1rem;
        display: block;
    }

    .feature-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 1rem;
    }

    .feature-description {
        color: var(--gray);
        line-height: 1.6;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .hero-stats {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }

        .carousel-text {
            font-size: 1.2rem;
            left: 1rem;
            bottom: 1rem;
        }
    }

    /* Effets supplémentaires pour le carousel */
    .carousel-container::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, #3b82f6, #8b5cf6, #06b6d4, #3b82f6);
        border-radius: 27px;
        z-index: -1;
        animation: borderGlow 3s ease-in-out infinite;
    }

    @keyframes borderGlow {

        0%,
        100% {
            opacity: 0.5;
        }

        50% {
            opacity: 1;
        }
    }

    .carousel-slide::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 30% 70%, rgba(167, 212, 255, 0.1) 0%, transparent 50%);
        border-radius: 15px;
        z-index: 1;
        pointer-events: none;
    }

    /* Effet de parallaxe sur les images */
    .carousel-slide img {
        transition: transform 0.3s ease;
    }

    .carousel-slide.active img {
        transform: scale(1.05);
    }

    /* Amélioration des boutons avec icônes */
    .carousel-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .carousel-btn:hover::before {
        width: 100%;
        height: 100%;
    }

    /* Animation des indicateurs */
    .carousel-indicator {
        position: relative;
        overflow: hidden;
    }

    .carousel-indicator::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
        transition: left 0.5s ease;
    }

    .carousel-indicator:hover::before {
        left: 100%;
    }

    /* Effet de focus pour l'accessibilité */
    .carousel-btn:focus,
    .carousel-indicator:focus {
        outline: 2px solid rgba(255, 255, 255, 0.8);
        outline-offset: 2px;
    }

    /* Animation de chargement pour le premier slide */
    @keyframes slideLoad {
        from {
            opacity: 0;
            transform: scale(0.8) translateY(20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .carousel-slide.active {
        animation: slideLoad 1s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Compteur de slides */
    .carousel-counter {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(0, 0, 0, 0.3);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        z-index: 10;
    }

    .carousel-counter span {
        color: #a7d4ff;
        font-weight: 700;
    }
    </style>
</head>

<body>
    <!-- Header dynamique -->
    <?php include '../components/header.php'; ?>

    <!-- Section Hero -->
    <section class="hero-section">
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>

        <div class="hero-content">
            <h1 class="hero-title">E-Vote ENSAE</h1>
            <p class="hero-subtitle">La démocratie numérique au service de la communauté étudiante</p>

            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Sécurisé</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Disponible</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Fraude</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Carousel -->
    <div class="carousel-container">
        <!-- Compteur de slides -->
        <div class="carousel-counter">
            <span id="currentSlide">1</span> / <span id="totalSlides">3</span>
        </div>

        <div class="carousel-slides">
            <div class="carousel-slide active">
                <img src="../assets/img/slide1.png" alt="Vote électronique">
                <div class="carousel-text">
                    <i class="fas fa-vote-yea"></i> Vote électronique facile et sécurisé
                </div>
            </div>
            <div class="carousel-slide">
                <img src="../assets/img/slide2.png" alt="Sécurité du vote">
                <div class="carousel-text">
                    <i class="fas fa-shield-alt"></i> Sécurité et transparence garanties
                </div>
            </div>
            <div class="carousel-slide">
                <img src="../assets/img/slide3.png" alt="Transparence">
                <div class="carousel-text">
                    <i class="fas fa-chart-bar"></i> Résultats en temps réel
                </div>
            </div>
        </div>

        <!-- Indicateurs de navigation -->
        <div class="carousel-indicators">
            <div class="carousel-indicator active" data-slide="0"></div>
            <div class="carousel-indicator" data-slide="1"></div>
            <div class="carousel-indicator" data-slide="2"></div>
        </div>

        <button class="carousel-btn prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-btn next"><i class="fas fa-chevron-right"></i></button>
    </div>

    <!-- Section Features -->
    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-user-plus feature-icon"></i>
                    <h3 class="feature-title">Inscription Simplifiée</h3>
                    <p class="feature-description">
                        Inscription en ligne rapide et sécurisée pour tous les candidats.
                        Gestion centralisée des profils et des documents.
                    </p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-users feature-icon"></i>
                    <h3 class="feature-title">Gestion des Électeurs</h3>
                    <p class="feature-description">
                        Base de données complète des électeurs avec vérification automatique
                        des droits de vote et gestion des classes.
                    </p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-lock feature-icon"></i>
                    <h3 class="feature-title">Votes Anonymes</h3>
                    <p class="feature-description">
                        Système de vote anonyme et sécurisé avec cryptage des données
                        et protection contre la fraude.
                    </p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-chart-line feature-icon"></i>
                    <h3 class="feature-title">Résultats en Temps Réel</h3>
                    <p class="feature-description">
                        Affichage des résultats en temps réel avec graphiques interactifs
                        et statistiques détaillées.
                    </p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-mobile-alt feature-icon"></i>
                    <h3 class="feature-title">Interface Responsive</h3>
                    <p class="feature-description">
                        Interface adaptée à tous les appareils : ordinateurs, tablettes
                        et smartphones pour un accès universel.
                    </p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-history feature-icon"></i>
                    <h3 class="feature-title">Historique Complet</h3>
                    <p class="feature-description">
                        Conservation de l'historique des élections avec traçabilité
                        complète et archives sécurisées.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Processus -->
    <div class="container">
        <div id="accueil">
            <div class="content">
                <div class="section">
                    <h3><i class="fas fa-target"></i> 🎯 Objectifs</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> Inscription en ligne des candidats</li>
                        <li><i class="fas fa-check-circle"></i> Gestion des électeurs</li>
                        <li><i class="fas fa-check-circle"></i> Votes anonymes et sécurisés</li>
                        <li><i class="fas fa-check-circle"></i> Résultats en temps réel</li>
                    </ul>
                </div>

                <div class="section">
                    <h3><i class="fas fa-vote-yea"></i> 🗳️ Processus électoral</h3>
                    <ul>
                        <li><i class="fas fa-arrow-right"></i> Phase d'inscription</li>
                        <li><i class="fas fa-arrow-right"></i> Campagne électorale</li>
                        <li><i class="fas fa-arrow-right"></i> Vote en ligne</li>
                        <li><i class="fas fa-arrow-right"></i> Publication des résultats</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer dynamique -->
    <?php include '../components/footer.php'; ?>

    <!-- Inclusion dynamique du header/footer -->
    <script src="../assets/js/include.js"></script>
    <script src="../assets/js/carousel.js"></script>

    <script>
    // Animation d'apparition au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observer les cartes de features
    document.querySelectorAll('.feature-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });

    // Animation des statistiques
    function animateStats() {
        const stats = document.querySelectorAll('.stat-number');
        stats.forEach(stat => {
            const target = stat.textContent;
            const isPercentage = target.includes('%');
            const isTime = target.includes('/');

            if (!isPercentage && !isTime && target !== '0') {
                let current = 0;
                const increment = parseInt(target) / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= parseInt(target)) {
                        current = parseInt(target);
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(current);
                }, 50);
            }
        });
    }

    // Déclencher l'animation des stats quand la section hero est visible
    const heroObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStats();
                heroObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.5
    });

    heroObserver.observe(document.querySelector('.hero-section'));
    </script>
</body>

</html>
// Carousel amélioré avec animations et indicateurs

window.addEventListener('DOMContentLoaded', () => {
    const slidesContainer = document.querySelector('.carousel-slides');
    if (!slidesContainer) return;

    const slides = document.querySelectorAll('.carousel-slide');
    const indicators = document.querySelectorAll('.carousel-indicator');
    const nextBtn = document.querySelector('.carousel-btn.next');
    const prevBtn = document.querySelector('.carousel-btn.prev');
    let currentIndex = 0;
    let isTransitioning = false;
    let autoPlayInterval;

    // Fonction pour afficher un slide spécifique
    function showSlide(index) {
        if (isTransitioning || index === currentIndex) return;
        
        isTransitioning = true;
        
        // Retirer la classe active de tous les slides et indicateurs
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        // Ajouter la classe active au slide et indicateur actuels
        slides[index].classList.add('active');
        if (indicators[index]) {
            indicators[index].classList.add('active');
        }
        
        // Mettre à jour le compteur
        const currentSlideElement = document.getElementById('currentSlide');
        if (currentSlideElement) {
            currentSlideElement.textContent = index + 1;
        }
        
        currentIndex = index;
        
        // Réactiver les transitions après l'animation
        setTimeout(() => {
            isTransitioning = false;
        }, 800);
    }

    // Fonction pour passer au slide suivant
    function nextSlide() {
        const nextIndex = (currentIndex + 1) % slides.length;
        showSlide(nextIndex);
    }

    // Fonction pour passer au slide précédent
    function prevSlide() {
        const prevIndex = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(prevIndex);
    }

    // Fonction pour démarrer l'autoplay
    function startAutoPlay() {
        autoPlayInterval = setInterval(nextSlide, 5000);
    }

    // Fonction pour arrêter l'autoplay
    function stopAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
        }
    }

    // Event listeners pour les boutons
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            stopAutoPlay();
            startAutoPlay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            stopAutoPlay();
            startAutoPlay();
        });
    }

    // Event listeners pour les indicateurs
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            showSlide(index);
            stopAutoPlay();
            startAutoPlay();
        });
    });

    // Pause de l'autoplay au survol
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', stopAutoPlay);
        carouselContainer.addEventListener('mouseleave', startAutoPlay);
    }

    // Navigation au clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            stopAutoPlay();
            startAutoPlay();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            stopAutoPlay();
            startAutoPlay();
        }
    });

    // Swipe pour mobile (touch events)
    let touchStartX = 0;
    let touchEndX = 0;

    carouselContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    carouselContainer.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe gauche - slide suivant
                nextSlide();
            } else {
                // Swipe droite - slide précédent
                prevSlide();
            }
            stopAutoPlay();
            startAutoPlay();
        }
    }

    // Initialisation
    showSlide(0);
    startAutoPlay();

    // Animation d'entrée pour le premier slide
    setTimeout(() => {
        slides[0].classList.add('active');
        if (indicators[0]) {
            indicators[0].classList.add('active');
        }
    }, 100);
});

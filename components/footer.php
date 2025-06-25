<?php
// Vérification du CSS footer
$footerCssPath = '../assets/css/footer.css';
?>
<style>
<?php if (file_exists($footerCssPath)) {
    echo "@import url('$footerCssPath');";
} else {
    echo '/* CSS footer manquant */';
}
?>
</style>



<footer class="site-footer">
    <div class="footer-container">

        <!-- Bloc 1 : Présentation -->
        <div class="footer-brand">
            <?php 
            $logoPath = '../assets/img/logo_ensae.png';
            if (file_exists($logoPath)) {
                echo '<img src="' . $logoPath . '" alt="Logo ENSAE" class="footer-logo">';
            } else {
                echo '<span style="color:red;font-weight:bold">Logo manquant</span>';
            }
            ?>
            <div>
                <h4>E-election ENSAE Dakar</h4>
                <p>Plateforme officielle de vote électronique pour les étudiants de l'ENSAE Dakar.</p>
            </div>
        </div>

        <!-- Bloc 2 : Liens utiles -->
        <div class="footer-links">
            <h4>Liens rapides</h4>
            <ul>
                <li><a href="campagnes.php">Campagnes</a></li>
                <li><a href="vote.php">Voter</a></li>
                <li><a href="statistique.php">Statistiques</a></li>
                <li><a href="resultat.php">Résultats</a></li>
            </ul>
        </div>

        <!-- Bloc 3 : Réseaux sociaux -->
        <div class="footer-social">
            <h4>Suivez-nous</h4>
            <div class="social-icons">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" aria-label="Site Web"><i class="fas fa-globe"></i></a>
            </div>
        </div>
    </div>

    <!-- Footer bas -->
    <div class="footer-bottom">
        <p>© 2025 E-election ENSAE Dakar — Tous droits réservés</p>
        <a href="#top" class="back-to-top">↑ Retour en haut</a>
    </div>
</footer>
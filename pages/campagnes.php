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

// Récupérer les candidatures validées avec toutes les informations
$sql = "SELECT c.id AS candidat_id,
       u.nom, u.prenom, u.photo AS user_photo, u.classe,
       c.photo AS candidat_photo, c.programme_pdf,
       p.nom_poste, p.id AS poste_id,
       e.titre AS election_titre, e.id AS election_id, e.portee, e.classe_cible,
       te.nom_type
FROM candidats c
JOIN users u ON u.id = c.user_id
JOIN postes p ON p.id = c.poste_id
JOIN elections e ON e.id = p.election_id
JOIN types_elections te ON te.id = e.type_id
WHERE c.statut = 'valide'
ORDER BY e.titre, p.nom_poste, u.nom";

$candidatures = $db->fetchAll($sql);

// Filtrer les candidatures selon la portée de l'élection
$candidaturesFiltrees = [];
foreach ($candidatures as $candidature) {
    // Si c'est une élection générale, tous les étudiants peuvent voir
    if ($candidature['portee'] === 'generale') {
        $candidaturesFiltrees[] = $candidature;
    }
    // Si c'est une élection spécifique, seuls les étudiants de la classe cible peuvent voir
    elseif ($candidature['portee'] === 'specifique' && $candidature['classe_cible'] === $currentUser['classe']) {
        $candidaturesFiltrees[] = $candidature;
    }
}

// Grouper les candidatures par élection et poste
$electionsGrouped = [];
foreach ($candidaturesFiltrees as $candidature) {
    $electionId = $candidature['election_id'];
    $posteId = $candidature['poste_id'];
    
    if (!isset($electionsGrouped[$electionId])) {
        $electionsGrouped[$electionId] = [
            'titre' => $candidature['election_titre'],
            'portee' => $candidature['portee'],
            'classe_cible' => $candidature['classe_cible'],
            'type' => $candidature['nom_type'],
            'postes' => []
        ];
    }
    
    if (!isset($electionsGrouped[$electionId]['postes'][$posteId])) {
        $electionsGrouped[$electionId]['postes'][$posteId] = [
            'nom_poste' => $candidature['nom_poste'],
            'candidats' => []
        ];
    }
    
    $electionsGrouped[$electionId]['postes'][$posteId]['candidats'][] = $candidature;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campagnes Électorales - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/campagne.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .campagnes-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .page-header h1 {
        color: var(--primary);
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: var(--gray);
        font-size: 1.1rem;
    }

    .filters-section {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text);
    }

    .filter-group select {
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 1rem;
        background: white;
    }

    .filter-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    }

    .election-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .election-header {
        background: var(--primary);
        color: white;
        padding: 1.5rem;
    }

    .election-header.generale {
        background: var(--primary);
    }

    .election-header.specifique {
        background: #7c3aed;
        /* Violet pour les élections spécifiques */
    }

    .election-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
    }

    .election-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .election-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .election-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .election-badge.generale {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .election-badge.specifique {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .poste-section {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
    }

    .poste-section:last-child {
        border-bottom: none;
    }

    .poste-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .candidats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .candidat-card {
        background: #f8fafc;
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid var(--border);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .candidat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .candidat-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .candidat-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary);
    }

    .candidat-info h3 {
        margin: 0 0 0.25rem 0;
        color: var(--text);
        font-size: 1.1rem;
    }

    .candidat-info p {
        margin: 0;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .candidat-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-secondary {
        background: var(--gray);
        color: white;
    }

    .btn-success {
        background: #16a34a;
        color: white;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .no-candidatures {
        text-align: center;
        padding: 3rem;
        color: var(--gray);
    }

    .no-candidatures i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .election-hidden {
        background: #fef3c7;
        color: #92400e;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        text-align: center;
    }

    @media (max-width: 768px) {
        .campagnes-container {
            padding: 1rem;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .candidats-grid {
            grid-template-columns: 1fr;
        }

        .candidat-header {
            flex-direction: column;
            text-align: center;
        }

        .candidat-actions {
            justify-content: center;
        }

        .election-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
    }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="campagnes-container">
        <div class="page-header">
            <h1><i class="fas fa-vote-yea"></i> Campagnes Électorales</h1>
            <p>Découvrez les candidats et leurs programmes électoraux</p>

            <!-- Information sur les élections visibles -->
            <div
                style="background: #e0f2fe; border: 1px solid #0288d1; border-radius: 8px; padding: 1rem; margin-top: 1rem; text-align: left;">
                <h4 style="margin: 0 0 0.5rem 0; color: #0277bd;">
                    <i class="fas fa-info-circle"></i> Élections visibles pour votre classe
                    (<?php echo htmlspecialchars($currentUser['classe']); ?>)
                </h4>
                <ul style="margin: 0; padding-left: 1.5rem; color: #01579b;">
                    <li><strong>Élections générales :</strong> Visibles par tous les étudiants</li>
                    <li><strong>Élections spécifiques à <?php echo htmlspecialchars($currentUser['classe']); ?>
                            :</strong> Visibles uniquement par votre classe</li>
                </ul>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters-section">
            <h3 style="margin-bottom: 1rem; color: var(--primary);">
                <i class="fas fa-filter"></i> Filtres
            </h3>
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="filterElection">Élection</label>
                    <select id="filterElection" onchange="filterCandidatures()">
                        <option value="">Toutes les élections</option>
                        <?php
                        $elections = array_unique(array_column($candidaturesFiltrees, 'election_titre'));
                        foreach ($elections as $election) {
                            echo "<option value='" . htmlspecialchars($election) . "'>" . htmlspecialchars($election) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filterPoste">Poste</label>
                    <select id="filterPoste" onchange="filterCandidatures()">
                        <option value="">Tous les postes</option>
                        <?php
                        $postes = array_unique(array_column($candidaturesFiltrees, 'nom_poste'));
                        foreach ($postes as $poste) {
                            echo "<option value='" . htmlspecialchars($poste) . "'>" . htmlspecialchars($poste) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filterClasse">Classe</label>
                    <select id="filterClasse" onchange="filterCandidatures()">
                        <option value="">Toutes les classes</option>
                        <?php
                        // Toutes les classes possibles
                        $toutesClasses = [
                            'AS1', 'AS2', 'AS3',
                            'ISEP1', 'ISEP2', 'ISEP3',
                            'ISE1 math', 'ISE1 eco',
                            'ISE2', 'ISE3'
                        ];
                        
                        // Classes des candidats avec candidatures validées
                        $classesCandidats = array_unique(array_column($candidaturesFiltrees, 'classe'));
                        
                        // Afficher toutes les classes dans l'ordre
                        foreach ($toutesClasses as $classe) {
                            $selected = '';
                            if (in_array($classe, $classesCandidats)) {
                                echo "<option value='" . htmlspecialchars($classe) . "'>" . htmlspecialchars($classe) . "</option>";
                            } else {
                                echo "<option value='" . htmlspecialchars($classe) . "' disabled>" . htmlspecialchars($classe) . " (aucun candidat)</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filterPortee">Type d'élection</label>
                    <select id="filterPortee" onchange="filterCandidatures()">
                        <option value="">Tous les types</option>
                        <option value="generale">Élections générales</option>
                        <option value="specifique">Élections spécifiques</option>
                    </select>
                </div>

                <div class="filter-group">
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Effacer les filtres
                    </button>
                </div>
            </div>
        </div>

        <!-- Affichage des élections -->
        <div id="electionsContainer">
            <?php if (empty($electionsGrouped)): ?>
            <div class="no-candidatures">
                <i class="fas fa-users"></i>
                <h3>Aucune candidature disponible</h3>
                <p>Il n'y a actuellement aucune candidature validée pour votre classe.</p>
            </div>
            <?php else: ?>
            <?php foreach ($electionsGrouped as $electionId => $election): ?>
            <div class="election-card" data-election="<?php echo htmlspecialchars($election['titre']); ?>">
                <div class="election-header <?php echo $election['portee']; ?>">
                    <h2>
                        <?php echo htmlspecialchars($election['titre']); ?>
                        <span class="election-badge <?php echo $election['portee']; ?>">
                            <?php echo $election['portee'] === 'generale' ? 'GÉNÉRALE' : 'SPÉCIFIQUE'; ?>
                        </span>
                    </h2>
                    <div class="election-meta">
                        <span>
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($election['type']); ?>
                        </span>
                        <span>
                            <i class="fas fa-globe"></i>
                            <?php echo $election['portee'] === 'generale' ? 'Élection générale' : 'Élection spécifique à ' . htmlspecialchars($election['classe_cible']); ?>
                        </span>
                    </div>
                </div>

                <?php foreach ($election['postes'] as $posteId => $poste): ?>
                <div class="poste-section" data-poste="<?php echo htmlspecialchars($poste['nom_poste']); ?>">
                    <div class="poste-title">
                        <i class="fas fa-user-tie"></i>
                        <?php echo htmlspecialchars($poste['nom_poste']); ?>
                    </div>

                    <div class="candidats-grid">
                        <?php foreach ($poste['candidats'] as $candidat): ?>
                        <div class="candidat-card" data-classe="<?php echo htmlspecialchars($candidat['classe']); ?>">
                            <div class="candidat-header">
                                <?php
                                // Logique pour déterminer l'image du candidat
                                $imagePath = '../uploads/default_avatar.jpg'; // Image par défaut
                                
                                if (!empty($candidat['candidat_photo'])) {
                                    $imagePath = '../' . $candidat['candidat_photo'];
                                } elseif (!empty($candidat['user_photo'])) {
                                    // Si user_photo contient déjà "uploads/", on l'utilise directement
                                    if (strpos($candidat['user_photo'], 'uploads/') === 0) {
                                        $imagePath = '../' . $candidat['user_photo'];
                                    } else {
                                        // Sinon on ajoute le préfixe uploads/
                                        $imagePath = '../uploads/' . $candidat['user_photo'];
                                    }
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                    alt="Photo de <?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?>"
                                    class="candidat-photo" onerror="this.src='../uploads/default_avatar.jpg';">
                                <div class="candidat-info">
                                    <h3><?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?>
                                    </h3>
                                    <p><i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($candidat['classe']); ?></p>
                                </div>
                            </div>

                            <div class="candidat-actions">
                                <?php if ($candidat['programme_pdf']): ?>
                                <a href="../<?php echo htmlspecialchars($candidat['programme_pdf']); ?>"
                                    class="btn btn-primary" target="_blank" title="Lire le programme">
                                    <i class="fas fa-file-pdf"></i> Lire le programme
                                </a>
                                <a href="../<?php echo htmlspecialchars($candidat['programme_pdf']); ?>"
                                    class="btn btn-success" download title="Télécharger le programme">
                                    <i class="fas fa-download"></i> Télécharger
                                </a>
                                <?php else: ?>
                                <span class="btn btn-secondary" style="opacity: 0.6; cursor: not-allowed;">
                                    <i class="fas fa-file-pdf"></i> Programme non disponible
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>

    <script>
    function filterCandidatures() {
        const electionFilter = document.getElementById('filterElection').value.toLowerCase();
        const posteFilter = document.getElementById('filterPoste').value.toLowerCase();
        const classeFilter = document.getElementById('filterClasse').value.toLowerCase();
        const porteeFilter = document.getElementById('filterPortee').value.toLowerCase();

        const electionCards = document.querySelectorAll('.election-card');
        const candidatCards = document.querySelectorAll('.candidat-card');

        // Filtrer les cartes de candidats
        candidatCards.forEach(card => {
            const classe = card.getAttribute('data-classe').toLowerCase();
            const classeMatch = !classeFilter || classe === classeFilter;

            if (classeMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });

        // Filtrer les sections de postes
        const posteSections = document.querySelectorAll('.poste-section');
        posteSections.forEach(section => {
            const poste = section.getAttribute('data-poste').toLowerCase();
            const posteMatch = !posteFilter || poste === posteFilter;
            const hasVisibleCandidats = Array.from(section.querySelectorAll('.candidat-card'))
                .some(card => card.style.display !== 'none');

            if (posteMatch && hasVisibleCandidats) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });

        // Filtrer les cartes d'élection
        electionCards.forEach(card => {
            const election = card.getAttribute('data-election').toLowerCase();
            const electionMatch = !electionFilter || election === electionFilter;
            const hasVisiblePostes = Array.from(card.querySelectorAll('.poste-section'))
                .some(section => section.style.display !== 'none');

            // Filtre par type d'élection (portée)
            const electionHeader = card.querySelector('.election-header');
            const isGenerale = electionHeader.classList.contains('generale');
            const isSpecifique = electionHeader.classList.contains('specifique');
            const porteeMatch = !porteeFilter ||
                (porteeFilter === 'generale' && isGenerale) ||
                (porteeFilter === 'specifique' && isSpecifique);

            if (electionMatch && hasVisiblePostes && porteeMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function clearFilters() {
        document.getElementById('filterElection').value = '';
        document.getElementById('filterPoste').value = '';
        document.getElementById('filterClasse').value = '';
        document.getElementById('filterPortee').value = '';

        // Afficher tous les éléments
        const allElements = document.querySelectorAll('.election-card, .poste-section, .candidat-card');
        allElements.forEach(element => {
            element.style.display = 'block';
        });
    }

    // Initialiser les filtres au chargement
    document.addEventListener('DOMContentLoaded', function() {
        // Créer une image par défaut si elle n'existe pas
        const defaultAvatar = new Image();
        defaultAvatar.onerror = function() {
            // Si l'image par défaut n'existe pas, utiliser une icône
            const photos = document.querySelectorAll('.candidat-photo');
            photos.forEach(photo => {
                if (photo.src.includes('ali.jpg')) {
                    photo.style.display = 'none';
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-user';
                    icon.style.cssText =
                        'font-size: 80px; color: var(--gray); margin: 0 auto; display: block;';
                    photo.parentNode.insertBefore(icon, photo);
                }
            });
        };
        defaultAvatar.src = '../assets/img/ali.jpg';
    });
    </script>
</body>

</html>
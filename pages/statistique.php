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

// Récupérer toutes les élections (terminées et en cours)
$sql = "SELECT e.*, te.nom_type,
        (SELECT COUNT(*) FROM postes WHERE election_id = e.id) as nb_postes,
        (SELECT COUNT(DISTINCT v.user_id) FROM votes v 
         JOIN postes p ON v.poste_id = p.id 
         WHERE p.election_id = e.id) as nb_votants
        FROM elections e
        JOIN types_elections te ON e.type_id = te.id
        ORDER BY e.date_fin DESC";

$elections = $db->fetchAll($sql);

// Récupérer le nombre total d'étudiants
$totalStudents = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'etudiant'")['count'];

// Traitement des filtres
$selectedElection = $_GET['election'] ?? null;
$selectedPoste = $_GET['poste'] ?? null;

// Récupérer les statistiques détaillées si une élection est sélectionnée
$electionStats = null;
$postesStats = null;
$candidatsStats = null;

if ($selectedElection) {
    // Statistiques de l'élection
    $electionStats = $db->fetchOne("SELECT e.*, te.nom_type,
        (SELECT COUNT(*) FROM postes WHERE election_id = e.id) as nb_postes,
        (SELECT COUNT(DISTINCT v.user_id) FROM votes v 
         JOIN postes p ON v.poste_id = p.id 
         WHERE p.election_id = e.id) as nb_votants,
        (SELECT COUNT(*) FROM users WHERE role = 'etudiant' AND 
         (e.portee = 'generale' OR (e.portee = 'specifique' AND classe = e.classe_cible))) as nb_eligibles
        FROM elections e
        JOIN types_elections te ON e.type_id = te.id
        WHERE e.id = :id", ['id' => $selectedElection]);

    // Statistiques par poste
    $postesStats = $db->fetchAll("SELECT p.*,
        (SELECT COUNT(*) FROM candidats WHERE poste_id = p.id AND statut = 'valide') as nb_candidats,
        (SELECT COUNT(*) FROM votes WHERE poste_id = p.id) as nb_votes
        FROM postes p
        WHERE p.election_id = :election_id
        ORDER BY p.nom_poste", ['election_id' => $selectedElection]);

    // Statistiques par candidat
    $candidatsStats = $db->fetchAll("SELECT c.*, u.nom, u.prenom, u.classe, p.nom_poste,
        (SELECT COUNT(*) FROM votes WHERE candidat_id = c.id) as nb_votes
        FROM candidats c
        JOIN users u ON c.user_id = u.id
        JOIN postes p ON c.poste_id = p.id
        WHERE c.statut = 'valide' AND p.election_id = :election_id
        ORDER BY p.nom_poste, nb_votes DESC", ['election_id' => $selectedElection]);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .stats-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .page-header h1 {
        color: var(--primary);
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .page-header p {
        color: var(--gray);
        font-size: 1.1rem;
    }

    .elections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .election-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, 0.1);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .election-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .election-card.selected {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .election-header {
        background: linear-gradient(135deg, var(--primary), #1e3a8a);
        color: white;
        padding: 1.5rem;
    }

    .election-header h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.2rem;
    }

    .election-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.85rem;
        opacity: 0.9;
        flex-wrap: wrap;
    }

    .election-meta span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .election-stats {
        padding: 1.5rem;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border);
    }

    .stat-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .stat-label {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .stat-value {
        font-weight: 600;
        color: var(--text);
    }

    .participation-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .participation-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-en_cours {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-terminee {
        background: #d1fae5;
        color: #065f46;
    }

    .status-en_attente {
        background: #fef3c7;
        color: #92400e;
    }

    .stats-details {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .stats-header {
        background: var(--primary);
        color: white;
        padding: 1.5rem;
    }

    .stats-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }

    .stats-content {
        padding: 2rem;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .chart-container {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }

    .chart-container h3 {
        margin-bottom: 1rem;
        color: var(--primary);
    }

    .postes-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
    }

    .postes-table th,
    .postes-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    .postes-table th {
        background: var(--primary-light);
        color: var(--primary);
        font-weight: 600;
    }

    .candidats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .candidat-stat-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border);
    }

    .candidat-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .candidat-photo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
    }

    .candidat-info h4 {
        margin: 0 0 0.25rem 0;
        color: var(--text);
    }

    .candidat-info p {
        margin: 0;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .vote-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .vote-count {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary);
    }

    .vote-percentage {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 0.5rem;
    }

    .progress-fill {
        height: 100%;
        background: var(--primary);
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .no-data {
        text-align: center;
        padding: 3rem;
        color: var(--gray);
    }

    .no-data i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .chart-container canvas {
        max-width: 260px;
        max-height: 120px;
        width: 260px !important;
        height: 120px !important;
        margin: 0 auto;
        display: block;
    }

    @media (max-width: 768px) {
        .stats-container {
            padding: 1rem;
        }

        .elections-grid {
            grid-template-columns: 1fr;
        }

        .charts-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .candidats-grid {
            grid-template-columns: 1fr;
        }

        .election-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
    }

    @media (max-width: 600px) {
        .chart-container canvas {
            max-width: 100% !important;
            width: 100% !important;
            height: auto !important;
        }

        .stats-container {
            padding: 0.5rem;
        }

        .charts-grid {
            display: block !important;
        }

        .candidats-grid {
            display: block !important;
        }

        .stats-details {
            margin-bottom: 1.5rem;
        }

        .postes-table,
        .postes-table thead,
        .postes-table tbody,
        .postes-table tr,
        .postes-table th,
        .postes-table td {
            display: block;
            width: 100%;
            box-sizing: border-box;
        }

        .postes-table {
            overflow-x: auto;
            white-space: nowrap;
            border-spacing: 0;
        }

        .postes-table th,
        .postes-table td {
            padding: 0.5rem 0.25rem;
            text-align: left;
        }

        .candidat-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .page-header h1 {
            font-size: 1.3rem;
        }
    }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="stats-container">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Statistiques</h1>
            <p>Suivez la participation et les résultats des élections</p>
        </div>

        <!-- Sélection des élections -->
        <div class="elections-grid">
            <?php foreach ($elections as $election): ?>
            <div class="election-card <?php echo $selectedElection == $election['id'] ? 'selected' : ''; ?>"
                onclick="selectElection(<?php echo $election['id']; ?>)">
                <div class="election-header">
                    <h3><?php echo htmlspecialchars($election['titre']); ?></h3>
                    <div class="election-meta">
                        <span>
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($election['nom_type']); ?>
                        </span>
                        <span>
                            <i class="fas fa-globe"></i>
                            <?php echo $election['portee'] === 'generale' ? 'Générale' : 'Spécifique'; ?>
                        </span>
                        <span class="status-badge status-<?php echo $election['statut']; ?>">
                            <?php 
                                switch($election['statut']) {
                                    case 'en_cours': echo 'En cours'; break;
                                    case 'terminee': echo 'Terminée'; break;
                                    case 'en_attente': echo 'En attente'; break;
                                }
                                ?>
                        </span>
                    </div>
                </div>
                <div class="election-stats">
                    <div class="stat-row">
                        <span class="stat-label">Postes à élire</span>
                        <span class="stat-value"><?php echo $election['nb_postes']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Votants</span>
                        <span class="stat-value"><?php echo $election['nb_votants']; ?></span>
                    </div>
                    <?php if ($election['statut'] !== 'en_attente'): ?>
                    <div class="stat-row">
                        <span class="stat-label">Taux de participation</span>
                        <span class="stat-value">
                            <?php 
                                    $participation = $election['nb_votants'] > 0 ? 
                                        round(($election['nb_votants'] / $totalStudents) * 100, 1) : 0;
                                    echo $participation . '%';
                                    ?>
                        </span>
                    </div>
                    <div class="participation-bar">
                        <div class="participation-fill" style="width: <?php echo $participation; ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 1rem; text-align: center;">
                    <button class="btn btn-primary see-stat"
                        onclick="event.stopPropagation(); selectStatAndScroll(<?php echo $election['id']; ?>);">
                        <i class="fas fa-eye"></i> Voir Statistique
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($selectedElection && $electionStats): ?>
        <!-- Détails de l'élection sélectionnée -->
        <div class="stats-details">
            <div class="stats-header">
                <h2><i class="fas fa-chart-line"></i> <?php echo htmlspecialchars($electionStats['titre']); ?></h2>
            </div>
            <div class="stats-content">
                <!-- Statistiques générales -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <h3>Taux de participation global</h3>
                        <canvas id="participationChart" width="260" height="120"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3>Répartition des votes par poste</h3>
                        <canvas id="postesChart" width="260" height="120"></canvas>
                    </div>
                </div>

                <!-- Tableau des postes -->
                <h3><i class="fas fa-list"></i> Détails par poste</h3>
                <div class="table-responsive">
                    <table class="postes-table">
                        <thead>
                            <tr>
                                <th>Poste</th>
                                <th>Candidats</th>
                                <th>Votes enregistrés</th>
                                <th>Taux de participation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($postesStats as $poste): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($poste['nom_poste']); ?></strong></td>
                                <td><?php echo $poste['nb_candidats']; ?> candidat(s)</td>
                                <td><?php echo $poste['nb_votes']; ?> vote(s)</td>
                                <td>
                                    <?php 
                                            $posteParticipation = $electionStats['nb_eligibles'] > 0 ? 
                                                round(($poste['nb_votes'] / $electionStats['nb_eligibles']) * 100, 1) : 0;
                                            echo $posteParticipation . '%';
                                            ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Résultats par candidat -->
                <h3><i class="fas fa-trophy"></i> Résultats par candidat</h3>
                <div class="candidats-grid">
                    <?php 
                        $currentPoste = null;
                        foreach ($candidatsStats as $candidat): 
                            if ($currentPoste !== $candidat['nom_poste']):
                                if ($currentPoste !== null) echo '</div>'; // Fermer le groupe précédent
                                $currentPoste = $candidat['nom_poste'];
                        ?>
                    <h4 style="grid-column: 1 / -1; margin: 1rem 0 0.5rem 0; color: var(--primary);">
                        <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($currentPoste); ?>
                    </h4>
                    <div style="display: contents;">
                        <?php endif; ?>

                        <div class="candidat-stat-card">
                            <div class="candidat-header">
                                <?php
                                // Logique pour déterminer l'image du candidat
                                $imagePath = '../uploads/default_avatar.jpg'; // Image par défaut
                                
                                if (!empty($candidat['photo'])) {
                                    // Si photo contient déjà "uploads/", on l'utilise directement
                                    if (strpos($candidat['photo'], 'uploads/') === 0) {
                                        $imagePath = '../' . $candidat['photo'];
                                    } else {
                                        // Sinon on ajoute le préfixe uploads/
                                        $imagePath = '../uploads/' . $candidat['photo'];
                                    }
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                    alt="Photo de <?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?>"
                                    class="candidat-photo" onerror="this.src='../uploads/default_avatar.jpg';">
                                <div class="candidat-info">
                                    <h4><?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?>
                                    </h4>
                                    <p><i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($candidat['classe']); ?></p>
                                </div>
                            </div>

                            <div class="vote-stats">
                                <div>
                                    <div class="vote-count"><?php echo $candidat['nb_votes']; ?></div>
                                    <div class="vote-percentage">votes</div>
                                </div>
                                <div>
                                    <?php 
                                    $totalVotesPoste = array_sum(array_map(function($c) use ($candidat) {
                                        return $c['nom_poste'] === $candidat['nom_poste'] ? $c['nb_votes'] : 0;
                                    }, $candidatsStats));
                                    
                                    $percentage = $totalVotesPoste > 0 ? 
                                        round(($candidat['nb_votes'] / $totalVotesPoste) * 100, 1) : 0;
                                    ?>
                                    <div class="vote-percentage"><?php echo $percentage; ?>%</div>
                                </div>
                            </div>

                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>

                        <?php endforeach; ?>
                        <?php if ($currentPoste !== null) echo '</div>'; // Fermer le dernier groupe ?>
                    </div>
                </div>
            </div>
            <?php elseif (empty($elections)): ?>
            <div class="no-data">
                <i class="fas fa-chart-bar"></i>
                <h3>Aucune élection disponible</h3>
                <p>Aucune élection n'a encore été créée.</p>
            </div>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-mouse-pointer"></i>
                <h3>Sélectionnez une élection</h3>
                <p>Cliquez sur une élection ci-dessus pour voir ses statistiques détaillées.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php include '../components/footer.php'; ?>

        <script>
        function selectElection(electionId) {
            window.location.href = 'statistique.php?election=' + electionId;
        }

        function selectStatAndScroll(electionId) {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('election') == electionId && document.querySelector('.stats-details')) {
                document.querySelector('.stats-details').scrollIntoView({
                    behavior: 'smooth'
                });
                return;
            }
            window.location.href = 'statistique.php?election=' + electionId + '#stat-details';
        }

        // Graphiques
        <?php if ($selectedElection && $electionStats): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Graphique de participation
            const participationCtx = document.getElementById('participationChart').getContext('2d');
            new Chart(participationCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Votants', 'Non votants'],
                    datasets: [{
                        data: [
                            <?php echo $electionStats['nb_votants']; ?>,
                            <?php echo $electionStats['nb_eligibles'] - $electionStats['nb_votants']; ?>
                        ],
                        backgroundColor: ['#10b981', '#e5e7eb'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Graphique des postes
            const postesCtx = document.getElementById('postesChart').getContext('2d');
            new Chart(postesCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($poste) { 
                        return '"' . addslashes($poste['nom_poste']) . '"'; 
                    }, $postesStats)); ?>],
                    datasets: [{
                        label: 'Votes',
                        data: [<?php echo implode(',', array_map(function($poste) { 
                            return $poste['nb_votes']; 
                        }, $postesStats)); ?>],
                        backgroundColor: '#2563eb',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
        <?php endif; ?>
        </script>
</body>

</html>
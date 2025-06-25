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

// Récupérer les élections terminées
$sql = "SELECT e.*, te.nom_type,
        (SELECT COUNT(*) FROM postes WHERE election_id = e.id) as nb_postes,
        (SELECT COUNT(DISTINCT v.user_id) FROM votes v 
         JOIN postes p ON v.poste_id = p.id 
         WHERE p.election_id = e.id) as nb_votants
        FROM elections e
        JOIN types_elections te ON e.type_id = te.id
        WHERE e.statut = 'terminee'
        ORDER BY e.date_fin DESC";

$elections = $db->fetchAll($sql);

// Traitement des filtres
$selectedElection = $_GET['election'] ?? null;

// Récupérer les résultats détaillés si une élection est sélectionnée
$electionResults = null;
$postesResults = null;
$winners = null;

if ($selectedElection) {
    // Informations de l'élection
    $electionResults = $db->fetchOne("SELECT e.*, te.nom_type,
        (SELECT COUNT(*) FROM postes WHERE election_id = e.id) as nb_postes,
        (SELECT COUNT(DISTINCT v.user_id) FROM votes v 
         JOIN postes p ON v.poste_id = p.id 
         WHERE p.election_id = e.id) as nb_votants,
        (SELECT COUNT(*) FROM users WHERE role = 'etudiant' AND 
         (e.portee = 'generale' OR (e.portee = 'specifique' AND classe = e.classe_cible))) as nb_eligibles
        FROM elections e
        JOIN types_elections te ON e.type_id = te.id
        WHERE e.id = :id AND e.statut = 'terminee'", ['id' => $selectedElection]);

    if ($electionResults) {
        // Résultats par poste avec classement
        $postesResults = $db->fetchAll("SELECT p.*,
            (SELECT COUNT(*) FROM candidats WHERE poste_id = p.id AND statut = 'valide') as nb_candidats,
            (SELECT COUNT(*) FROM votes WHERE poste_id = p.id) as nb_votes
            FROM postes p
            WHERE p.election_id = :election_id
            ORDER BY p.nom_poste", ['election_id' => $selectedElection]);

        // Résultats détaillés par candidat avec classement
        $candidatsResults = $db->fetchAll("SELECT c.*, u.nom, u.prenom, u.classe, p.nom_poste, p.id as poste_id,
            (SELECT COUNT(*) FROM votes WHERE candidat_id = c.id) as nb_votes,
            (SELECT COUNT(*) FROM votes WHERE poste_id = p.id) as total_votes_poste
            FROM candidats c
            JOIN users u ON c.user_id = u.id
            JOIN postes p ON c.poste_id = p.id
            WHERE c.statut = 'valide' AND p.election_id = :election_id
            ORDER BY p.nom_poste, nb_votes DESC", ['election_id' => $selectedElection]);

        // Organiser les résultats par poste avec classement
        $resultsByPoste = [];
        foreach ($candidatsResults as $candidat) {
            $posteId = $candidat['poste_id'];
            if (!isset($resultsByPoste[$posteId])) {
                $resultsByPoste[$posteId] = [
                    'nom_poste' => $candidat['nom_poste'],
                    'candidats' => []
                ];
            }
            $resultsByPoste[$posteId]['candidats'][] = $candidat;
        }

        // Ajouter le classement pour chaque poste
        foreach ($resultsByPoste as $posteId => &$poste) {
            $rank = 1;
            $previousVotes = null;
            foreach ($poste['candidats'] as &$candidat) {
                if ($previousVotes !== null && $candidat['nb_votes'] < $previousVotes) {
                    $rank++;
                }
                $candidat['rank'] = $rank;
                $candidat['is_winner'] = ($rank === 1);
                $previousVotes = $candidat['nb_votes'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats Officiels - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .results-container {
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
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
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

    .results-details {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .results-header {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        padding: 1.5rem;
    }

    .results-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }

    .results-content {
        padding: 2rem;
    }

    .election-summary {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .summary-item {
        text-align: center;
    }

    .summary-number {
        font-size: 2rem;
        font-weight: bold;
        color: #059669;
        margin-bottom: 0.5rem;
    }

    .summary-label {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .poste-results {
        margin-bottom: 3rem;
    }

    .poste-header {
        background: var(--primary-light);
        color: var(--primary);
        padding: 1rem 1.5rem;
        border-radius: 12px 12px 0 0;
        font-weight: 600;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .candidats-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 0 0 12px 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .candidats-table th,
    .candidats-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    .candidats-table th {
        background: #f8fafc;
        color: var(--primary);
        font-weight: 600;
    }

    .candidats-table tr:last-child td {
        border-bottom: none;
    }

    .candidat-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .candidat-photo {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
    }

    .candidat-details h4 {
        margin: 0 0 0.25rem 0;
        color: var(--text);
    }

    .candidat-details p {
        margin: 0;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .rank-1 {
        background: #fbbf24;
        color: #92400e;
    }

    .rank-2 {
        background: #9ca3af;
        color: #374151;
    }

    .rank-3 {
        background: #f59e0b;
        color: #92400e;
    }

    .rank-other {
        background: #e5e7eb;
        color: #6b7280;
    }

    .winner-badge {
        background: #10b981;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .vote-count {
        font-weight: 600;
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

    .charts-section {
        margin-top: 2rem;
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

    .no-results {
        text-align: center;
        padding: 3rem;
        color: var(--gray);
    }

    .no-results i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .chart-container canvas {
        max-width: 300px;
        max-height: 140px;
        width: 300px !important;
        height: 140px !important;
        margin: 0 auto;
        display: block;
    }

    @media (max-width: 768px) {
        .results-container {
            padding: 1rem;
        }

        .elections-grid {
            grid-template-columns: 1fr;
        }

        .charts-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .election-meta {
            flex-direction: column;
            gap: 0.5rem;
        }

        .candidat-info {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }

        .candidats-table th,
        .candidats-table td {
            padding: 0.75rem 0.5rem;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 600px) {
        .chart-container canvas {
            max-width: 100% !important;
            width: 100% !important;
            height: auto !important;
        }

        .results-container {
            padding: 0.5rem;
        }

        .charts-grid {
            display: block !important;
        }

        .candidats-grid {
            display: block !important;
        }

        .poste-results {
            margin-bottom: 1.5rem;
        }

        .candidats-table,
        .candidats-table thead,
        .candidats-table tbody,
        .candidats-table tr,
        .candidats-table th,
        .candidats-table td {
            display: block;
            width: 100%;
            box-sizing: border-box;
        }

        .candidats-table {
            overflow-x: auto;
            white-space: nowrap;
            border-spacing: 0;
        }

        .candidats-table th,
        .candidats-table td {
            padding: 0.5rem 0.25rem;
            text-align: left;
        }

        .candidat-info {
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

    <div class="results-container">
        <div class="page-header">
            <h1><i class="fas fa-trophy"></i> Résultats Officiels</h1>
            <p>Consultez les résultats des élections terminées</p>
        </div>

        <!-- Sélection des élections -->
        <div class="elections-grid">
            <?php foreach ($elections as $election): ?>
            <div class="election-card <?php echo $selectedElection == $election['id'] ? 'selected' : ''; ?>"
                onclick="selectElectionAndScroll(<?php echo $election['id']; ?>)">
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
                        <span>
                            <i class="fas fa-calendar-check"></i>
                            Terminée le <?php echo date('d/m/Y', strtotime($election['date_fin'])); ?>
                        </span>
                    </div>
                </div>
                <div class="election-stats">
                    <div class="stat-row">
                        <span class="stat-label">Postes élus</span>
                        <span class="stat-value"><?php echo $election['nb_postes']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Votants</span>
                        <span class="stat-value"><?php echo $election['nb_votants']; ?></span>
                    </div>
                </div>
                <div style="padding: 1rem; text-align: center;">
                    <button class="btn btn-primary see-result"
                        onclick="event.stopPropagation(); selectElectionAndScroll(<?php echo $election['id']; ?>);">
                        <i class="fas fa-eye"></i> Voir Résultat
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($elections)): ?>
        <div class="no-results">
            <i class="fas fa-trophy"></i>
            <h3>Aucune élection terminée</h3>
            <p>Aucune élection n'a encore été clôturée.</p>
        </div>
        <?php elseif ($selectedElection && $electionResults): ?>
        <!-- Résultats détaillés de l'élection sélectionnée -->
        <div class="results-details">
            <div class="results-header">
                <h2><i class="fas fa-award"></i> <?php echo htmlspecialchars($electionResults['titre']); ?></h2>
            </div>
            <div class="results-content">
                <!-- Résumé de l'élection -->
                <div class="election-summary">
                    <h3 style="margin-bottom: 1rem; color: #059669;">
                        <i class="fas fa-chart-pie"></i> Résumé de l'élection
                    </h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-number"><?php echo $electionResults['nb_postes']; ?></div>
                            <div class="summary-label">Postes élus</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number"><?php echo $electionResults['nb_votants']; ?></div>
                            <div class="summary-label">Votants</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number"><?php echo $electionResults['nb_eligibles']; ?></div>
                            <div class="summary-label">Électeurs éligibles</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-number">
                                <?php 
                                    $participation = $electionResults['nb_eligibles'] > 0 ? 
                                        round(($electionResults['nb_votants'] / $electionResults['nb_eligibles']) * 100, 1) : 0;
                                    echo $participation . '%';
                                    ?>
                            </div>
                            <div class="summary-label">Taux de participation</div>
                        </div>
                    </div>
                </div>

                <!-- Résultats par poste -->
                <?php foreach ($resultsByPoste as $posteId => $poste): ?>
                <div class="poste-results">
                    <div class="poste-header">
                        <i class="fas fa-user-tie"></i>
                        <?php echo htmlspecialchars($poste['nom_poste']); ?>
                    </div>
                    <table class="candidats-table">
                        <thead>
                            <tr>
                                <th>Classement</th>
                                <th>Candidat</th>
                                <th>Votes</th>
                                <th>Pourcentage</th>
                                <th>Répartition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($poste['candidats'] as $candidat): ?>
                            <tr <?php echo $candidat['is_winner'] ? 'style="background: #f0fdf4;"' : ''; ?>>
                                <td>
                                    <span
                                        class="rank-badge rank-<?php echo $candidat['rank'] <= 3 ? $candidat['rank'] : 'other'; ?>">
                                        <?php echo $candidat['rank']; ?>
                                    </span>
                                    <?php if ($candidat['is_winner']): ?>
                                    <span class="winner-badge">
                                        <i class="fas fa-crown"></i> Gagnant
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="candidat-info">
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
                                        <div class="candidat-details">
                                            <h4><?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?>
                                            </h4>
                                            <p><i class="fas fa-graduation-cap"></i>
                                                <?php echo htmlspecialchars($candidat['classe']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="vote-count"><?php echo $candidat['nb_votes']; ?></div>
                                    <div class="vote-percentage">votes</div>
                                </td>
                                <td>
                                    <?php 
                                                $percentage = $candidat['total_votes_poste'] > 0 ? 
                                                    round(($candidat['nb_votes'] / $candidat['total_votes_poste']) * 100, 1) : 0;
                                                ?>
                                    <div class="vote-percentage"><?php echo $percentage; ?>%</div>
                                </td>
                                <td style="width: 200px;">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>

                <!-- Graphiques -->
                <div class="charts-section">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary);">
                        <i class="fas fa-chart-bar"></i> Visualisation des résultats
                    </h3>
                    <div class="charts-grid">
                        <div class="chart-container">
                            <h3>Répartition des votes par poste</h3>
                            <canvas id="postesChart" width="300" height="140"></canvas>
                        </div>
                        <div class="chart-container">
                            <h3>Top 3 des candidats les plus votés</h3>
                            <canvas id="topCandidatsChart" width="300" height="140"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="no-results">
            <i class="fas fa-mouse-pointer"></i>
            <h3>Sélectionnez une élection</h3>
            <p>Cliquez sur une élection ci-dessus pour voir ses résultats officiels.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php include '../components/footer.php'; ?>

    <script>
    function selectElectionAndScroll(electionId) {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('election') == electionId && document.getElementById('resultat-details')) {
            document.getElementById('resultat-details').scrollIntoView({
                behavior: 'smooth'
            });
            return;
        }
        window.location.href = 'resultat.php?election=' + electionId + '#resultat-details';
    }

    // Graphiques
    <?php if ($selectedElection && $electionResults && isset($resultsByPoste)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Graphique des postes
        const postesCtx = document.getElementById('postesChart').getContext('2d');
        new Chart(postesCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($poste) { 
                        return '"' . addslashes($poste['nom_poste']) . '"'; 
                    }, $resultsByPoste)); ?>],
                datasets: [{
                    label: 'Votes',
                    data: [<?php echo implode(',', array_map(function($poste) { 
                            return array_sum(array_column($poste['candidats'], 'nb_votes')); 
                        }, $resultsByPoste)); ?>],
                    backgroundColor: '#059669',
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

        // Graphique des top candidats
        const topCandidatsCtx = document.getElementById('topCandidatsChart').getContext('2d');

        // Récupérer tous les candidats et les trier par votes
        const allCandidats = [];
        <?php foreach ($resultsByPoste as $poste): ?>
        <?php foreach ($poste['candidats'] as $candidat): ?>
        allCandidats.push({
            name: '<?php echo addslashes($candidat['prenom'] . ' ' . $candidat['nom']); ?>',
            votes: <?php echo $candidat['nb_votes']; ?>,
            poste: '<?php echo addslashes($poste['nom_poste']); ?>'
        });
        <?php endforeach; ?>
        <?php endforeach; ?>

        // Trier par votes et prendre le top 3
        allCandidats.sort((a, b) => b.votes - a.votes);
        const top3 = allCandidats.slice(0, 3);

        new Chart(topCandidatsCtx, {
            type: 'doughnut',
            data: {
                labels: top3.map(c => c.name + ' (' + c.poste + ')'),
                datasets: [{
                    data: top3.map(c => c.votes),
                    backgroundColor: ['#fbbf24', '#9ca3af', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    });
    <?php endif; ?>

    window.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash === '#resultat-details') {
            var el = document.getElementById('resultat-details');
            if (el) {
                el.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
    });
    </script>
</body>

</html>
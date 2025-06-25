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

// Vérifier si l'utilisateur est membre d'un comité
$sqlComites = "SELECT c.*, e.titre as election_titre, e.portee, e.classe_cible, e.date_debut, e.date_fin, e.statut, te.nom_type
        FROM comites c
        JOIN elections e ON c.election_id = e.id
        JOIN types_elections te ON e.type_id = te.id
        WHERE c.user_id = :user_id
        ORDER BY e.date_debut DESC";

$comites = $db->fetchAll($sqlComites, ['user_id' => $currentUser['id']]);

// Si l'utilisateur n'est membre d'aucun comité, rediriger
if (empty($comites)) {
    header('Location: index.php');
    exit;
}

// Récupérer les élections où l'utilisateur est membre du comité
$electionsComite = [];
foreach ($comites as $comite) {
    $electionsComite[] = $comite['election_id'];
}

// Récupérer les candidatures en attente pour ces élections
$candidaturesEnAttente = [];
if (!empty($electionsComite)) {
    $sql = "SELECT c.*, u.nom, u.prenom, u.classe, p.nom_poste, e.titre as election_titre
            FROM candidats c
            JOIN users u ON c.user_id = u.id
            JOIN postes p ON c.poste_id = p.id
            JOIN elections e ON p.election_id = e.id
            WHERE c.statut = 'en_attente' 
            AND p.election_id IN (" . implode(',', $electionsComite) . ")
            ORDER BY c.date_candidature DESC";
    
    $candidaturesEnAttente = $db->fetchAll($sql);
}

// Récupérer les statistiques pour les élections gérées
$statistiques = [];
if (!empty($electionsComite)) {
    foreach ($electionsComite as $electionId) {
        $stats = [
            'election_id' => $electionId,
            'total_candidatures' => $db->count('candidats', 'poste_id IN (SELECT id FROM postes WHERE election_id = :election_id)', ['election_id' => $electionId]),
            'candidatures_validees' => $db->count('candidats', 'poste_id IN (SELECT id FROM postes WHERE election_id = :election_id) AND statut = "valide"', ['election_id' => $electionId]),
            'candidatures_en_attente' => $db->count('candidats', 'poste_id IN (SELECT id FROM postes WHERE election_id = :election_id) AND statut = "en_attente"', ['election_id' => $electionId]),
            'total_votes' => $db->count('votes', 'poste_id IN (SELECT id FROM postes WHERE election_id = :election_id)', ['election_id' => $electionId])
        ];
        $statistiques[$electionId] = $stats;
    }
}

// Traitement des actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['candidat_id'])) {
        $candidatId = (int)$_POST['candidat_id'];
        $action = $_POST['action'];
        
        try {
            if ($action === 'valider') {
                $db->update('candidats', ['statut' => 'valide'], 'id = :id', ['id' => $candidatId]);
                $message = "Candidature validée avec succès !";
                $messageType = 'success';
            } elseif ($action === 'rejeter') {
                $db->update('candidats', ['statut' => 'rejete'], 'id = :id', ['id' => $candidatId]);
                $message = "Candidature rejetée.";
                $messageType = 'success';
            }
            
            // Recharger les candidatures en attente
            $candidaturesEnAttente = $db->fetchAll($sql);
            
        } catch (Exception $e) {
            $message = "Erreur : " . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_POST['update_election'])) {
        // Mise à jour d'une élection
        $electionId = (int)$_POST['election_id'];
        $dateDebut = $_POST['date_debut'];
        $dateFin = $_POST['date_fin'];
        $statut = $_POST['statut'];
        
        // Vérifier que l'utilisateur est bien membre du comité de cette élection
        if (!in_array($electionId, $electionsComite)) {
            $message = "Vous n'avez pas les droits pour modifier cette élection.";
            $messageType = 'error';
        } else {
            try {
                $db->update('elections', [
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'statut' => $statut
                ], 'id = :id', ['id' => $electionId]);
                
                $message = "Élection mise à jour avec succès !";
                $messageType = 'success';
                
                // Recharger les données
                $comites = $db->fetchAll($sqlComites, ['user_id' => $currentUser['id']]);
            } catch (Exception $e) {
                $message = "Erreur lors de la mise à jour : " . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['add_poste'])) {
        // Ajout d'un poste
        $electionId = (int)$_POST['election_id'];
        $nomPoste = trim($_POST['nom_poste']);
        
        // Vérifier que l'utilisateur est bien membre du comité de cette élection
        if (!in_array($electionId, $electionsComite)) {
            $message = "Vous n'avez pas les droits pour ajouter un poste à cette élection.";
            $messageType = 'error';
        } else {
            try {
                $db->insert('postes', [
                    'nom_poste' => $nomPoste,
                    'election_id' => $electionId
                ]);
                
                $message = "Poste ajouté avec succès !";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Erreur lors de l'ajout du poste : " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Récupérer les postes pour chaque élection
$postesParElection = [];
if (!empty($electionsComite)) {
    $sql = "SELECT p.*, e.titre as election_titre 
            FROM postes p 
            JOIN elections e ON p.election_id = e.id 
            WHERE p.election_id IN (" . implode(',', $electionsComite) . ")
            ORDER BY e.titre, p.nom_poste";
    $postes = $db->fetchAll($sql);
    
    foreach ($postes as $poste) {
        $postesParElection[$poste['election_id']][] = $poste;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Rôle - Comité d'Organisation</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .role-container {
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
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .page-header p {
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

    .nav-tabs {
        display: flex;
        background: white;
        border-radius: 12px;
        padding: 0.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow-x: auto;
    }

    .nav-tab {
        padding: 0.75rem 1.5rem;
        border: none;
        background: none;
        color: var(--gray);
        cursor: pointer;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .nav-tab.active {
        background: var(--primary);
        color: white;
    }

    .nav-tab:hover:not(.active) {
        background: var(--primary-light);
        color: var(--primary);
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .section-header {
        background: linear-gradient(135deg, var(--primary), #1e3a8a);
        color: white;
        padding: 1.5rem;
    }

    .section-header h2 {
        margin: 0;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-content {
        padding: 2rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
        border: 1px solid var(--border);
    }

    .stat-card i {
        font-size: 2rem;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--text);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .elections-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
    }

    .election-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border);
    }

    .election-card h3 {
        color: var(--primary);
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }

    .election-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .election-info span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-en_attente {
        background: #fef3c7;
        color: #92400e;
    }

    .status-en_cours {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-terminee {
        background: #d1fae5;
        color: #065f46;
    }

    .form-group {
        margin-bottom: 1rem;
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

    .btn-danger {
        background: #dc2626;
        color: white;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .candidatures-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .candidatures-header {
        background: var(--primary-light);
        color: var(--primary);
        padding: 1.5rem;
    }

    .candidatures-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }

    .candidatures-content {
        padding: 2rem;
    }

    .candidature-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
    }

    .candidature-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .candidat-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .candidat-photo {
        width: 60px;
        height: 60px;
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

    .candidature-meta {
        text-align: right;
    }

    .candidature-meta .poste {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 0.25rem;
    }

    .candidature-meta .date {
        color: var(--gray);
        font-size: 0.8rem;
    }

    .candidature-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
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

    .postes-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .poste-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .poste-item h4 {
        margin: 0 0 0.5rem 0;
        color: var(--primary);
    }

    .poste-item p {
        margin: 0;
        color: var(--gray);
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .role-container {
            padding: 1rem;
        }

        .nav-tabs {
            flex-direction: column;
        }

        .elections-grid {
            grid-template-columns: 1fr;
        }

        .candidature-header {
            flex-direction: column;
            gap: 1rem;
        }

        .candidature-meta {
            text-align: left;
        }

        .candidature-actions {
            flex-direction: column;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="role-container">
        <div class="page-header">
            <h1><i class="fas fa-user-tie"></i> Mon Rôle</h1>
            <p>Gestion des élections en tant que membre du comité d'organisation</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Navigation par onglets -->
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('overview')">
                <i class="fas fa-chart-bar"></i> Vue d'ensemble
            </button>
            <button class="nav-tab" onclick="showTab('elections')">
                <i class="fas fa-list"></i> Gérer les élections
            </button>
            <button class="nav-tab" onclick="showTab('candidatures')">
                <i class="fas fa-clock"></i> Candidatures
            </button>
            <button class="nav-tab" onclick="showTab('postes')">
                <i class="fas fa-user-tie"></i> Gérer les postes
            </button>
        </div>

        <!-- Onglet Vue d'ensemble -->
        <div id="overview" class="tab-content active">
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-info-circle"></i> Informations sur votre rôle</h2>
                </div>
                <div class="section-content">
                    <p>En tant que membre du comité d'organisation, vous avez la responsabilité de :</p>
                    <ul style="margin: 1rem 0; padding-left: 1.5rem;">
                        <li>Valider ou rejeter les candidatures soumises</li>
                        <li>Gérer les dates et statuts des élections</li>
                        <li>Ajouter ou modifier les postes à pourvoir</li>
                        <li>Surveiller le bon déroulement des élections</li>
                        <li>Assurer la transparence du processus électoral</li>
                    </ul>
                </div>
            </div>

            <!-- Statistiques globales -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-pie"></i> Statistiques globales</h2>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <div class="stat-number"><?php echo count($comites); ?></div>
                            <div class="stat-label">Élections gérées</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-file-alt"></i>
                            <div class="stat-number"><?php echo count($candidaturesEnAttente); ?></div>
                            <div class="stat-label">Candidatures en attente</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-check-circle"></i>
                            <div class="stat-number">
                                <?php echo array_sum(array_column($statistiques, 'candidatures_validees')); ?></div>
                            <div class="stat-label">Candidatures validées</div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-vote-yea"></i>
                            <div class="stat-number">
                                <?php echo array_sum(array_column($statistiques, 'total_votes')); ?></div>
                            <div class="stat-label">Total des votes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Élections gérées -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Élections que vous gérez</h2>
                </div>
                <div class="section-content">
                    <div class="elections-grid">
                        <?php foreach ($comites as $comite): ?>
                        <div class="election-card">
                            <h3><?php echo htmlspecialchars($comite['election_titre']); ?></h3>
                            <div class="election-info">
                                <span>
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($comite['nom_type']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-globe"></i>
                                    <?php echo $comite['portee'] === 'generale' ? 'Élection générale' : 'Élection spécifique à ' . htmlspecialchars($comite['classe_cible']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    Du <?php echo date('d/m/Y', strtotime($comite['date_debut'])); ?> au
                                    <?php echo date('d/m/Y', strtotime($comite['date_fin'])); ?>
                                </span>
                                <span>
                                    <span class="status-badge status-<?php echo $comite['statut']; ?>">
                                        <?php 
                                            switch($comite['statut']) {
                                                case 'en_attente': echo 'En attente'; break;
                                                case 'en_cours': echo 'En cours'; break;
                                                case 'terminee': echo 'Terminée'; break;
                                            }
                                            ?>
                                    </span>
                                </span>
                            </div>
                            <?php if (isset($statistiques[$comite['election_id']])): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                                <div
                                    style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-size: 0.9rem;">
                                    <div><strong>Candidatures:</strong>
                                        <?php echo $statistiques[$comite['election_id']]['total_candidatures']; ?></div>
                                    <div><strong>Validées:</strong>
                                        <?php echo $statistiques[$comite['election_id']]['candidatures_validees']; ?>
                                    </div>
                                    <div><strong>En attente:</strong>
                                        <?php echo $statistiques[$comite['election_id']]['candidatures_en_attente']; ?>
                                    </div>
                                    <div><strong>Votes:</strong>
                                        <?php echo $statistiques[$comite['election_id']]['total_votes']; ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Gérer les élections -->
        <div id="elections" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-edit"></i> Modifier les élections</h2>
                </div>
                <div class="section-content">
                    <div class="elections-grid">
                        <?php foreach ($comites as $comite): ?>
                        <div class="election-card">
                            <h3><?php echo htmlspecialchars($comite['election_titre']); ?></h3>
                            <form method="POST">
                                <input type="hidden" name="election_id" value="<?php echo $comite['election_id']; ?>">

                                <div class="form-group">
                                    <label for="date_debut_<?php echo $comite['election_id']; ?>">Date de début</label>
                                    <input type="datetime-local" id="date_debut_<?php echo $comite['election_id']; ?>"
                                        name="date_debut"
                                        value="<?php echo date('Y-m-d\TH:i', strtotime($comite['date_debut'])); ?>"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="date_fin_<?php echo $comite['election_id']; ?>">Date de fin</label>
                                    <input type="datetime-local" id="date_fin_<?php echo $comite['election_id']; ?>"
                                        name="date_fin"
                                        value="<?php echo date('Y-m-d\TH:i', strtotime($comite['date_fin'])); ?>"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="statut_<?php echo $comite['election_id']; ?>">Statut</label>
                                    <select id="statut_<?php echo $comite['election_id']; ?>" name="statut" required>
                                        <option value="en_attente"
                                            <?php echo $comite['statut'] === 'en_attente' ? 'selected' : ''; ?>>En
                                            attente</option>
                                        <option value="en_cours"
                                            <?php echo $comite['statut'] === 'en_cours' ? 'selected' : ''; ?>>En cours
                                        </option>
                                        <option value="terminee"
                                            <?php echo $comite['statut'] === 'terminee' ? 'selected' : ''; ?>>Terminée
                                        </option>
                                    </select>
                                </div>

                                <button type="submit" name="update_election" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Mettre à jour
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglet Candidatures -->
        <div id="candidatures" class="tab-content">
            <div class="candidatures-section">
                <div class="candidatures-header">
                    <h2><i class="fas fa-clock"></i> Candidatures en attente de validation</h2>
                </div>
                <div class="candidatures-content">
                    <?php if (empty($candidaturesEnAttente)): ?>
                    <div class="no-candidatures">
                        <i class="fas fa-check-circle"></i>
                        <h3>Aucune candidature en attente</h3>
                        <p>Toutes les candidatures ont été traitées.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($candidaturesEnAttente as $candidature): ?>
                    <div class="candidature-card">
                        <div class="candidature-header">
                            <div class="candidat-info">
                                <img src="<?php echo $candidature['photo'] ? '../uploads/' . htmlspecialchars($candidature['photo']) : '../assets/img/ali.jpg'; ?>"
                                    alt="Photo de <?php echo htmlspecialchars($candidature['prenom'] . ' ' . $candidature['nom']); ?>"
                                    class="candidat-photo">
                                <div class="candidat-details">
                                    <h4><?php echo htmlspecialchars($candidature['prenom'] . ' ' . $candidature['nom']); ?>
                                    </h4>
                                    <p><i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($candidature['classe']); ?></p>
                                </div>
                            </div>
                            <div class="candidature-meta">
                                <div class="poste"><?php echo htmlspecialchars($candidature['nom_poste']); ?></div>
                                <div class="election"><?php echo htmlspecialchars($candidature['election_titre']); ?>
                                </div>
                                <div class="date">Candidature du
                                    <?php echo date('d/m/Y à H:i', strtotime($candidature['date_candidature'])); ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($candidature['programme_pdf']): ?>
                        <div style="margin-bottom: 1rem;">
                            <a href="../uploads/<?php echo htmlspecialchars($candidature['programme_pdf']); ?>"
                                class="btn btn-secondary" target="_blank">
                                <i class="fas fa-file-pdf"></i> Voir le programme
                            </a>
                        </div>
                        <?php endif; ?>

                        <div class="candidature-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="valider">
                                <input type="hidden" name="candidat_id" value="<?php echo $candidature['id']; ?>">
                                <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Valider cette candidature ?')">
                                    <i class="fas fa-check"></i> Valider
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="rejeter">
                                <input type="hidden" name="candidat_id" value="<?php echo $candidature['id']; ?>">
                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Rejeter cette candidature ?')">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Gérer les postes -->
        <div id="postes" class="tab-content">
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-user-tie"></i> Gérer les postes</h2>
                </div>
                <div class="section-content">
                    <!-- Ajouter un nouveau poste -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 12px;">
                        <h3 style="margin-bottom: 1rem; color: var(--primary);">Ajouter un nouveau poste</h3>
                        <form method="POST"
                            style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label for="election_select">Élection</label>
                                <select id="election_select" name="election_id" required>
                                    <option value="">Sélectionner une élection</option>
                                    <?php foreach ($comites as $comite): ?>
                                    <option value="<?php echo $comite['election_id']; ?>">
                                        <?php echo htmlspecialchars($comite['election_titre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="nom_poste">Nom du poste</label>
                                <input type="text" id="nom_poste" name="nom_poste" placeholder="Ex: Président" required>
                            </div>
                            <button type="submit" name="add_poste" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </form>
                    </div>

                    <!-- Liste des postes existants -->
                    <?php foreach ($comites as $comite): ?>
                    <div style="margin-bottom: 2rem;">
                        <h3 style="margin-bottom: 1rem; color: var(--primary);">
                            <?php echo htmlspecialchars($comite['election_titre']); ?>
                        </h3>
                        <?php if (isset($postesParElection[$comite['election_id']])): ?>
                        <div class="postes-list">
                            <?php foreach ($postesParElection[$comite['election_id']] as $poste): ?>
                            <div class="poste-item">
                                <h4><?php echo htmlspecialchars($poste['nom_poste']); ?></h4>
                                <p>ID: <?php echo $poste['id']; ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color: var(--gray); font-style: italic;">Aucun poste défini pour cette élection.</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../components/footer.php'; ?>

    <script>
    function showTab(tabName) {
        // Masquer tous les onglets
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // Désactiver tous les boutons
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // Afficher l'onglet sélectionné
        document.getElementById(tabName).classList.add('active');

        // Activer le bouton correspondant
        event.target.classList.add('active');
    }
    </script>
</body>

</html>
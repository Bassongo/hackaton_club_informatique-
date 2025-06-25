<?php
// Gestion des sessions au tout début, avant tout output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../auth_check.php';

// Vérifier que l'utilisateur est connecté et est un étudiant
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

// Récupérer les élections en cours accessibles à l'utilisateur
$sql = "SELECT DISTINCT e.*, te.nom_type
        FROM elections e
        JOIN types_elections te ON e.type_id = te.id
        WHERE e.statut = 'en_cours'
        AND e.date_debut <= NOW()
        AND e.date_fin >= NOW()
        AND (e.portee = 'generale' OR (e.portee = 'specifique' AND e.classe_cible = :classe))
        ORDER BY e.date_debut DESC";

$elections = $db->fetchAll($sql, ['classe' => $currentUser['classe']]);

// Traitement du vote
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $candidatId = (int)$_POST['candidat_id'];
    $posteId = (int)$_POST['poste_id'];
    $electionId = (int)$_POST['election_id'];
    
    try {
        // Vérifications de sécurité
        $db->getConnection()->beginTransaction();
        
        // 1. Vérifier que l'élection est en cours
        $election = $db->fetchOne("SELECT * FROM elections WHERE id = :id AND statut = 'en_cours'", ['id' => $electionId]);
        if (!$election) {
            throw new Exception("Cette élection n'est plus en cours.");
        }
        
        // 2. Vérifier que l'utilisateur n'a pas déjà voté pour ce poste
        $existingVote = $db->fetchOne("SELECT * FROM votes WHERE user_id = :user_id AND poste_id = :poste_id", 
            ['user_id' => $currentUser['id'], 'poste_id' => $posteId]);
        if ($existingVote) {
            throw new Exception("Vous avez déjà voté pour ce poste.");
        }
        
        // 3. Vérifier que le candidat existe et est valide pour ce poste
        $candidat = $db->fetchOne("SELECT c.*, p.election_id FROM candidats c 
                                  JOIN postes p ON c.poste_id = p.id 
                                  WHERE c.id = :id AND c.statut = 'valide' AND p.id = :poste_id", 
            ['id' => $candidatId, 'poste_id' => $posteId]);
        if (!$candidat) {
            throw new Exception("Candidat invalide.");
        }
        
        // 4. Vérifier que le poste appartient à la bonne élection
        if ($candidat['election_id'] != $electionId) {
            throw new Exception("Poste invalide pour cette élection.");
        }
        
        // Enregistrer le vote
        $voteData = [
            'user_id' => $currentUser['id'],
            'poste_id' => $posteId,
            'candidat_id' => $candidatId
        ];
        
        $db->insert('votes', $voteData);
        $db->getConnection()->commit();
        
        $message = "Votre vote a été enregistré avec succès !";
        $messageType = 'success';
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $message = "Erreur : " . $e->getMessage();
        $messageType = 'error';
    }
}

// Récupérer les votes de l'utilisateur pour afficher l'état
$userVotes = $db->fetchAll("SELECT v.poste_id, c.user_id, u.nom, u.prenom 
                           FROM votes v 
                           JOIN candidats c ON v.candidat_id = c.id 
                           JOIN users u ON c.user_id = u.id 
                           WHERE v.user_id = :user_id", 
    ['user_id' => $currentUser['id']]);

$votedPostes = array_column($userVotes, 'poste_id');
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .vote-container {
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

    .election-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, 0.1);
    }

    .election-header {
        background: linear-gradient(135deg, var(--primary), #1e3a8a);
        color: white;
        padding: 1.5rem;
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

    .poste-section {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
    }

    .poste-section:last-child {
        border-bottom: none;
    }

    .poste-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .poste-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 1rem;
    }

    .poste-status.voted {
        background: #d1fae5;
        color: #065f46;
    }

    .poste-status.not-voted {
        background: #fef3c7;
        color: #92400e;
    }

    .candidats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .candidat-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }

    .candidat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border-color: var(--primary);
    }

    .candidat-card.selected {
        border-color: var(--primary);
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .vote-form {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .vote-form.hidden {
        display: none;
    }

    .no-elections {
        text-align: center;
        padding: 3rem;
        color: var(--gray);
    }

    .no-elections i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .radio-input {
        display: none;
    }

    .radio-input:checked+.candidat-card {
        border-color: var(--primary);
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    @media (max-width: 768px) {
        .vote-container {
            padding: 1rem;
        }

        .candidats-grid {
            grid-template-columns: 1fr;
        }

        .candidat-header {
            flex-direction: column;
            text-align: center;
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

    <div class="vote-container">
        <div class="page-header">
            <h1><i class="fas fa-vote-yea"></i> Voter</h1>
            <p>Participez aux élections en cours et faites entendre votre voix</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if (empty($elections)): ?>
        <div class="no-elections">
            <i class="fas fa-calendar-times"></i>
            <h3>Aucune élection en cours</h3>
            <p>Il n'y a actuellement aucune élection en cours pour votre classe.</p>
        </div>
        <?php else: ?>
        <?php foreach ($elections as $election): ?>
        <div class="election-card">
            <div class="election-header">
                <h2><?php echo htmlspecialchars($election['titre']); ?></h2>
                <div class="election-meta">
                    <span>
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($election['nom_type']); ?>
                    </span>
                    <span>
                        <i class="fas fa-globe"></i>
                        <?php echo $election['portee'] === 'generale' ? 'Élection générale' : 'Élection spécifique à ' . htmlspecialchars($election['classe_cible']); ?>
                    </span>
                    <span>
                        <i class="fas fa-clock"></i>
                        Se termine le <?php echo date('d/m/Y à H:i', strtotime($election['date_fin'])); ?>
                    </span>
                </div>
            </div>

            <?php
                    // Récupérer les postes de cette élection
                    $postes = $db->fetchAll("SELECT * FROM postes WHERE election_id = :election_id ORDER BY nom_poste", 
                        ['election_id' => $election['id']]);
                    ?>

            <?php foreach ($postes as $poste): ?>
            <div class="poste-section">
                <div class="poste-title">
                    <i class="fas fa-user-tie"></i>
                    <?php echo htmlspecialchars($poste['nom_poste']); ?>
                    <?php if (in_array($poste['id'], $votedPostes)): ?>
                    <span class="poste-status voted">
                        <i class="fas fa-check"></i> Voté
                    </span>
                    <?php else: ?>
                    <span class="poste-status not-voted">
                        <i class="fas fa-clock"></i> En attente
                    </span>
                    <?php endif; ?>
                </div>

                <?php
                            // Récupérer les candidats valides pour ce poste
                            $candidats = $db->fetchAll("SELECT c.*, u.nom, u.prenom, u.photo as user_photo, u.classe
                                                       FROM candidats c 
                                                       JOIN users u ON c.user_id = u.id 
                                                       WHERE c.poste_id = :poste_id AND c.statut = 'valide'
                                                       ORDER BY u.nom, u.prenom", 
                                ['poste_id' => $poste['id']]);
                            ?>

                <?php if (empty($candidats)): ?>
                <p style="color: var(--gray); font-style: italic;">
                    <i class="fas fa-info-circle"></i> Aucun candidat validé pour ce poste.
                </p>
                <?php else: ?>
                <div class="candidats-grid">
                    <?php foreach ($candidats as $candidat): ?>
                    <?php
                    // Logique pour déterminer l'image du candidat
                    $imagePath = '../uploads/default_avatar.jpg'; // Image par défaut
                    
                    if (!empty($candidat['candidat_photo'])) {
                        $imagePath = '../uploads/' . $candidat['candidat_photo'];
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
                    <div class="candidat-card">
                        <div class="candidat-header">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                alt="Photo de <?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?>"
                                class="candidat-photo" onerror="this.src='../uploads/default_avatar.jpg';">
                            <div class="candidat-info">
                                <h3><?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?></h3>
                                <p><i class="fas fa-graduation-cap"></i>
                                    <?php echo htmlspecialchars($candidat['classe']); ?></p>
                            </div>
                        </div>

                        <div class="candidat-actions">
                            <?php if ($candidat['programme_pdf']): ?>
                            <a href="../uploads/<?php echo htmlspecialchars($candidat['programme_pdf']); ?>"
                                class="btn btn-secondary" target="_blank" title="Lire le programme">
                                <i class="fas fa-file-pdf"></i> Programme
                            </a>
                            <?php endif; ?>

                            <?php if (!in_array($poste['id'], $votedPostes)): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="vote" value="1">
                                <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                <input type="hidden" name="poste_id" value="<?php echo $poste['id']; ?>">
                                <input type="hidden" name="candidat_id" value="<?php echo $candidat['id']; ?>">
                                <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Êtes-vous sûr de vouloir voter pour <?php echo htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']); ?> ? Cette action est irréversible.');">
                                    <i class="fas fa-vote-yea"></i> Voter
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="btn btn-secondary" style="opacity: 0.6; cursor: not-allowed;">
                                <i class="fas fa-check"></i> Déjà voté
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (in_array($poste['id'], $votedPostes)): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: #d1fae5; border-radius: 8px; color: #065f46;">
                    <i class="fas fa-check-circle"></i>
                    Vous avez voté pour ce poste.
                    <?php
                                        $votedCandidat = array_filter($userVotes, function($vote) use ($poste) {
                                            return $vote['poste_id'] == $poste['id'];
                                        });
                                        if (!empty($votedCandidat)) {
                                            $candidat = reset($votedCandidat);
                                            echo "Votre choix : " . htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']);
                                        }
                                        ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include '../components/footer.php'; ?>
</body>

</html>
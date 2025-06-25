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

// Initialiser la base de données
$db = Database::getInstance();

// Traitement de la soumission de candidature
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'submit_candidature') {
        $posteId = (int)$_POST['poste_id'];
        $photo = $_FILES['photo'] ?? null;
        $programme = $_FILES['programme'] ?? null;
        
        // Validation
        if (empty($posteId)) {
            $error = "Veuillez sélectionner un poste.";
        } elseif (!$programme || $programme['error'] !== UPLOAD_ERR_OK) {
            $error = "Le programme électoral (PDF) est obligatoire.";
        } elseif ($programme['type'] !== 'application/pdf') {
            $error = "Le programme doit être au format PDF.";
        } elseif ($programme['size'] > 5 * 1024 * 1024) { // 5MB max
            $error = "Le programme ne doit pas dépasser 5MB.";
        } else {
            try {
                // Vérifier si l'utilisateur a déjà candidaté pour un poste de la même élection
                $posteInfo = $db->fetchOne("SELECT election_id FROM postes WHERE id = :poste_id", ['poste_id' => $posteId]);
                if ($posteInfo) {
                    $electionId = $posteInfo['election_id'];
                    $dejaCandidat = $db->fetchOne("
                        SELECT c.id FROM candidats c
                        JOIN postes p ON c.poste_id = p.id
                        WHERE c.user_id = :user_id AND p.election_id = :election_id
                    ", ['user_id' => $currentUser['id'], 'election_id' => $electionId]);
                    if ($dejaCandidat) {
                        $error = "Vous avez déjà candidaté à un poste pour cette élection.";
                    }
                }
                // Vérifier si l'utilisateur a déjà candidaté pour ce poste (sécurité supplémentaire)
                if (!$error && $db->exists('candidats', 'user_id = :user_id AND poste_id = :poste_id', ['user_id' => $currentUser['id'], 'poste_id' => $posteId])) {
                    $error = "Vous avez déjà candidaté pour ce poste.";
                }
                if (!$error) {
                    // Créer le dossier uploads s'il n'existe pas
                    $uploadDir = '../uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $candidatureData = [
                        'user_id' => $currentUser['id'],
                        'poste_id' => $posteId,
                        'statut' => 'en_attente'
                    ];
                    
                    // Traitement de la photo (facultative)
                    if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        if (!in_array($photo['type'], $allowedTypes)) {
                            $error = "La photo doit être au format JPG ou PNG.";
                        } elseif ($photo['size'] > 2 * 1024 * 1024) { // 2MB max
                            $error = "La photo ne doit pas dépasser 2MB.";
                        } else {
                            $photoExt = pathinfo($photo['name'], PATHINFO_EXTENSION);
                            $photoName = 'photo_' . $currentUser['id'] . '_' . time() . '.' . $photoExt;
                            $photoPath = $uploadDir . $photoName;
                            
                            if (move_uploaded_file($photo['tmp_name'], $photoPath)) {
                                $candidatureData['photo'] = 'uploads/' . $photoName;
                            }
                        }
                    }
                    
                    // Traitement du programme (obligatoire)
                    if (!$error) {
                        $programmeExt = pathinfo($programme['name'], PATHINFO_EXTENSION);
                        $programmeName = 'programme_' . $currentUser['id'] . '_' . time() . '.' . $programmeExt;
                        $programmePath = $uploadDir . $programmeName;
                        
                        if (move_uploaded_file($programme['tmp_name'], $programmePath)) {
                            $candidatureData['programme_pdf'] = 'uploads/' . $programmeName;
                            
                            // Insérer la candidature
                            $candidatureId = $db->insert('candidats', $candidatureData);
                            $success = "Votre candidature a été soumise avec succès ! Elle sera examinée par l'administration.";
                        } else {
                            $error = "Erreur lors du téléchargement du programme.";
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "Erreur lors de la soumission : " . $e->getMessage();
            }
        }
    }
}

// Récupérer les élections et postes disponibles
$elections = $db->fetchAll("
    SELECT e.*, te.nom_type 
    FROM elections e 
    JOIN types_elections te ON e.type_id = te.id 
    WHERE e.statut = 'en_cours' 
    AND (e.portee = 'generale' OR (e.portee = 'specifique' AND e.classe_cible = :classe))
    ORDER BY e.date_fin DESC
", ['classe' => $currentUser['classe']]);

// Récupérer les candidatures de l'utilisateur
$userCandidatures = $db->fetchAll("
    SELECT c.*, p.nom_poste, e.titre as election_titre, te.nom_type 
    FROM candidats c 
    JOIN postes p ON c.poste_id = p.id 
    JOIN elections e ON p.election_id = e.id 
    JOIN types_elections te ON e.type_id = te.id 
    WHERE c.user_id = :user_id 
    ORDER BY c.date_candidature DESC
", ['user_id' => $currentUser['id']]);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Candidatures - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/candidature.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .candidature-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .section {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .section h2 {
        color: var(--primary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .election-card {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        background: #f8fafc;
    }

    .election-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .election-title {
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--primary);
    }

    .election-type {
        background: var(--primary-light);
        color: var(--primary);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .postes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .poste-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .poste-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .poste-card.selected {
        border-color: var(--primary);
        background: var(--primary-light);
    }

    .poste-name {
        font-weight: bold;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .candidature-form {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 2rem;
        margin-top: 1rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text);
    }

    .form-group input[type="file"] {
        width: 100%;
        padding: 0.75rem;
        border: 2px dashed var(--border);
        border-radius: 6px;
        background: #f8fafc;
        cursor: pointer;
    }

    .form-group input[type="file"]:hover {
        border-color: var(--primary);
    }

    .file-info {
        font-size: 0.8rem;
        color: var(--gray);
        margin-top: 0.25rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: var(--gray);
        color: white;
    }

    .candidatures-list {
        display: grid;
        gap: 1rem;
    }

    .candidature-item {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .candidature-info h3 {
        margin: 0 0 0.5rem 0;
        color: var(--text);
    }

    .candidature-details {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-en_attente {
        background: #fef3c7;
        color: #92400e;
    }

    .status-valide {
        background: #d1fae5;
        color: #065f46;
    }

    .status-rejete {
        background: #fee2e2;
        color: #dc2626;
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
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

    @media (max-width: 768px) {
        .candidature-container {
            padding: 1rem;
        }

        .section {
            padding: 1rem;
        }

        .election-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .postes-grid {
            grid-template-columns: 1fr;
        }

        .candidature-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .candidature-form {
            padding: 1rem;
        }
    }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <div class="container">
        <main class="page-content">
            <div class="candidature-container">
                <div class="section">
                    <h2><i class="fas fa-file-alt"></i> Mes Candidatures</h2>

                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Candidatures existantes -->
                    <?php if (!empty($userCandidatures)): ?>
                    <h3>Candidatures déposées</h3>
                    <div class="candidatures-list">
                        <?php foreach ($userCandidatures as $candidature): ?>
                        <div class="candidature-item">
                            <div class="candidature-info">
                                <h3><?php echo htmlspecialchars($candidature['nom_poste']); ?></h3>
                                <div class="candidature-details">
                                    <strong>Élection :</strong>
                                    <?php echo htmlspecialchars($candidature['election_titre']); ?>
                                    (<?php echo htmlspecialchars($candidature['nom_type']); ?>)<br>
                                    <strong>Date de soumission :</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($candidature['date_candidature'])); ?>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo $candidature['statut']; ?>">
                                <?php 
                                switch($candidature['statut']) {
                                    case 'en_attente': echo 'En attente'; break;
                                    case 'valide': echo 'Validée'; break;
                                    case 'rejete': echo 'Rejetée'; break;
                                }
                                ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-elections">
                        <i class="fas fa-file-alt"></i>
                        <h3>Aucune candidature déposée</h3>
                        <p>Vous n'avez pas encore déposé de candidature.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Élections disponibles -->
                <?php if (!empty($elections)): ?>
                <div class="section">
                    <h2><i class="fas fa-vote-yea"></i> Élections disponibles</h2>

                    <?php foreach ($elections as $election): ?>
                    <div class="election-card">
                        <div class="election-header">
                            <div>
                                <div class="election-title"><?php echo htmlspecialchars($election['titre']); ?></div>
                                <div style="color: var(--gray); font-size: 0.9rem; margin-top: 0.25rem;">
                                    <?php echo $election['portee'] === 'generale' ? 'Élection générale' : 'Élection spécifique à ' . htmlspecialchars($election['classe_cible']); ?>
                                </div>
                            </div>
                            <span class="election-type"><?php echo htmlspecialchars($election['nom_type']); ?></span>
                        </div>

                        <div style="margin-bottom: 1rem; color: var(--gray); font-size: 0.9rem;">
                            <i class="fas fa-calendar"></i>
                            Du <?php echo date('d/m/Y H:i', strtotime($election['date_debut'])); ?>
                            au <?php echo date('d/m/Y H:i', strtotime($election['date_fin'])); ?>
                        </div>

                        <?php
                        // Récupérer les postes de cette élection
                        $postes = $db->fetchAll("
                            SELECT p.*, 
                                   CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END as already_candidated
                            FROM postes p 
                            LEFT JOIN candidats c ON p.id = c.poste_id AND c.user_id = :user_id
                            WHERE p.election_id = :election_id
                            ORDER BY p.nom_poste
                        ", ['election_id' => $election['id'], 'user_id' => $currentUser['id']]);
                        ?>

                        <?php if (!empty($postes)): ?>
                        <h4>Postes disponibles :</h4>
                        <div class="postes-grid">
                            <?php foreach ($postes as $poste): ?>
                            <div class="poste-card <?php echo $poste['already_candidated'] ? 'disabled' : ''; ?>"
                                onclick="<?php echo $poste['already_candidated'] ? '' : 'selectPoste(' . $poste['id'] . ', \'' . htmlspecialchars($poste['nom_poste']) . '\')'; ?>">
                                <div class="poste-name"><?php echo htmlspecialchars($poste['nom_poste']); ?></div>
                                <?php if ($poste['already_candidated']): ?>
                                <div style="color: var(--gray); font-size: 0.8rem;">
                                    <i class="fas fa-check"></i> Candidature déjà déposée
                                </div>
                                <?php else: ?>
                                <div style="color: var(--primary); font-size: 0.8rem;">
                                    <i class="fas fa-plus"></i> Cliquez pour candidater
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <!-- Formulaire de candidature -->
                    <div id="candidatureForm" class="candidature-form" style="display: none;">
                        <h3>Déposer une candidature</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="submit_candidature">
                            <input type="hidden" name="poste_id" id="selectedPosteId">

                            <div class="form-group">
                                <label for="poste_name">Poste sélectionné</label>
                                <input type="text" id="selectedPosteName" readonly style="background: #f8fafc;">
                            </div>

                            <div class="form-group">
                                <label for="photo">Photo (facultative)</label>
                                <input type="file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png">
                                <div class="file-info">
                                    <i class="fas fa-info-circle"></i> Formats acceptés : JPG, PNG. Taille max : 2MB
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="programme">Programme électoral (PDF obligatoire)</label>
                                <input type="file" id="programme" name="programme" accept="application/pdf" required>
                                <div class="file-info">
                                    <i class="fas fa-info-circle"></i> Format PDF uniquement. Taille max : 5MB
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Soumettre ma candidature
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="section">
                    <div class="no-elections">
                        <i class="fas fa-vote-yea"></i>
                        <h3>Aucune élection disponible</h3>
                        <p>Il n'y a actuellement aucune élection ouverte pour votre classe.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include '../components/footer.php'; ?>

    <script>
    function selectPoste(posteId, posteName) {
        // Mettre à jour les champs cachés
        document.getElementById('selectedPosteId').value = posteId;
        document.getElementById('selectedPosteName').value = posteName;

        // Afficher le formulaire
        document.getElementById('candidatureForm').style.display = 'block';

        // Scroll vers le formulaire
        document.getElementById('candidatureForm').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        // Mettre en surbrillance le poste sélectionné
        document.querySelectorAll('.poste-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.target.closest('.poste-card').classList.add('selected');
    }

    // Validation des fichiers
    document.getElementById('photo').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                alert('La photo ne doit pas dépasser 2MB.');
                this.value = '';
            }
        }
    });

    document.getElementById('programme').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.type !== 'application/pdf') {
                alert('Le programme doit être au format PDF.');
                this.value = '';
            } else if (file.size > 5 * 1024 * 1024) {
                alert('Le programme ne doit pas dépasser 5MB.');
                this.value = '';
            }
        }
    });
    </script>
</body>

</html>
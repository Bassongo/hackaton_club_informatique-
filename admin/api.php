<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth_check.php';

// Configuration des headers pour API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Debug: log des informations de session
error_log("Session data: " . print_r($_SESSION, true));

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("User not logged in");
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté',
        'data' => null,
        'debug' => [
            'session' => $_SESSION,
            'isLoggedIn' => isLoggedIn()
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    error_log("User not admin. Role: " . getCurrentUserRole());
    echo json_encode([
        'success' => false,
        'message' => 'Accès refusé : droits administrateur requis',
        'data' => null,
        'debug' => [
            'user_role' => getCurrentUserRole(),
            'isAdmin' => isAdmin()
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

error_log("User authenticated as admin: " . getCurrentUserName());

$db = Database::getInstance();
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Debug: log de la requête
    error_log("API Request: $method - $action");

    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
            break;
        default:
            throw new Exception('Méthode non autorisée');
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

// Fonctions pour gérer les requêtes GET
function handleGetRequest($action) {
    global $db, $response;
    
    switch ($action) {
        case 'statistics':
            // Statistiques générales
            $stats = [
                'total_users' => $db->count('users'),
                'total_elections' => $db->count('elections'),
                'total_candidatures' => $db->count('candidats'),
                'total_votes' => $db->count('votes'),
                'pending_candidatures' => $db->count('candidats', 'statut = "en_attente"'),
                'active_elections' => $db->count('elections', 'statut = "en_cours"')
            ];
            $response = ['success' => true, 'data' => $stats];
            break;

        case 'elections':
            // Liste des élections
            $elections = $db->fetchAll("
                SELECT e.*, te.nom_type 
                FROM elections e 
                JOIN types_elections te ON e.type_id = te.id 
                ORDER BY e.date_debut DESC
            ");
            $response = ['success' => true, 'data' => $elections];
            break;

        case 'users':
            // Liste des utilisateurs avec filtre par classe
            $classe = $_GET['classe'] ?? 'all'; // 'all', 'AS1', 'AS2', etc.
            
            $whereClause = '';
            $params = [];
            
            if ($classe !== 'all') {
                $whereClause = 'WHERE classe = :classe';
                $params['classe'] = $classe;
            }
            
            $users = $db->fetchAll("
                SELECT * FROM users 
                $whereClause
                ORDER BY classe, nom, prenom
            ", $params);
            $response = ['success' => true, 'data' => $users];
            break;

        case 'candidatures':
            // Candidatures avec filtre par statut
            $status = $_GET['status'] ?? 'all'; // 'all', 'en_attente', 'valide', 'rejete'
            
            $whereClause = '';
            $params = [];
            
            if ($status !== 'all') {
                $whereClause = 'WHERE c.statut = :status';
                $params['status'] = $status;
            }
            
            $candidatures = $db->fetchAll("
                SELECT c.*, u.nom, u.prenom, u.email, p.nom_poste, e.titre as election_titre
                FROM candidats c 
                JOIN users u ON c.user_id = u.id 
                JOIN postes p ON c.poste_id = p.id 
                JOIN elections e ON p.election_id = e.id 
                $whereClause
                ORDER BY c.date_candidature DESC
            ", $params);
            $response = ['success' => true, 'data' => $candidatures];
            break;

        case 'election_types':
            // Types d'élections
            $types = $db->fetchAll("SELECT * FROM types_elections ORDER BY nom_type");
            $response = ['success' => true, 'data' => $types];
            break;

        case 'postes':
            // Liste des postes
            $electionId = $_GET['election_id'] ?? null;
            if ($electionId) {
                $postes = $db->fetchAll("
                    SELECT p.*, e.titre as election_titre 
                    FROM postes p 
                    JOIN elections e ON p.election_id = e.id 
                    WHERE p.election_id = :election_id
                    ORDER BY p.nom_poste
                ", ['election_id' => $electionId]);
            } else {
                $postes = $db->fetchAll("
                    SELECT p.*, e.titre as election_titre 
                    FROM postes p 
                    JOIN elections e ON p.election_id = e.id 
                    ORDER BY e.titre, p.nom_poste
                ");
            }
            $response = ['success' => true, 'data' => $postes];
            break;

        case 'committee_members':
            // Membres d'un comité
            $electionId = $_GET['election_id'] ?? null;
            if ($electionId) {
                $members = $db->fetchAll("
                    SELECT c.*, u.nom, u.prenom, u.email 
                    FROM comites c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.election_id = :election_id
                ", ['election_id' => $electionId]);
                $response = ['success' => true, 'data' => $members];
            } else {
                throw new Exception('ID d\'élection requis');
            }
            break;

        case 'election_results':
            // Résultats d'une élection
            $electionId = $_GET['election_id'] ?? null;
            if ($electionId) {
                $results = $db->fetchAll("
                    SELECT p.nom_poste, c.id as candidat_id, u.nom, u.prenom, 
                           COUNT(v.id) as vote_count
                    FROM postes p 
                    LEFT JOIN candidats c ON p.id = c.poste_id AND c.statut = 'valide'
                    LEFT JOIN users u ON c.user_id = u.id 
                    LEFT JOIN votes v ON c.id = v.candidat_id 
                    WHERE p.election_id = :election_id 
                    GROUP BY p.id, c.id, u.nom, u.prenom
                    ORDER BY p.nom_poste, vote_count DESC
                ", ['election_id' => $electionId]);
                $response = ['success' => true, 'data' => $results];
            } else {
                throw new Exception('ID d\'élection requis');
            }
            break;

        case 'emails':
            // Liste des emails autorisés
            $emails = $db->fetchAll("SELECT * FROM gmail ORDER BY gmail");
            $response = ['success' => true, 'data' => $emails];
            break;

        case 'committees':
            // Liste des comités
            $electionId = $_GET['election_id'] ?? null;
            if ($electionId) {
                $committees = $db->fetchAll("
                    SELECT c.*, u.nom, u.prenom, u.email, e.titre as election_titre
                    FROM comites c 
                    JOIN users u ON c.user_id = u.id 
                    JOIN elections e ON c.election_id = e.id 
                    WHERE c.election_id = :election_id
                    ORDER BY u.nom, u.prenom
                ", ['election_id' => $electionId]);
            } else {
                $committees = $db->fetchAll("
                    SELECT c.*, u.nom, u.prenom, u.email, e.titre as election_titre
                    FROM comites c 
                    JOIN users u ON c.user_id = u.id 
                    JOIN elections e ON c.election_id = e.id 
                    ORDER BY e.titre, u.nom, u.prenom
                ");
            }
            $response = ['success' => true, 'data' => $committees];
            break;

        case 'delete_election_type':
            $typeId = (int)$_GET['type_id'];
            
            // Vérifier si le type est utilisé dans des élections
            if ($db->exists('elections', 'type_id = :type_id', ['type_id' => $typeId])) {
                throw new Exception('Ce type d\'élection est utilisé dans des élections et ne peut pas être supprimé');
            }
            
            $db->delete('types_elections', 'id = :id', ['id' => $typeId]);
            $response = ['success' => true, 'message' => 'Type d\'élection supprimé'];
            break;

        case 'election_participation':
            // Statistiques de participation par élection
            $electionId = $_GET['election_id'] ?? null;
            if ($electionId) {
                // Récupérer les informations de l'élection
                $election = $db->fetchOne("
                    SELECT e.*, te.nom_type 
                    FROM elections e 
                    JOIN types_elections te ON e.type_id = te.id 
                    WHERE e.id = :election_id
                ", ['election_id' => $electionId]);
                
                if (!$election) {
                    throw new Exception('Élection non trouvée');
                }
                
                // Calculer le nombre total d'électeurs éligibles
                $totalElecteurs = 0;
                if ($election['portee'] === 'generale') {
                    // Tous les étudiants
                    $totalElecteurs = $db->count('users', 'role = "etudiant"');
                } else {
                    // Seulement la classe spécifique
                    $totalElecteurs = $db->count('users', 'role = "etudiant" AND classe = :classe', 
                        ['classe' => $election['classe_cible']]);
                }
                
                // Calculer le nombre de votants par poste
                $participationParPoste = $db->fetchAll("
                    SELECT p.id as poste_id, p.nom_poste, 
                           COUNT(DISTINCT v.user_id) as votants,
                           $totalElecteurs as total_electeurs,
                           ROUND((COUNT(DISTINCT v.user_id) / $totalElecteurs) * 100, 2) as taux_participation
                    FROM postes p 
                    LEFT JOIN votes v ON p.id = v.poste_id 
                    WHERE p.election_id = :election_id 
                    GROUP BY p.id, p.nom_poste
                    ORDER BY p.nom_poste
                ", ['election_id' => $electionId]);
                
                // Calculer le taux global de participation
                $totalVotants = $db->fetchOne("
                    SELECT COUNT(DISTINCT v.user_id) as total_votants
                    FROM votes v 
                    JOIN postes p ON v.poste_id = p.id 
                    WHERE p.election_id = :election_id
                ", ['election_id' => $electionId]);
                
                $tauxGlobal = $totalElecteurs > 0 ? round(($totalVotants['total_votants'] / $totalElecteurs) * 100, 2) : 0;
                
                $response = [
                    'success' => true, 
                    'data' => [
                        'election' => $election,
                        'total_electeurs' => $totalElecteurs,
                        'total_votants' => $totalVotants['total_votants'],
                        'taux_global' => $tauxGlobal,
                        'participation_par_poste' => $participationParPoste
                    ]
                ];
            } else {
                throw new Exception('ID d\'élection requis');
            }
            break;

        case 'search_users':
            // Recherche d'utilisateurs
            $search = trim($_GET['search'] ?? '');
            $classe = $_GET['classe'] ?? 'all';
            
            if (empty($search)) {
                throw new Exception('Terme de recherche requis');
            }
            
            $whereConditions = [];
            $params = [];
            
            // Recherche dans nom, prénom, email avec des paramètres uniques
            $whereConditions[] = "(nom LIKE :search_nom OR prenom LIKE :search_prenom OR email LIKE :search_email OR CONCAT(prenom, ' ', nom) LIKE :search_full)";
            $params['search_nom'] = "%$search%";
            $params['search_prenom'] = "%$search%";
            $params['search_email'] = "%$search%";
            $params['search_full'] = "%$search%";
            
            // Filtre par classe si spécifié
            if ($classe !== 'all') {
                $whereConditions[] = "classe = :classe";
                $params['classe'] = $classe;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $users = $db->fetchAll("
                SELECT * FROM users 
                $whereClause
                ORDER BY classe, nom, prenom
                LIMIT 50
            ", $params);
            
            $response = [
                'success' => true, 
                'data' => $users,
                'search_term' => $search,
                'results_count' => count($users)
            ];
            break;

        case 'search_committee_users':
            // Recherche d'utilisateurs pour les comités
            $search = trim($_GET['search'] ?? '');
            
            if (empty($search)) {
                throw new Exception('Terme de recherche requis');
            }
            
            // Recherche dans nom, prénom, email, classe
            $users = $db->fetchAll("
                SELECT * FROM users 
                WHERE (nom LIKE :search_nom OR prenom LIKE :search_prenom OR email LIKE :search_email OR classe LIKE :search_classe OR CONCAT(prenom, ' ', nom) LIKE :search_full)
                AND role = 'etudiant'
                ORDER BY classe, nom, prenom
                LIMIT 50
            ", [
                'search_nom' => "%$search%",
                'search_prenom' => "%$search%",
                'search_email' => "%$search%",
                'search_classe' => "%$search%",
                'search_full' => "%$search%"
            ]);
            
            $response = [
                'success' => true, 
                'data' => $users,
                'search_term' => $search,
                'results_count' => count($users)
            ];
            break;

        default:
            throw new Exception('Action non reconnue');
    }
}

// Fonctions pour gérer les requêtes POST
function handlePostRequest($action) {
    global $db, $response;
    
    switch ($action) {
        case 'add_email':
            $email = trim($_POST['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format d\'email invalide');
            }
            
            if ($db->exists('gmail', 'email = :email', ['email' => $email])) {
                throw new Exception('Cet email est déjà autorisé');
            }

            $db->insert('gmail', ['email' => $email]);
            $response = ['success' => true, 'message' => 'Email ajouté avec succès'];
            break;

        case 'import_emails':
            if (!isset($_FILES['email_file']) || $_FILES['email_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Fichier manquant ou invalide');
            }
            $tmpFile = $_FILES['email_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['email_file']['name'], PATHINFO_EXTENSION));
            $emails = [];
            if ($ext === 'csv') {
                if (($handle = fopen($tmpFile, 'r')) !== false) {
                    while (($row = fgetcsv($handle)) !== false) {
                        if (isset($row[0])) { $emails[] = $row[0]; }
                    }
                    fclose($handle);
                }
            } elseif ($ext === 'xlsx') {
                $emails = parseEmailsFromXlsx($tmpFile);
            } else {
                throw new Exception('Format de fichier non pris en charge');
            }
            $inserted = 0;
            foreach ($emails as $mail) {
                $mail = trim($mail);
                if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) { continue; }
                if (!$db->exists('gmail', 'email = :email', ['email' => $mail])) {
                    $db->insert('gmail', ['email' => $mail]);
                    $inserted++;
                }
            }
            $response = ['success' => true, 'message' => "$inserted emails importés"];
            break;

        case 'add_election_type':
            $typeName = trim($_POST['type_name']);
            if (empty($typeName)) {
                throw new Exception('Nom du type requis');
            }
            
            if ($db->exists('types_elections', 'nom_type = :nom', ['nom' => $typeName])) {
                throw new Exception('Ce type d\'élection existe déjà');
            }
            
            $db->insert('types_elections', ['nom_type' => $typeName]);
            $response = ['success' => true, 'message' => 'Type d\'élection ajouté'];
            break;

        case 'update_election_type':
            $typeId = (int)$_POST['type_id'];
            $typeName = trim($_POST['type_name']);
            
            if (empty($typeName)) {
                throw new Exception('Nom du type requis');
            }
            
            // Vérifier si le nouveau nom existe déjà (sauf pour le type actuel)
            if ($db->exists('types_elections', 'nom_type = :nom AND id != :id', ['nom' => $typeName, 'id' => $typeId])) {
                throw new Exception('Ce nom de type d\'élection existe déjà');
            }
            
            $db->update('types_elections', ['nom_type' => $typeName], 'id = :id', ['id' => $typeId]);
            $response = ['success' => true, 'message' => 'Type d\'élection modifié'];
            break;

        case 'create_election':
            $electionData = [
                'titre' => trim($_POST['titre']),
                'type_id' => (int)$_POST['type_id'],
                'portee' => $_POST['portee'],
                'classe_cible' => $_POST['portee'] === 'specifique' ? $_POST['classe_cible'] : null,
                'date_debut' => $_POST['date_debut'],
                'date_fin' => $_POST['date_fin'],
                'statut' => 'en_attente'
            ];
            
            $electionId = $db->insert('elections', $electionData);
            $response = ['success' => true, 'message' => 'Élection créée', 'data' => ['id' => $electionId]];
            break;

        case 'add_poste':
            $posteData = [
                'nom_poste' => trim($_POST['nom_poste']),
                'election_id' => (int)$_POST['election_id']
            ];
            
            $posteId = $db->insert('postes', $posteData);
            $response = ['success' => true, 'message' => 'Poste ajouté', 'data' => ['id' => $posteId]];
            break;

        case 'update_poste':
            $posteId = (int)$_POST['poste_id'];
            $posteData = [
                'nom_poste' => trim($_POST['nom_poste']),
                'election_id' => (int)$_POST['election_id']
            ];
            
            // Vérifier si le poste existe
            if (!$db->exists('postes', 'id = :id', ['id' => $posteId])) {
                throw new Exception('Poste non trouvé');
            }
            
            $db->update('postes', $posteData, 'id = :id', ['id' => $posteId]);
            $response = ['success' => true, 'message' => 'Poste modifié'];
            break;

        case 'add_committee_member':
            $userId = (int)$_POST['user_id'];
            $electionId = (int)$_POST['election_id'];
            
            if ($db->exists('comites', 'user_id = :user_id AND election_id = :election_id', 
                ['user_id' => $userId, 'election_id' => $electionId])) {
                throw new Exception('Cet utilisateur est déjà membre du comité');
            }
            
            $db->insert('comites', ['user_id' => $userId, 'election_id' => $electionId]);
            $response = ['success' => true, 'message' => 'Membre ajouté au comité'];
            break;

        case 'update_candidature':
            $candidatId = (int)$_POST['candidat_id'];
            $status = $_POST['status'];
            
            if (!in_array($status, ['en_attente', 'valide', 'rejete'])) {
                throw new Exception('Statut invalide');
            }
            
            $db->update('candidats', ['statut' => $status], 'id = :id', ['id' => $candidatId]);
            $response = ['success' => true, 'message' => 'Statut mis à jour'];
            break;

        case 'update_election_status':
            $electionId = (int)$_POST['election_id'];
            $status = $_POST['status'];
            
            if (!in_array($status, ['en_attente', 'en_cours', 'terminee'])) {
                throw new Exception('Statut invalide');
            }
            
            $db->update('elections', ['statut' => $status], 'id = :id', ['id' => $electionId]);
            $response = ['success' => true, 'message' => 'Statut de l\'élection mis à jour'];
            break;

        default:
            throw new Exception('Action non reconnue');
    }
}

// Fonctions pour gérer les requêtes PUT
function handlePutRequest($action) {
    global $db, $response;
    
    // Récupérer les données PUT
    $putData = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_user':
            $userId = (int)$putData['user_id'];
            $updateData = [
                'nom' => trim($putData['nom']),
                'prenom' => trim($putData['prenom']),
                'email' => trim($putData['email']),
                'classe' => $putData['classe'],
                'role' => $putData['role']
            ];
            
            $db->update('users', $updateData, 'id = :id', ['id' => $userId]);
            $response = ['success' => true, 'message' => 'Utilisateur mis à jour'];
            break;

        case 'update_election':
            $electionId = (int)$putData['election_id'];
            $updateData = [
                'titre' => trim($putData['titre']),
                'date_debut' => $putData['date_debut'],
                'date_fin' => $putData['date_fin']
            ];
            
            $db->update('elections', $updateData, 'id = :id', ['id' => $electionId]);
            $response = ['success' => true, 'message' => 'Élection mise à jour'];
            break;

        default:
            throw new Exception('Action non reconnue');
    }
}

// Fonctions pour gérer les requêtes DELETE
function handleDeleteRequest($action) {
    global $db, $response;
    
    switch ($action) {
        case 'delete_email':
            $email = $_GET['email'] ?? '';
            if (empty($email)) {
                throw new Exception('Email requis');
            }
            
            $db->delete('gmail', 'email = :email', ['email' => $email]);
            $response = ['success' => true, 'message' => 'Email supprimé'];
            break;

        case 'delete_user':
            $userId = (int)$_GET['user_id'];
            $db->delete('users', 'id = :id', ['id' => $userId]);
            $response = ['success' => true, 'message' => 'Utilisateur supprimé'];
            break;

        case 'delete_election':
            $electionId = (int)$_GET['election_id'];
            $db->delete('elections', 'id = :id', ['id' => $electionId]);
            $response = ['success' => true, 'message' => 'Élection supprimée'];
            break;

        case 'delete_election_type':
            $typeId = (int)$_GET['type_id'];
            
            // Vérifier si le type est utilisé dans des élections
            if ($db->exists('elections', 'type_id = :type_id', ['type_id' => $typeId])) {
                throw new Exception('Ce type d\'élection est utilisé dans des élections et ne peut pas être supprimé');
            }
            
            $db->delete('types_elections', 'id = :id', ['id' => $typeId]);
            $response = ['success' => true, 'message' => 'Type d\'élection supprimé'];
            break;

        case 'delete_poste':
            $posteId = (int)$_GET['poste_id'];
            
            // Vérifier si le poste a des candidatures
            if ($db->exists('candidats', 'poste_id = :poste_id', ['poste_id' => $posteId])) {
                throw new Exception('Ce poste a des candidatures et ne peut pas être supprimé');
            }
            
            $db->delete('postes', 'id = :id', ['id' => $posteId]);
            $response = ['success' => true, 'message' => 'Poste supprimé'];
            break;

        case 'remove_committee_member':
            $userId = (int)$_GET['user_id'];
            $electionId = (int)$_GET['election_id'];
            $db->delete('comites', 'user_id = :user_id AND election_id = :election_id', 
                ['user_id' => $userId, 'election_id' => $electionId]);
            $response = ['success' => true, 'message' => 'Membre retiré du comité'];
            break;

        default:
            throw new Exception('Action non reconnue');
    }
}

function parseEmailsFromXlsx($filePath) {
    $result = [];
    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        $strings = [];
        if (($idx = $zip->locateName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($zip->getFromIndex($idx));
            foreach ($xml->si as $i => $si) {
                $strings[(int)$i] = (string)$si->t;
            }
        }
        if (($sheetIdx = $zip->locateName('xl/worksheets/sheet1.xml')) !== false) {
            $sheet = simplexml_load_string($zip->getFromIndex($sheetIdx));
            foreach ($sheet->sheetData->row as $row) {
                $cell = $row->c[0];
                if (!$cell) continue;
                $v = (string)$cell->v;
                if ((string)$cell['t'] === 's') {
                    $v = $strings[(int)$v] ?? $v;
                }
                $result[] = $v;
            }
        }
        $zip->close();
    }
    return $result;
}
?>
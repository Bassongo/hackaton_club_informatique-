<?php
/**
 * Configuration de la base de données E-election ENSAE
 * Basé sur la structure de e_ensae.sql
 */

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'vote_ensae');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe Database pour gérer la connexion et les opérations
 */
class Database {
    private $connection;
    private static $instance = null;

    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    /**
     * Obtenir l'instance unique de la base de données (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtenir la connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Exécuter une requête préparée
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erreur d'exécution de requête : " . $e->getMessage());
        }
    }

    /**
     * Récupérer une seule ligne
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Récupérer toutes les lignes
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insérer une nouvelle ligne et retourner l'ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }

    /**
     * Mettre à jour une ligne
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "$column = :$column";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Supprimer une ligne
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Compter les lignes
     */
    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $result = $this->fetchOne($sql, $params);
        return (int) $result['count'];
    }

    /**
     * Vérifier si une ligne existe
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
}

/**
 * Fonctions utilitaires pour les utilisateurs
 */
class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Authentifier un utilisateur
     */
    public function authenticate($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $user = $this->db->fetchOne($sql, ['email' => $email]);
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            return $user;
        }
        return false;
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function createUser($data) {
        // Vérifier si l'email est autorisé
        if (!$this->isEmailAllowed($data['email'])) {
            throw new Exception("Cet email n'est pas autorisé à s'inscrire.");
        }

        // Vérifier si l'email existe déjà
        if ($this->db->exists('users', 'email = :email', ['email' => $data['email']])) {
            throw new Exception("Cet email est déjà utilisé.");
        }

        // Hasher le mot de passe
        $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        
        return $this->db->insert('users', $data);
    }

    /**
     * Vérifier si un email est autorisé
     */
    public function isEmailAllowed($email) {
        return $this->db->exists('gmail', 'gmail = :email', ['email' => $email]);
    }

    /**
     * Obtenir un utilisateur par ID
     */
    public function getUserById($id) {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $id]);
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateUser($id, $data) {
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]);
    }
}

/**
 * Fonctions utilitaires pour les élections
 */
class ElectionManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtenir toutes les élections actives
     */
    public function getActiveElections() {
        $sql = "SELECT e.*, te.nom_type 
                FROM elections e 
                JOIN types_elections te ON e.type_id = te.id 
                WHERE e.statut = 'en_cours' 
                AND e.date_debut <= NOW() 
                AND e.date_fin >= NOW()";
        return $this->db->fetchAll($sql);
    }

    /**
     * Obtenir les élections par type
     */
    public function getElectionsByType($typeName) {
        $sql = "SELECT e.*, te.nom_type 
                FROM elections e 
                JOIN types_elections te ON e.type_id = te.id 
                WHERE te.nom_type = :type 
                ORDER BY e.date_debut DESC";
        return $this->db->fetchAll($sql, ['type' => $typeName]);
    }

    /**
     * Obtenir les postes d'une élection
     */
    public function getPostesByElection($electionId) {
        $sql = "SELECT * FROM postes WHERE election_id = :election_id";
        return $this->db->fetchAll($sql, ['election_id' => $electionId]);
    }

    /**
     * Créer une nouvelle élection
     */
    public function createElection($data) {
        return $this->db->insert('elections', $data);
    }

    /**
     * Mettre à jour le statut d'une élection
     */
    public function updateElectionStatus($electionId, $status) {
        return $this->db->update('elections', 
            ['statut' => $status], 
            'id = :id', 
            ['id' => $electionId]
        );
    }
}

/**
 * Fonctions utilitaires pour les candidats
 */
class CandidateManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Créer une nouvelle candidature
     */
    public function createCandidature($data) {
        // Vérifier si l'utilisateur n'a pas déjà candidaté pour ce poste
        if ($this->db->exists('candidats', 
            'user_id = :user_id AND poste_id = :poste_id', 
            ['user_id' => $data['user_id'], 'poste_id' => $data['poste_id']])) {
            throw new Exception("Vous avez déjà candidaté pour ce poste.");
        }

        return $this->db->insert('candidats', $data);
    }

    /**
     * Obtenir les candidats d'un poste
     */
    public function getCandidatesByPoste($posteId) {
        $sql = "SELECT c.*, u.nom, u.prenom, u.photo as user_photo, p.nom_poste 
                FROM candidats c 
                JOIN users u ON c.user_id = u.id 
                JOIN postes p ON c.poste_id = p.id 
                WHERE c.poste_id = :poste_id AND c.statut = 'valide'";
        return $this->db->fetchAll($sql, ['poste_id' => $posteId]);
    }

    /**
     * Obtenir les candidatures d'un utilisateur
     */
    public function getUserCandidatures($userId) {
        $sql = "SELECT c.*, p.nom_poste, e.titre as election_titre, te.nom_type 
                FROM candidats c 
                JOIN postes p ON c.poste_id = p.id 
                JOIN elections e ON p.election_id = e.id 
                JOIN types_elections te ON e.type_id = te.id 
                WHERE c.user_id = :user_id 
                ORDER BY c.date_candidature DESC";
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    /**
     * Valider ou rejeter une candidature
     */
    public function updateCandidatureStatus($candidatId, $status) {
        return $this->db->update('candidats', 
            ['statut' => $status], 
            'id = :id', 
            ['id' => $candidatId]
        );
    }
}

/**
 * Fonctions utilitaires pour les votes
 */
class VoteManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Enregistrer un vote
     */
    public function recordVote($userId, $posteId, $candidatId) {
        // Vérifier si l'utilisateur a déjà voté pour ce poste
        if ($this->db->exists('votes', 
            'user_id = :user_id AND poste_id = :poste_id', 
            ['user_id' => $userId, 'poste_id' => $posteId])) {
            throw new Exception("Vous avez déjà voté pour ce poste.");
        }

        $data = [
            'user_id' => $userId,
            'poste_id' => $posteId,
            'candidat_id' => $candidatId
        ];

        return $this->db->insert('votes', $data);
    }

    /**
     * Obtenir les résultats d'un poste
     */
    public function getResultsByPoste($posteId) {
        $sql = "SELECT c.id, c.user_id, u.nom, u.prenom, u.photo, 
                       COUNT(v.id) as vote_count 
                FROM candidats c 
                JOIN users u ON c.user_id = u.id 
                LEFT JOIN votes v ON c.id = v.candidat_id 
                WHERE c.poste_id = :poste_id AND c.statut = 'valide' 
                GROUP BY c.id, c.user_id, u.nom, u.prenom, u.photo 
                ORDER BY vote_count DESC";
        return $this->db->fetchAll($sql, ['poste_id' => $posteId]);
    }

    /**
     * Obtenir les statistiques de participation
     */
    public function getParticipationStats($electionId) {
        $sql = "SELECT 
                    p.id as poste_id,
                    p.nom_poste,
                    COUNT(DISTINCT v.user_id) as voters_count,
                    (SELECT COUNT(*) FROM users WHERE role = 'etudiant') as total_students
                FROM postes p 
                LEFT JOIN votes v ON p.id = v.poste_id 
                WHERE p.election_id = :election_id 
                GROUP BY p.id, p.nom_poste";
        return $this->db->fetchAll($sql, ['election_id' => $electionId]);
    }

    /**
     * Vérifier si un utilisateur a voté pour une élection
     */
    public function hasUserVotedInElection($userId, $electionId) {
        $sql = "SELECT COUNT(*) as count 
                FROM votes v 
                JOIN postes p ON v.poste_id = p.id 
                WHERE v.user_id = :user_id AND p.election_id = :election_id";
        $result = $this->db->fetchOne($sql, ['user_id' => $userId, 'election_id' => $electionId]);
        return (int) $result['count'] > 0;
    }
}

/**
 * Fonctions utilitaires pour les comités
 */
class CommitteeManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Ajouter un membre au comité
     */
    public function addCommitteeMember($userId, $electionId) {
        $data = [
            'user_id' => $userId,
            'election_id' => $electionId
        ];
        return $this->db->insert('comites', $data);
    }

    /**
     * Obtenir les membres d'un comité
     */
    public function getCommitteeMembers($electionId) {
        $sql = "SELECT c.*, u.nom, u.prenom, u.email 
                FROM comites c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.election_id = :election_id";
        return $this->db->fetchAll($sql, ['election_id' => $electionId]);
    }

    /**
     * Vérifier si un utilisateur est membre d'un comité
     */
    public function isCommitteeMember($userId, $electionId) {
        return $this->db->exists('comites', 
            'user_id = :user_id AND election_id = :election_id', 
            ['user_id' => $userId, 'election_id' => $electionId]
        );
    }
}

// Initialisation des gestionnaires
$userManager = new UserManager();
$electionManager = new ElectionManager();
$candidateManager = new CandidateManager();
$voteManager = new VoteManager();
$committeeManager = new CommitteeManager();

?>
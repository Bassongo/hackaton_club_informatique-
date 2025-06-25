<?php
session_start();
require_once '../config/database.php';
require_once '../auth_check.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Vote ENSAE</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .admin-container {
        display: grid;
        grid-template-columns: 250px 1fr;
        min-height: 100vh;
        background: #f8fafc;
    }

    .sidebar {
        background: var(--header);
        color: white;
        padding: 2rem 1rem;

    }

    .sidebar h2 {
        margin-bottom: 2rem;
        text-align: center;
        font-size: 1.2rem;
    }

    .nav-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-menu li {
        margin-bottom: 0.5rem;
    }

    .nav-menu a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: background var(--transition);
        cursor: pointer;
    }

    .nav-menu a:hover,
    .nav-menu a.active {
        background: rgba(255, 255, 255, 0.1);
    }

    .main-content {
        padding: 2rem;
        overflow-y: auto;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
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

    .section {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        display: none;
    }

    .section.active {
        display: block;
    }

    .section h3 {
        margin-bottom: 1rem;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }

    th {
        background: var(--primary-light);
        color: var(--primary);
        font-weight: 600;
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
        transition: all var(--transition);
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-secondary {
        background: var(--gray);
        color: white;
    }

    .btn-danger {
        background: #dc2626;
        color: white;
    }

    .btn-success {
        background: #16a34a;
        color: white;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
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

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 2rem;
        border-radius: 12px;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 1rem;
    }

    .close {
        float: right;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: none;
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

    .alert-info {
        background: #e0f2fe;
        color: #0277bd;
        border: 1px solid #81d4fa;
    }

    .loading {
        text-align: center;
        padding: 2rem;
        color: var(--gray);
    }

    .loading i {
        font-size: 2rem;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Styles de base pour desktop */
    .dashboard-header h1 {
        font-size: 2rem;
        margin: 0;
        color: var(--primary);
    }

    .dashboard-header p {
        font-size: 1rem;
        margin: 0;
        color: var(--gray);
    }

    .section h3 {
        font-size: 1.3rem;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .filter-buttons .btn {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }

    /* Overlay caché par défaut (desktop) */
    .sidebar-overlay {
        display: none;
    }

    @media (max-width: 768px) {
        .admin-container {
            grid-template-columns: 1fr;
        }

        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            height: 100vh;
            z-index: 1000;
            transition: left 0.3s ease;
            width: 280px;
            overflow-y: auto;
        }

        .sidebar.open {
            left: 0;
        }

        .main-content {
            padding: 1rem;
            margin-left: 0;
        }

        .dashboard-header {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        .dashboard-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }

        .dashboard-header p {
            font-size: 0.9rem;
            margin: 0;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }

        .section {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .section h3 {
            font-size: 1.1rem;
        }

        /* Barre de recherche mobile */
        #userSearchInput {
            min-width: auto !important;
            font-size: 16px !important;
            /* Évite le zoom sur iOS */
        }

        /* Filtres par classe mobile */
        .filter-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        .filter-buttons .btn {
            font-size: 0.8rem;
            padding: 0.5rem 0.25rem;
        }

        /* Actions rapides mobile */
        .quick-actions-grid {
            grid-template-columns: 1fr !important;
            gap: 0.75rem !important;
        }

        .quick-actions-grid .btn {
            padding: 0.75rem;
            font-size: 0.9rem;
        }

        /* Tables responsives */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 600px;
            font-size: 0.85rem;
        }

        th,
        td {
            padding: 0.5rem 0.25rem;
            white-space: nowrap;
        }

        /* Modals mobile */
        .modal-content {
            margin: 10% auto;
            padding: 1.5rem;
            max-width: 95%;
            max-height: 90vh;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            font-size: 16px;
            /* Évite le zoom sur iOS */
        }

        /* Boutons mobile */
        .btn {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            min-height: 44px;
            /* Taille minimale pour le touch */
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            min-height: 36px;
        }

        /* Graphiques mobile */
        #participationCharts {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }

        /* Navigation mobile */
        .nav-menu a {
            padding: 1rem;
            font-size: 0.9rem;
        }

        .nav-menu i {
            font-size: 1.1rem;
        }

        /* Overlay pour sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.open {
            display: block;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 0.75rem;
        }

        .dashboard-header h1 {
            font-size: 1.25rem;
        }

        .section {
            padding: 0.75rem;
        }

        .stat-card {
            padding: 0.75rem;
        }

        .stat-number {
            font-size: 1.25rem;
        }

        /* Filtres encore plus compacts */
        .filter-buttons {
            grid-template-columns: 1fr;
        }

        .filter-buttons .btn {
            font-size: 0.75rem;
            padding: 0.4rem 0.2rem;
        }

        /* Tables très compactes */
        table {
            min-width: 500px;
            font-size: 0.75rem;
        }

        th,
        td {
            padding: 0.4rem 0.2rem;
        }

        /* Modals plus petits */
        .modal-content {
            margin: 5% auto;
            padding: 1rem;
        }

        /* Boutons plus compacts */
        .btn {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
        }
    }

    /* Améliorations pour les très petits écrans */
    @media (max-width: 360px) {
        .dashboard-header {
            gap: 0.5rem;
        }

        .dashboard-header h1 {
            font-size: 1.1rem;
        }

        .section h3 {
            font-size: 1rem;
        }

        .btn {
            padding: 0.5rem 0.6rem;
            font-size: 0.8rem;
        }

        .modal-content {
            padding: 0.75rem;
        }
    }

    /* Améliorations tactiles et accessibilité */
    @media (hover: none) and (pointer: coarse) {
        .btn {
            min-height: 48px;
            min-width: 48px;
        }

        .nav-menu a {
            min-height: 48px;
            display: flex;
            align-items: center;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            min-height: 44px;
        }
    }

    /* Amélioration de la navigation au clavier */
    .nav-link:focus,
    .btn:focus {
        outline: 2px solid var(--primary);
        outline-offset: 2px;
    }

    /* Animation de chargement améliorée */
    .loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .loading i {
        margin-bottom: 1rem;
    }

    /* Amélioration des alertes sur mobile */
    .alert {
        margin: 0.5rem 0;
        padding: 0.75rem;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .alert {
            margin: 0.25rem 0;
            padding: 0.5rem;
            font-size: 0.85rem;
        }
    }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Overlay pour sidebar mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <h2><i class="fas fa-shield-alt"></i> Admin Dashboard</h2>
            <ul class="nav-menu">
                <li><a href="#dashboard" class="nav-link active" data-section="dashboard">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a></li>
                <li><a href="../pages/index.php" style="color: #ffd700; font-weight: bold;">
                        <i class="fas fa-home"></i> Voir le site
                    </a></li>
                <li><a href="#emails" class="nav-link" data-section="emails">
                        <i class="fas fa-envelope"></i> Importer les emails
                    </a></li>
                <li><a href="#users" class="nav-link" data-section="users">
                        <i class="fas fa-users"></i> Gérer les utilisateurs
                    </a></li>
                <li><a href="#election-types" class="nav-link" data-section="election-types">
                        <i class="fas fa-plus-circle"></i> Types d'élection
                    </a></li>
                <li><a href="#elections" class="nav-link" data-section="elections">
                        <i class="fas fa-vote-yea"></i> Créer une élection
                    </a></li>
                <li><a href="#manage-elections" class="nav-link" data-section="manage-elections">
                        <i class="fas fa-list"></i> Gérer les élections
                    </a></li>
                <li><a href="#postes" class="nav-link" data-section="postes">
                        <i class="fas fa-puzzle-piece"></i> Gérer les postes
                    </a></li>
                <li><a href="#committees" class="nav-link" data-section="committees">
                        <i class="fas fa-user-tie"></i> Nommer les comités
                    </a></li>
                <li><a href="#candidatures" class="nav-link" data-section="candidatures">
                        <i class="fas fa-file-alt"></i> Candidatures
                    </a></li>
                <li><a href="#statistics" class="nav-link" data-section="statistics">
                        <i class="fas fa-chart-bar"></i> Résultats & Stats
                    </a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="dashboard-header">
                <div>
                    <h1>E-election
                        ENSAE</h1>
                    <p>Bienvenue, <?php echo htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom']); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="../pages/index.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-home"></i> Voir le site
                    </a>
                    <button class="btn btn-primary" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i> Menu
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            <div id="alertContainer"></div>

            <!-- Dashboard Section -->
            <div id="dashboard" class="section active">
                <!-- Statistics -->
                <div class="stats-grid" id="statsGrid">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des statistiques...</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section">
                    <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
                    <div class="quick-actions-grid"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="../pages/index.php" class="btn btn-secondary"
                            style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fas fa-home"></i> Voir le site
                        </a>
                        <button class="btn btn-primary" onclick="showModal('addEmailModal')">
                            <i class="fas fa-envelope"></i> Ajouter un email
                        </button>
                        <button class="btn btn-secondary" onclick="showModal('importEmailsModal')">
                            <i class="fas fa-file-upload"></i> Importer des emails
                        </button>
                        <button class="btn btn-primary" onclick="showModal('addElectionTypeModal')">
                            <i class="fas fa-plus-circle"></i> Nouveau type d'élection
                        </button>
                        <button class="btn btn-primary" onclick="showSection('elections')">
                            <i class="fas fa-vote-yea"></i> Créer une élection
                        </button>
                        <button class="btn btn-primary" onclick="showModal('addPosteModal')">
                            <i class="fas fa-puzzle-piece"></i> Ajouter un poste
                        </button>
                    </div>
                </div>
            </div>

            <!-- Emails Section -->
            <div id="emails" class="section">
                <h3><i class="fas fa-envelope"></i> Gestion des emails autorisés</h3>
                <div class="table-responsive" id="emailsTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des emails...</p>
                    </div>
                </div>
            </div>

            <!-- Users Section -->
            <div id="users" class="section">
                <h3><i class="fas fa-users"></i> Gestion des utilisateurs</h3>

                <!-- Barre de recherche -->
                <div style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <input type="text" id="userSearchInput" placeholder="Rechercher par nom, prénom, email..."
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
                    </div>
                    <button class="btn btn-primary" onclick="searchUsers()">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <button class="btn btn-secondary" onclick="clearSearch()">
                        <i class="fas fa-times"></i> Effacer
                    </button>
                </div>

                <!-- Filtres par classe -->
                <div class="filter-buttons" style="margin-bottom: 1rem;">
                    <button class="btn btn-secondary" onclick="filterUsers('all')">Toutes les classes</button>
                    <button class="btn btn-secondary" onclick="filterUsers('AS1')">AS1</button>
                    <button class="btn btn-secondary" onclick="filterUsers('AS2')">AS2</button>
                    <button class="btn btn-secondary" onclick="filterUsers('AS3')">AS3</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISEP1')">ISEP1</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISEP2')">ISEP2</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISEP3')">ISEP3</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISE1 math')">ISE1 math</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISE1 eco')">ISE1 eco</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISE2')">ISE2</button>
                    <button class="btn btn-secondary" onclick="filterUsers('ISE3')">ISE3</button>
                </div>

                <div class="table-responsive" id="usersTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des utilisateurs...</p>
                    </div>
                </div>
            </div>

            <!-- Election Types Section -->
            <div id="election-types" class="section">
                <h3><i class="fas fa-plus-circle"></i> Types d'élections</h3>
                <div class="table-responsive" id="electionTypesTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des types d'élections...</p>
                    </div>
                </div>
            </div>

            <!-- Elections Section -->
            <div id="elections" class="section">
                <h3><i class="fas fa-vote-yea"></i> Créer une élection</h3>
                <form id="createElectionForm">
                    <div class="form-group">
                        <label for="titre">Titre de l'élection</label>
                        <input type="text" id="titre" name="titre" placeholder="Ex: Élection du BDE 2024" required>
                    </div>
                    <div class="form-group">
                        <label for="type_id">Type d'élection</label>
                        <select id="type_id" name="type_id" required>
                            <option value="">Sélectionner un type</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="portee">Portée</label>
                        <select id="portee" name="portee" required onchange="toggleClasseCible()">
                            <option value="generale">Générale</option>
                            <option value="specifique">Spécifique à une classe</option>
                        </select>
                    </div>
                    <div class="form-group" id="classeCibleGroup" style="display: none;">
                        <label for="classe_cible">Classe cible</label>
                        <select id="classe_cible" name="classe_cible">
                            <option value="AS1">AS1</option>
                            <option value="AS2">AS2</option>
                            <option value="AS3">AS3</option>
                            <option value="ISEP1">ISEP1</option>
                            <option value="ISEP2">ISEP2</option>
                            <option value="ISEP3">ISEP3</option>
                            <option value="ISE1 math">ISE1 math</option>
                            <option value="ISE1 eco">ISE1 eco</option>
                            <option value="ISE2">ISE2</option>
                            <option value="ISE3">ISE3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_debut">Date de début</label>
                        <input type="datetime-local" id="date_debut" name="date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date de fin</label>
                        <input type="datetime-local" id="date_fin" name="date_fin" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Créer l'élection
                    </button>
                </form>
            </div>

            <!-- Manage Elections Section -->
            <div id="manage-elections" class="section">
                <h3><i class="fas fa-list"></i> Gérer les élections</h3>
                <div class="table-responsive" id="electionsTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des élections...</p>
                    </div>
                </div>
            </div>

            <!-- Postes Section -->
            <div id="postes" class="section">
                <h3><i class="fas fa-puzzle-piece"></i> Gérer les postes</h3>
                <div class="table-responsive" id="postesTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des postes...</p>
                    </div>
                </div>
            </div>

            <!-- Committees Section -->
            <div id="committees" class="section">
                <h3><i class="fas fa-user-tie"></i> Nommer les comités</h3>

                <!-- Barre de recherche pour les comités -->
                <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <label for="committeeSearchInput"
                                style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                <i class="fas fa-search"></i> Rechercher des personnes à nommer
                            </label>
                            <input type="text" id="committeeSearchInput" placeholder="Nom, prénom, email ou classe..."
                                style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px;">
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="searchCommitteeUsers()">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                            <button class="btn btn-secondary" onclick="clearCommitteeSearch()">
                                <i class="fas fa-times"></i> Effacer
                            </button>
                        </div>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #6c757d;">
                        Recherchez par nom, prénom, début de nom/prénom, email ou classe
                    </div>
                </div>

                <div class="table-responsive" id="committeesTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des comités...</p>
                    </div>
                </div>
            </div>

            <!-- Candidatures Section -->
            <div id="candidatures" class="section">
                <h3><i class="fas fa-file-alt"></i> Gestion des candidatures</h3>
                <div style="margin-bottom: 1rem;">
                    <button class="btn btn-secondary" onclick="filterCandidatures('all')">Toutes</button>
                    <button class="btn btn-secondary" onclick="filterCandidatures('en_attente')">En attente</button>
                    <button class="btn btn-success" onclick="filterCandidatures('valide')">Validées</button>
                    <button class="btn btn-danger" onclick="filterCandidatures('rejete')">Rejetées</button>
                </div>
                <div class="table-responsive" id="candidaturesTable">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des candidatures...</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Section -->
            <div id="statistics" class="section">
                <h3><i class="fas fa-chart-bar"></i> Résultats & Statistiques</h3>

                <!-- Sélecteur d'élection pour les graphiques -->
                <div style="margin-bottom: 2rem;">
                    <div class="form-group">
                        <label for="electionSelect">Sélectionner une élection pour voir les graphiques de participation
                            :</label>
                        <select id="electionSelect" onchange="loadElectionParticipation()">
                            <option value="">-- Choisir une élection --</option>
                        </select>
                    </div>
                </div>

                <!-- Graphiques de participation -->
                <div id="participationCharts" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                        <div class="stat-card">
                            <h4>Taux de participation global</h4>
                            <canvas id="globalParticipationChart" width="250" height="120"></canvas>
                        </div>
                        <div class="stat-card">
                            <h4>Participation par poste</h4>
                            <canvas id="posteParticipationChart" width="250" height="120"></canvas>
                        </div>
                    </div>

                    <div class="stat-card">
                        <h4>Détails de participation</h4>
                        <div id="participationDetails"></div>
                    </div>
                </div>

                <!-- Statistiques générales -->
                <div id="statisticsContent">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Chargement des statistiques...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Add Email Modal -->
    <div id="addEmailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addEmailModal')">&times;</span>
            <h3><i class="fas fa-envelope"></i> Ajouter un email autorisé</h3>
            <form id="addEmailForm">
                <div class="form-group">
                    <label for="email">Email ENSAE</label>
                    <input type="email" id="email" name="email" placeholder="prenom.nom@ensae.sn" required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Import Emails Modal -->
    <div id="importEmailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('importEmailsModal')">&times;</span>
            <h3><i class="fas fa-file-upload"></i> Importer des emails</h3>
            <form id="importEmailsForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="email_file">Fichier Excel (.xlsx ou .csv)</label>
                    <input type="file" id="email_file" name="email_file" accept=".xlsx,.csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Importer</button>
            </form>
        </div>
    </div>

    <!-- Add Election Type Modal -->
    <div id="addElectionTypeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addElectionTypeModal')">&times;</span>
            <h3><i class="fas fa-plus-circle"></i> Ajouter un type d'élection</h3>
            <form id="addElectionTypeForm">
                <div class="form-group">
                    <label for="type_name">Nom du type</label>
                    <input type="text" id="type_name" name="type_name" placeholder="Ex: club, bde, classe" required>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>

    <!-- Edit Election Type Modal -->
    <div id="editElectionTypeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('editElectionTypeModal')">&times;</span>
            <h3><i class="fas fa-edit"></i> Modifier le type d'élection</h3>
            <form id="editElectionTypeForm">
                <input type="hidden" id="edit_type_id" name="type_id">
                <div class="form-group">
                    <label for="edit_type_name">Nom du type</label>
                    <input type="text" id="edit_type_name" name="type_name" placeholder="Ex: club, bde, classe"
                        required>
                </div>
                <button type="submit" class="btn btn-primary">Modifier</button>
            </form>
        </div>
    </div>

    <!-- Add Poste Modal -->
    <div id="addPosteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addPosteModal')">&times;</span>
            <h3><i class="fas fa-puzzle-piece"></i> Ajouter un poste</h3>
            <form id="addPosteForm">
                <div class="form-group">
                    <label for="nom_poste">Nom du poste</label>
                    <input type="text" id="nom_poste" name="nom_poste" placeholder="Ex: Président, Secrétaire" required>
                </div>
                <div class="form-group">
                    <label for="election_id">Élection</label>
                    <select id="election_id" name="election_id" required>
                        <option value="">Sélectionner une élection</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter le poste</button>
            </form>
        </div>
    </div>

    <!-- Edit Poste Modal -->
    <div id="editPosteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('editPosteModal')">&times;</span>
            <h3><i class="fas fa-edit"></i> Modifier le poste</h3>
            <form id="editPosteForm">
                <input type="hidden" id="edit_poste_id" name="poste_id">
                <div class="form-group">
                    <label for="edit_nom_poste">Nom du poste</label>
                    <input type="text" id="edit_nom_poste" name="nom_poste" placeholder="Ex: Président, Secrétaire"
                        required>
                </div>
                <div class="form-group">
                    <label for="edit_election_id">Élection</label>
                    <select id="edit_election_id" name="election_id" required>
                        <option value="">Sélectionner une élection</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Modifier le poste</button>
            </form>
        </div>
    </div>

    <!-- Add Committee Member Modal -->
    <div id="addCommitteeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addCommitteeModal')">&times;</span>
            <h3><i class="fas fa-user-plus"></i> Nommer un membre au comité</h3>
            <form id="addCommitteeForm">
                <div class="form-group">
                    <label for="committee_user_id">Utilisateur</label>
                    <select id="committee_user_id" name="user_id" required>
                        <option value="">Sélectionner un utilisateur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="committee_election_id">Élection</label>
                    <select id="committee_election_id" name="election_id" required>
                        <option value="">Sélectionner une élection</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Nommer le membre</button>
            </form>
        </div>
    </div>

    <script src="dashboard.js"></script>
    <script>
    // S'assurer que toutes les requêtes AJAX utilisent bien 'admin/api.php'
    // Si besoin, forcer l'URL de l'API ici :
    window.DASHBOARD_API_URL = 'api.php';
    </script>
</body>

</html>
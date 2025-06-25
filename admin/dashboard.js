// Variables globales
let currentSection = 'dashboard';
let elections = [];
let electionTypes = [];

// Variables globales pour la recherche
let currentSearchTerm = '';
let currentFilterClasse = 'all';

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    setupEventListeners();
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = e.target.getAttribute('data-section');
            if (section) {
                showSection(section);
            }
        });
    });

    // Formulaires
    document.getElementById('addEmailForm').addEventListener('submit', handleAddEmail);
    const importForm = document.getElementById('importEmailsForm');
    if (importForm) {
        importForm.addEventListener('submit', handleImportEmails);
    }
    document.getElementById('addElectionTypeForm').addEventListener('submit', handleAddElectionType);
    document.getElementById('editElectionTypeForm').addEventListener('submit', handleEditElectionType);
    document.getElementById('createElectionForm').addEventListener('submit', handleCreateElection);
    document.getElementById('addPosteForm').addEventListener('submit', handleAddPoste);
    document.getElementById('editPosteForm').addEventListener('submit', handleEditPoste);
    document.getElementById('addCommitteeForm').addEventListener('submit', handleAddCommitteeMember);

    // Ajouter l'écouteur d'événement pour la recherche en temps réel
    const searchInput = document.getElementById('userSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });
        
        // Recherche en temps réel (avec délai)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2) {
                    searchUsers();
                } else if (this.value.length === 0) {
                    clearSearch();
                }
            }, 500);
        });
    }
}

// Fonctions de navigation
function showSection(sectionId) {
    // Fermer la sidebar sur mobile
    closeSidebarOnMobile();
    
    // Masquer toutes les sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Afficher la section demandée
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Mettre à jour la navigation active
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`[data-section="${sectionId}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }

    // Charger les données de la section
    loadSectionData(sectionId);
    currentSection = sectionId;
}

// Charger les données selon la section
function loadSectionData(sectionName) {
    switch(sectionName) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'emails':
            loadEmails();
            break;
        case 'users':
            loadUsers();
            break;
        case 'election-types':
            loadElectionTypes();
            break;
        case 'elections':
            loadElectionTypesForForm();
            break;
        case 'manage-elections':
            loadElections();
            break;
        case 'postes':
            loadPostes();
            break;
        case 'committees':
            loadCommittees();
            break;
        case 'candidatures':
            loadCandidatures();
            break;
        case 'statistics':
            loadStatistics();
            break;
    }
}

// Helper pour construire l'URL
function apiUrl(query) {
    return window.DASHBOARD_API_URL + (query ? (window.DASHBOARD_API_URL.includes('?') ? '&' : '?') + query : '');
}

// Fonctions de chargement des données
async function loadDashboard() {
    console.log('Chargement du dashboard...');
    try {
        const response = await fetch(apiUrl('action=statistics'));
        console.log('Réponse reçue:', response);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('Données reçues:', data);
        if (data.success) {
            displayStatistics(data.data);
        } else {
            showAlert('Erreur lors du chargement des statistiques: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Erreur lors du chargement du dashboard:', error);
        showAlert('Erreur de connexion: ' + error.message, 'error');
    }
}

async function loadEmails() {
    try {
        const response = await fetch(apiUrl('action=emails'));
        const data = await response.json();
        if (data.success) {
            displayEmails(data.data);
        } else {
            showAlert('Erreur lors du chargement des emails', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadUsers(classe = 'all') {
    try {
        const response = await fetch(apiUrl(`action=users&classe=${classe}`));
        const data = await response.json();
        if (data.success) {
            displayUsers(data.data, classe);
        } else {
            showAlert('Erreur lors du chargement des utilisateurs', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadElectionTypes() {
    try {
        const response = await fetch(apiUrl('action=election_types'));
        const data = await response.json();
        if (data.success) {
            electionTypes = data.data;
            displayElectionTypes(data.data);
        } else {
            showAlert('Erreur lors du chargement des types d\'élections', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadElectionTypesForForm() {
    try {
        const response = await fetch(apiUrl('action=election_types'));
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('type_id');
            select.innerHTML = '<option value="">Sélectionner un type</option>';
            data.data.forEach(type => {
                select.innerHTML += `<option value="${type.id}">${escapeHtml(type.nom_type)}</option>`;
            });
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadElections() {
    try {
        const response = await fetch(apiUrl('action=elections'));
        const data = await response.json();
        
        if (data.success) {
            elections = data.data;
            displayElections(data.data);
            
            // Mettre à jour le select des élections pour les postes
            const select = document.getElementById('election_id');
            if (select) {
                select.innerHTML = '<option value="">Sélectionner une élection</option>';
                data.data.forEach(election => {
                    select.innerHTML += `<option value="${election.id}">${escapeHtml(election.titre)}</option>`;
                });
            }
        } else {
            showAlert('Erreur lors du chargement des élections', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadPostes() {
    try {
        const response = await fetch(apiUrl('action=postes'));
        const data = await response.json();
        
        if (data.success) {
            displayPostes(data.data);
        } else {
            showAlert('Erreur lors du chargement des postes', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadCommittees() {
    try {
        const response = await fetch(apiUrl('action=committees'));
        const data = await response.json();
        
        if (data.success) {
            displayCommittees(data.data);
        } else {
            showAlert('Erreur lors du chargement des comités', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadCandidatures(status = 'all') {
    try {
        const response = await fetch(apiUrl(`action=candidatures&status=${status}`));
        const data = await response.json();
        if (data.success) {
            displayCandidatures(data.data, status);
        } else {
            showAlert('Erreur lors du chargement des candidatures', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadStatistics() {
    try {
        const response = await fetch(apiUrl('action=statistics'));
        const data = await response.json();
        if (data.success) {
            displayDetailedStatistics(data.data);
            loadElectionsForParticipation();
        } else {
            showAlert('Erreur lors du chargement des statistiques', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function loadElectionsForParticipation() {
    try {
        const response = await fetch(apiUrl('action=elections'));
        const data = await response.json();
        if (data.success) {
            const select = document.getElementById('electionSelect');
            select.innerHTML = '<option value="">-- Choisir une élection --</option>';
            
            data.data.forEach(election => {
                const option = document.createElement('option');
                option.value = election.id;
                option.textContent = `${election.titre} (${election.portee === 'generale' ? 'Générale' : election.classe_cible})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des élections:', error);
    }
}

async function loadElectionParticipation() {
    const electionId = document.getElementById('electionSelect').value;
    if (!electionId) {
        document.getElementById('participationCharts').style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch(apiUrl(`action=election_participation&election_id=${electionId}`));
        const data = await response.json();
        if (data.success) {
            displayParticipationCharts(data.data);
        } else {
            showAlert('Erreur lors du chargement des statistiques de participation', 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

// Fonctions d'affichage
function displayStatistics(stats) {
    const statsGrid = document.getElementById('statsGrid');
    statsGrid.innerHTML = `
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-number">${stats.total_users}</div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-vote-yea"></i>
            <div class="stat-number">${stats.total_elections}</div>
            <div class="stat-label">Élections</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-file-alt"></i>
            <div class="stat-number">${stats.total_candidatures}</div>
            <div class="stat-label">Candidatures</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-bar"></i>
            <div class="stat-number">${stats.total_votes}</div>
            <div class="stat-label">Votes</div>
        </div>
    `;
}

function displayEmails(emails) {
    const table = document.getElementById('emailsTable');
    if (!emails || emails.length === 0) {
        table.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <p>Aucun email autorisé trouvé</p>
                <button class="btn btn-primary" onclick="showModal('addEmailModal')">
                    <i class="fas fa-plus"></i> Ajouter le premier email
                </button>
                <button class="btn btn-secondary" onclick="showModal('importEmailsModal')" style="margin-left:0.5rem;">
                    <i class="fas fa-file-upload"></i> Importer un fichier
                </button>
            </div>
        `;
        return;
    }

    let html = `
        <div style="margin-bottom: 1rem;">
            <button class="btn btn-primary" onclick="showModal('addEmailModal')">
                <i class="fas fa-plus"></i> Ajouter un email
            </button>
            <button class="btn btn-secondary" onclick="showModal('importEmailsModal')" style="margin-left:0.5rem;">
                <i class="fas fa-file-upload"></i> Importer un fichier
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    emails.forEach(email => {
        html += `
            <tr>
                <td>${escapeHtml(email.gmail)}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteEmail('${email.gmail}')">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function displayUsers(users, classe) {
    const table = document.getElementById('usersTable');
    if (!users || users.length === 0) {
        const classeText = classe === 'all' ? '' : ` de la classe ${classe}`;
        table.innerHTML = `<p>Aucun utilisateur trouvé${classeText}</p>`;
        return;
    }

    const classeText = classe === 'all' ? 'toutes les classes' : `la classe ${classe}`;
    const countText = `${users.length} utilisateur${users.length > 1 ? 's' : ''}`;

    let html = `
        <div style="margin-bottom: 1rem; padding: 0.5rem; background: #f0f8ff; border-radius: 5px;">
            <strong>${countText} trouvé${users.length > 1 ? 's' : ''} dans ${classeText}</strong>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Classe</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    users.forEach(user => {
        html += `
            <tr>
                <td>${escapeHtml(user.nom)}</td>
                <td>${escapeHtml(user.prenom)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td><span style="font-weight: bold; color: #2563eb;">${escapeHtml(user.classe)}</span></td>
                <td>
                    <span class="status-badge status-${user.role}">
                        ${formatStatus(user.role)}
                    </span>
                </td>
                <td>${formatDate(user.date_inscription)}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function displayElectionTypes(types) {
    const table = document.getElementById('electionTypesTable');
    if (!types || types.length === 0) {
        table.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <p>Aucun type d'élection trouvé</p>
                <button class="btn btn-primary" onclick="showModal('addElectionTypeModal')">
                    <i class="fas fa-plus"></i> Ajouter le premier type
                </button>
            </div>
        `;
        return;
    }

    let html = `
        <div style="margin-bottom: 1rem;">
            <button class="btn btn-primary" onclick="showModal('addElectionTypeModal')">
                <i class="fas fa-plus"></i> Ajouter un type d'élection
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom du type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    types.forEach(type => {
        html += `
            <tr>
                <td>${escapeHtml(type.nom_type)}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editElectionType(${type.id}, '${escapeHtml(type.nom_type)}')">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteElectionType(${type.id})">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function displayElections(elections) {
    const table = document.getElementById('electionsTable');
    if (!elections || elections.length === 0) {
        table.innerHTML = '<p>Aucune élection trouvée</p>';
        return;
    }

    let html = `
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Type</th>
                    <th>Portée</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    elections.forEach(election => {
        html += `
            <tr>
                <td>${escapeHtml(election.titre)}</td>
                <td>${escapeHtml(election.nom_type)}</td>
                <td>${escapeHtml(election.portee)}</td>
                <td>${formatDate(election.date_debut)}</td>
                <td>${formatDate(election.date_fin)}</td>
                <td>
                    <span class="status-badge status-${election.statut}">
                        ${formatStatus(election.statut)}
                    </span>
                </td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editElection(${election.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteElection(${election.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="updateElectionStatus(${election.id}, 'en_cours')">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="btn btn-success btn-sm" onclick="updateElectionStatus(${election.id}, 'terminee')">
                        <i class="fas fa-stop"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function displayPostes(postes) {
    const table = document.getElementById('postesTable');
    if (!postes || postes.length === 0) {
        table.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <p>Aucun poste trouvé</p>
                <button class="btn btn-primary" onclick="showModal('addPosteModal')">
                    <i class="fas fa-plus"></i> Ajouter le premier poste
                </button>
            </div>
        `;
        return;
    }

    let html = `
        <div style="margin-bottom: 1rem;">
            <button class="btn btn-primary" onclick="showModal('addPosteModal')">
                <i class="fas fa-plus"></i> Ajouter un poste
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom du poste</th>
                    <th>Élection</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    postes.forEach(poste => {
        html += `
            <tr>
                <td>${escapeHtml(poste.nom_poste)}</td>
                <td>${escapeHtml(poste.election_titre)}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editPoste(${poste.id}, '${escapeHtml(poste.nom_poste)}', ${poste.election_id})">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deletePoste(${poste.id})">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function displayCommittees(committees) {
    const table = document.getElementById('committeesTable');
    if (!committees || committees.length === 0) {
        table.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <p>Aucun comité trouvé</p>
                <button class="btn btn-primary" onclick="showModal('addCommitteeModal')">
                    <i class="fas fa-plus"></i> Nommer le premier membre
                </button>
            </div>
        `;
        return;
    }

    let html = `
        <div style="margin-bottom: 1rem;">
            <button class="btn btn-primary" onclick="showModal('addCommitteeModal')">
                <i class="fas fa-plus"></i> Nommer un membre
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Membre</th>
                    <th>Email</th>
                    <th>Élection</th>
                    <th>Date de nomination</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    committees.forEach(committee => {
        html += `
            <tr>
                <td>${escapeHtml(committee.prenom + ' ' + committee.nom)}</td>
                <td>${escapeHtml(committee.email)}</td>
                <td>${escapeHtml(committee.election_titre)}</td>
                <td>${formatDate(committee.date_nomination)}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeCommitteeMember(${committee.user_id}, ${committee.election_id})">
                        <i class="fas fa-user-minus"></i> Retirer
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function displayCandidatures(candidatures, status) {
    const table = document.getElementById('candidaturesTable');
    if (!candidatures || candidatures.length === 0) {
        const statusText = status === 'all' ? '' : ` ${status === 'en_attente' ? 'en attente' : status === 'valide' ? 'validées' : 'rejetées'}`;
        table.innerHTML = `<p>Aucune candidature${statusText}</p>`;
        return;
    }

    let html = `
        <table>
            <thead>
                <tr>
                    <th>Candidat</th>
                    <th>Email</th>
                    <th>Poste</th>
                    <th>Élection</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    candidatures.forEach(candidature => {
        const statusBadge = getStatusBadge(candidature.statut);
        const actions = getCandidatureActions(candidature);
        
        html += `
            <tr>
                <td>${escapeHtml(candidature.prenom + ' ' + candidature.nom)}</td>
                <td>${escapeHtml(candidature.email)}</td>
                <td>${escapeHtml(candidature.nom_poste)}</td>
                <td>${escapeHtml(candidature.election_titre)}</td>
                <td>${statusBadge}</td>
                <td>${formatDate(candidature.date_candidature)}</td>
                <td>${actions}</td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    table.innerHTML = html;
}

function getStatusBadge(status) {
    const statusMap = {
        'en_attente': '<span class="status-badge status-en_attente">En attente</span>',
        'valide': '<span class="status-badge status-en_cours">Validée</span>',
        'rejete': '<span class="status-badge status-terminee">Rejetée</span>'
    };
    return statusMap[status] || status;
}

function getCandidatureActions(candidature) {
    if (candidature.statut === 'en_attente') {
        return `
            <button class="btn btn-success btn-sm" onclick="updateCandidature(${candidature.id}, 'valide')">
                <i class="fas fa-check"></i> Valider
            </button>
            <button class="btn btn-danger btn-sm" onclick="updateCandidature(${candidature.id}, 'rejete')">
                <i class="fas fa-times"></i> Rejeter
            </button>
        `;
    } else if (candidature.statut === 'valide') {
        return `
            <button class="btn btn-danger btn-sm" onclick="updateCandidature(${candidature.id}, 'rejete')">
                <i class="fas fa-times"></i> Rejeter
            </button>
        `;
    } else if (candidature.statut === 'rejete') {
        return `
            <button class="btn btn-success btn-sm" onclick="updateCandidature(${candidature.id}, 'valide')">
                <i class="fas fa-check"></i> Valider
            </button>
        `;
    }
    return '';
}

function displayDetailedStatistics(stats) {
    const container = document.getElementById('statisticsContent');
    container.innerHTML = `
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number">${stats.total_users}</div>
                <div class="stat-label">Utilisateurs totaux</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-vote-yea"></i>
                <div class="stat-number">${stats.total_elections}</div>
                <div class="stat-label">Élections totales</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-alt"></i>
                <div class="stat-number">${stats.total_candidatures}</div>
                <div class="stat-label">Candidatures totales</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-bar"></i>
                <div class="stat-number">${stats.total_votes}</div>
                <div class="stat-label">Votes totaux</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-number">${stats.pending_candidatures}</div>
                <div class="stat-label">Candidatures en attente</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-play-circle"></i>
                <div class="stat-number">${stats.active_elections}</div>
                <div class="stat-label">Élections actives</div>
            </div>
        </div>
    `;
}

function displayParticipationCharts(participationData) {
    const { election, total_electeurs, total_votants, taux_global, participation_par_poste } = participationData;
    
    // Afficher la section des graphiques
    document.getElementById('participationCharts').style.display = 'block';
    
    // Graphique de participation globale (donut chart)
    const globalCtx = document.getElementById('globalParticipationChart').getContext('2d');
    new Chart(globalCtx, {
        type: 'doughnut',
        data: {
            labels: ['A voté', 'N\'a pas voté'],
            datasets: [{
                data: [total_votants, total_electeurs - total_votants],
                backgroundColor: ['#16a34a', '#e5e7eb'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: `Taux de participation: ${taux_global}%`
                }
            }
        }
    });
    
    // Graphique de participation par poste (bar chart)
    const posteCtx = document.getElementById('posteParticipationChart').getContext('2d');
    new Chart(posteCtx, {
        type: 'bar',
        data: {
            labels: participation_par_poste.map(p => p.nom_poste),
            datasets: [{
                label: 'Taux de participation (%)',
                data: participation_par_poste.map(p => p.taux_participation),
                backgroundColor: '#2563eb',
                borderColor: '#1d4ed8',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
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
    
    // Détails de participation
    const detailsHtml = `
        <div style="margin-bottom: 1rem;">
            <h5>Informations sur l'élection</h5>
            <p><strong>Titre:</strong> ${election.titre}</p>
            <p><strong>Type:</strong> ${election.nom_type}</p>
            <p><strong>Portée:</strong> ${election.portee === 'generale' ? 'Générale (tous les étudiants)' : `Spécifique (${election.classe_cible})`}</p>
            <p><strong>Statut:</strong> ${formatStatus(election.statut)}</p>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <h5>Statistiques de participation</h5>
            <p><strong>Total d'électeurs éligibles:</strong> ${total_electeurs}</p>
            <p><strong>Total de votants:</strong> ${total_votants}</p>
            <p><strong>Taux de participation global:</strong> <span style="color: #16a34a; font-weight: bold;">${taux_global}%</span></p>
        </div>
        
        <div>
            <h5>Participation par poste</h5>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f3f4f6;">
                        <th style="padding: 0.5rem; text-align: left; border: 1px solid #d1d5db;">Poste</th>
                        <th style="padding: 0.5rem; text-align: center; border: 1px solid #d1d5db;">Votants</th>
                        <th style="padding: 0.5rem; text-align: center; border: 1px solid #d1d5db;">Taux</th>
                    </tr>
                </thead>
                <tbody>
                    ${participation_par_poste.map(p => `
                        <tr>
                            <td style="padding: 0.5rem; border: 1px solid #d1d5db;">${p.nom_poste}</td>
                            <td style="padding: 0.5rem; text-align: center; border: 1px solid #d1d5db;">${p.votants}/${p.total_electeurs}</td>
                            <td style="padding: 0.5rem; text-align: center; border: 1px solid #d1d5db; color: ${p.taux_participation > 50 ? '#16a34a' : p.taux_participation > 25 ? '#ca8a04' : '#dc2626'}; font-weight: bold;">${p.taux_participation}%</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('participationDetails').innerHTML = detailsHtml;
}

// Gestionnaires de formulaires
async function handleAddEmail(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_email');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('addEmailModal');
            e.target.reset();
            if (currentSection === 'emails') {
                loadEmails();
            }
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function handleImportEmails(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'import_emails');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('importEmailsModal');
            e.target.reset();
            if (currentSection === 'emails') {
                loadEmails();
            }
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function handleAddElectionType(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_election_type');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('addElectionTypeModal');
            e.target.reset();
            loadElectionTypes();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function handleEditElectionType(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'update_election_type');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('editElectionTypeModal');
            e.target.reset();
            loadElectionTypes();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function handleCreateElection(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'create_election');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            e.target.reset();
            loadElections();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function handleAddPoste(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_poste');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('addPosteModal');
            e.target.reset();
            loadPostes();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function handleEditPoste(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'update_poste');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('editPosteModal');
            e.target.reset();
            loadPostes();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

// Actions asynchrones
async function updateCandidature(candidatId, status) {
    try {
        const formData = new FormData();
        formData.append('action', 'update_candidature');
        formData.append('candidat_id', candidatId);
        formData.append('status', status);
        
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            // Recharger les candidatures avec le filtre actuel
            const activeFilter = document.querySelector('[onclick^="filterCandidatures"].btn-primary');
            const currentStatus = activeFilter ? activeFilter.getAttribute('onclick').match(/'([^']+)'/)[1] : 'all';
            loadCandidatures(currentStatus);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function deleteElection(electionId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette élection ?')) {
        return;
    }

    try {
        const response = await fetch(apiUrl(`action=delete_election&election_id=${electionId}`), {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            loadElections();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function updateElectionStatus(electionId, status) {
    const formData = new FormData();
    formData.append('action', 'update_election_status');
    formData.append('election_id', electionId);
    formData.append('status', status);

    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            loadElections();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function deleteEmail(email) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet email ?')) {
        return;
    }

    try {
        const response = await fetch(apiUrl(`action=delete_email&email=${encodeURIComponent(email)}`), {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            loadEmails();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function deleteUser(userId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        return;
    }
    
    try {
        const response = await fetch(apiUrl(`action=delete_user&user_id=${userId}`), {
            method: 'DELETE'
        });
        
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            // Recharger les utilisateurs avec le filtre actuel
            const activeFilter = document.querySelector('[onclick^="filterUsers"].btn-primary');
            const currentClasse = activeFilter ? activeFilter.getAttribute('onclick').match(/'([^']+)'/)[1] : 'all';
            loadUsers(currentClasse);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function deleteElectionType(typeId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce type d\'élection ?')) {
        return;
    }

    try {
        const response = await fetch(apiUrl(`action=delete_election_type&type_id=${typeId}`), {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            loadElectionTypes();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function deletePoste(posteId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce poste ?')) {
        return;
    }

    try {
        const response = await fetch(apiUrl(`action=delete_poste&poste_id=${posteId}`), {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            loadPostes();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

async function removeCommitteeMember(userId, electionId) {
    if (!confirm('Êtes-vous sûr de vouloir retirer ce membre du comité ?')) {
        return;
    }

    try {
        const response = await fetch(apiUrl(`action=remove_committee_member&user_id=${userId}&election_id=${electionId}`), {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            loadCommittees();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

// Fonctions utilitaires
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    
    // Définir l'icône selon le type
    let icon = 'exclamation';
    if (type === 'success') icon = 'check';
    else if (type === 'info') icon = 'info';
    
    alert.innerHTML = `
        <i class="fas fa-${icon}-circle"></i>
        ${message}
    `;
    alert.style.display = 'block';
    
    alertContainer.appendChild(alert);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function showModal(modalId, preselectUserId = null) {
    document.getElementById(modalId).style.display = 'block';
    
    // Charger les élections si c'est le modal d'ajout de poste
    if (modalId === 'addPosteModal') {
        loadElectionsForPosteModal();
    }
    
    // Charger les utilisateurs et élections si c'est le modal d'ajout de membre au comité
    if (modalId === 'addCommitteeModal') {
        loadUsersAndElectionsForCommitteeModal(preselectUserId);
    }
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

async function loadElectionsForPosteModal() {
    try {
        const response = await fetch(apiUrl('action=elections'));
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('election_id');
            select.innerHTML = '<option value="">Sélectionner une élection</option>';
            data.data.forEach(election => {
                select.innerHTML += `<option value="${election.id}">${escapeHtml(election.titre)}</option>`;
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des élections pour le modal:', error);
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    } else {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('open');
    }
}

function toggleClasseCible() {
    const portee = document.getElementById('portee').value;
    const classeCibleGroup = document.getElementById('classeCibleGroup');
    if (portee === 'specifique') {
        classeCibleGroup.style.display = 'block';
    } else {
        classeCibleGroup.style.display = 'none';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function formatStatus(status) {
    return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// Fonctions utilitaires supplémentaires
function editUser(userId) {
    // TODO: Implémenter l'édition d'utilisateur
    showAlert('Fonctionnalité d\'édition en cours de développement', 'error');
}

function editElection(electionId) {
    // TODO: Implémenter l'édition d'élection
    showAlert('Fonctionnalité d\'édition en cours de développement', 'error');
}

function editElectionType(typeId, typeName) {
    document.getElementById('edit_type_id').value = typeId;
    document.getElementById('edit_type_name').value = typeName;
    showModal('editElectionTypeModal');
}

function editPoste(posteId, nomPoste, electionId) {
    document.getElementById('edit_poste_id').value = posteId;
    document.getElementById('edit_nom_poste').value = nomPoste;
    
    // Charger les élections et sélectionner la bonne
    loadElectionsForEditPosteModal(electionId);
    showModal('editPosteModal');
}

async function loadElectionsForEditPosteModal(selectedElectionId) {
    try {
        const response = await fetch(apiUrl('action=elections'));
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('edit_election_id');
            select.innerHTML = '<option value="">Sélectionner une élection</option>';
            data.data.forEach(election => {
                const selected = election.id == selectedElectionId ? 'selected' : '';
                select.innerHTML += `<option value="${election.id}" ${selected}>${escapeHtml(election.titre)}</option>`;
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des élections pour le modal d\'édition:', error);
    }
}

async function loadUsersAndElectionsForCommitteeModal(preselectUserId = null) {
    try {
        // Charger les utilisateurs
        const usersResponse = await fetch(apiUrl('action=users'));
        const usersData = await usersResponse.json();
        
        if (usersData.success) {
            const userSelect = document.getElementById('committee_user_id');
            userSelect.innerHTML = '<option value="">Sélectionner un utilisateur</option>';
            usersData.data.forEach(user => {
                userSelect.innerHTML += `<option value="${user.id}">${escapeHtml(user.prenom + ' ' + user.nom)} - ${escapeHtml(user.email)}</option>`;
            });
            // Pré-sélectionner l'utilisateur si demandé
            if (preselectUserId) {
                userSelect.value = preselectUserId;
                userSelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Charger les élections
        const electionsResponse = await fetch(apiUrl('action=elections'));
        const electionsData = await electionsResponse.json();
        
        if (electionsData.success) {
            const electionSelect = document.getElementById('committee_election_id');
            electionSelect.innerHTML = '<option value="">Sélectionner une élection</option>';
            electionsData.data.forEach(election => {
                electionSelect.innerHTML += `<option value="${election.id}">${escapeHtml(election.titre)}</option>`;
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement des données pour le modal de comité:', error);
    }
}

async function handleAddCommitteeMember(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'add_committee_member');
    try {
        const response = await fetch(window.DASHBOARD_API_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            showAlert(data.message, 'success');
            hideModal('addCommitteeModal');
            e.target.reset();
            loadCommittees();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

// Fermer les modals en cliquant à l'extérieur
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Fermer les modals avec la touche Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

function filterCandidatures(status) {
    // Mettre à jour les styles des boutons
    document.querySelectorAll('[onclick^="filterCandidatures"]').forEach(btn => {
        btn.className = btn.className.replace(/btn-(primary|secondary|success|danger)/g, 'btn-secondary');
    });
    
    // Mettre en surbrillance le bouton actif
    const activeBtn = document.querySelector(`[onclick="filterCandidatures('${status}')"]`);
    if (activeBtn) {
        activeBtn.className = activeBtn.className.replace('btn-secondary', 'btn-primary');
    }
    
    loadCandidatures(status);
}

async function filterUsers(classe) {
    currentFilterClasse = classe;
    
    // Mettre à jour les styles des boutons
    document.querySelectorAll('[onclick^="filterUsers"]').forEach(btn => {
        btn.className = btn.className.replace(/btn-(primary|secondary)/g, 'btn-secondary');
    });
    
    // Mettre en surbrillance le bouton actif
    const activeBtn = document.querySelector(`[onclick="filterUsers('${classe}')"]`);
    if (activeBtn) {
        activeBtn.className = activeBtn.className.replace('btn-secondary', 'btn-primary');
    }
    
    // Si il y a un terme de recherche actif, faire une nouvelle recherche avec le filtre
    if (currentSearchTerm) {
        await searchUsers();
        return;
    }
    
    // Sinon, charger normalement
    loadUsers(classe);
}

async function searchUsers() {
    const searchTerm = document.getElementById('userSearchInput').value.trim();
    if (!searchTerm) {
        showAlert('Veuillez entrer un terme de recherche', 'error');
        return;
    }
    
    currentSearchTerm = searchTerm;
    
    try {
        const response = await fetch(apiUrl(`action=search_users&search=${encodeURIComponent(searchTerm)}&classe=${currentFilterClasse}`));
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.data, searchTerm, data.results_count);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

function clearSearch() {
    document.getElementById('userSearchInput').value = '';
    currentSearchTerm = '';
    loadUsers(currentFilterClasse);
}

function displaySearchResults(users, searchTerm, resultsCount) {
    const table = document.getElementById('usersTable');
    
    if (!users || users.length === 0) {
        table.innerHTML = `
            <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-search" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                <h4>Aucun résultat trouvé</h4>
                <p>Aucun utilisateur ne correspond à "<strong>${escapeHtml(searchTerm)}</strong>"</p>
                <button class="btn btn-primary" onclick="clearSearch()">
                    <i class="fas fa-arrow-left"></i> Voir tous les utilisateurs
                </button>
            </div>
        `;
        return;
    }
    
    const classeText = currentFilterClasse === 'all' ? 'toutes les classes' : `la classe ${currentFilterClasse}`;
    
    let html = `
        <div style="margin-bottom: 1rem; padding: 1rem; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>${resultsCount} résultat${resultsCount > 1 ? 's' : ''} trouvé${resultsCount > 1 ? 's' : ''}</strong> pour "<strong>${escapeHtml(searchTerm)}</strong>"
                    ${currentFilterClasse !== 'all' ? ` dans ${classeText}` : ''}
                </div>
                <button class="btn btn-secondary btn-sm" onclick="clearSearch()">
                    <i class="fas fa-times"></i> Effacer la recherche
                </button>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Classe</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    users.forEach(user => {
        // Mettre en surbrillance les termes de recherche
        const highlightedNom = highlightSearchTerm(user.nom, searchTerm);
        const highlightedPrenom = highlightSearchTerm(user.prenom, searchTerm);
        const highlightedEmail = highlightSearchTerm(user.email, searchTerm);
        
        html += `
            <tr>
                <td>${highlightedNom}</td>
                <td>${highlightedPrenom}</td>
                <td>${highlightedEmail}</td>
                <td><span style="font-weight: bold; color: #2563eb;">${escapeHtml(user.classe)}</span></td>
                <td>
                    <span class="status-badge status-${user.role}">
                        ${formatStatus(user.role)}
                    </span>
                </td>
                <td>${formatDate(user.date_inscription)}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editUser(${user.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    table.innerHTML = html;
}

function highlightSearchTerm(text, searchTerm) {
    if (!searchTerm) return escapeHtml(text);
    
    const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
    return escapeHtml(text).replace(regex, '<mark style="background: #ffeb3b; padding: 2px 4px; border-radius: 3px;">$1</mark>');
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Fermer la sidebar sur mobile après navigation
function closeSidebarOnMobile() {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    }
}

// Gestion du redimensionnement de la fenêtre
window.addEventListener('resize', function() {
    // Fermer la sidebar si on passe en mode desktop
    if (window.innerWidth > 768) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    }
});

// Amélioration de l'accessibilité mobile
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des attributs ARIA pour l'accessibilité
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.querySelector('[onclick="toggleSidebar()"]');
    
    if (sidebar && sidebarToggle) {
        sidebar.setAttribute('aria-hidden', 'true');
        sidebarToggle.setAttribute('aria-expanded', 'false');
        sidebarToggle.setAttribute('aria-controls', 'sidebar');
        sidebarToggle.setAttribute('aria-label', 'Ouvrir le menu de navigation');
    }
    
    // Améliorer la fonction toggleSidebar
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.querySelector('[onclick="toggleSidebar()"]');
        
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            sidebar.setAttribute('aria-hidden', 'true');
            if (overlay) overlay.classList.remove('open');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', 'false');
                toggleBtn.setAttribute('aria-label', 'Ouvrir le menu de navigation');
            }
        } else {
            sidebar.classList.add('open');
            sidebar.setAttribute('aria-hidden', 'false');
            if (overlay) overlay.classList.add('open');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.setAttribute('aria-label', 'Fermer le menu de navigation');
            }
        }
    };

    // Ajouter les écouteurs d'événements pour la recherche des comités
    const committeeSearchInput = document.getElementById('committeeSearchInput');
    if (committeeSearchInput) {
        // Recherche en appuyant sur Entrée
        committeeSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCommitteeUsers();
            }
        });
        
        // Recherche en temps réel (avec délai)
        let searchTimeout;
        committeeSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2) {
                    searchCommitteeUsers();
                } else if (this.value.length === 0) {
                    clearCommitteeSearch();
                }
            }, 500);
        });
    }
});

// Fonctions pour la recherche des comités
async function searchCommitteeUsers() {
    const searchTerm = document.getElementById('committeeSearchInput').value.trim();
    if (!searchTerm) {
        showAlert('Veuillez entrer un terme de recherche', 'error');
        return;
    }
    
    try {
        const response = await fetch(apiUrl(`action=search_committee_users&search=${encodeURIComponent(searchTerm)}`));
        const data = await response.json();
        
        if (data.success) {
            displayCommitteeSearchResults(data.data, searchTerm, data.results_count);
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        showAlert('Erreur de connexion', 'error');
    }
}

function clearCommitteeSearch() {
    document.getElementById('committeeSearchInput').value = '';
    loadCommittees();
}

function displayCommitteeSearchResults(users, searchTerm, resultsCount) {
    const table = document.getElementById('committeesTable');
    
    if (!users || users.length === 0) {
        table.innerHTML = `
            <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-search" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                <h4>Aucun résultat trouvé</h4>
                <p>Aucun étudiant ne correspond à "<strong>${escapeHtml(searchTerm)}</strong>"</p>
                <button class="btn btn-primary" onclick="clearCommitteeSearch()">
                    <i class="fas fa-arrow-left"></i> Voir tous les comités
                </button>
            </div>
        `;
        return;
    }
    
    let html = `
        <div style="margin-bottom: 1rem; padding: 1rem; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>${resultsCount} résultat${resultsCount > 1 ? 's' : ''} trouvé${resultsCount > 1 ? 's' : ''}</strong> pour "<strong>${escapeHtml(searchTerm)}</strong>"
                </div>
                <button class="btn btn-secondary btn-sm" onclick="clearCommitteeSearch()">
                    <i class="fas fa-times"></i> Effacer la recherche
                </button>
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <button class="btn btn-primary" onclick="showModal('addCommitteeModal')">
                <i class="fas fa-user-plus"></i> Nommer un membre au comité
            </button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Classe</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    users.forEach(user => {
        // Mettre en surbrillance les termes de recherche
        const highlightedNom = highlightSearchTerm(user.nom, searchTerm);
        const highlightedPrenom = highlightSearchTerm(user.prenom, searchTerm);
        const highlightedEmail = highlightSearchTerm(user.email, searchTerm);
        const highlightedClasse = highlightSearchTerm(user.classe, searchTerm);
        
        html += `
            <tr>
                <td>${highlightedNom}</td>
                <td>${highlightedPrenom}</td>
                <td>${highlightedEmail}</td>
                <td><span style="font-weight: bold; color: #2563eb;">${highlightedClasse}</span></td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="nominateToCommittee(${user.id}, '${escapeHtml(user.nom)}', '${escapeHtml(user.prenom)}')" title="Nommer au comité">
                        <i class="fas fa-user-plus"></i> Nommer
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    table.innerHTML = html;
}

function nominateToCommittee(userId, nom, prenom) {
    showModal('addCommitteeModal', userId);
    showAlert(`Prêt à nommer ${prenom} ${nom} au comité. Veuillez sélectionner l'élection et confirmer.`, 'info');
}
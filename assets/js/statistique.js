let donneesAES = [];
let donneesClubs = [];
let donneesClasse = [];

// ===============================
// Chargement des données
// ===============================
function groupByPoste(candidats) {
    const map = {};
    candidats.forEach(c => {
        const p = c.poste || 'Autre';
        if (!map[p]) map[p] = { poste: p, candidats: [] };
        map[p].candidats.push(c);
    });
    return Object.values(map);
}
function groupByClub(candidats) {
    const map = {};
    candidats.forEach(c => {
        const cl = c.club || 'Autre';
        if (!map[cl]) map[cl] = { nomClub: cl, candidats: [] };
        map[cl].candidats.push(c);
    });
    return Object.values(map);
}
function loadCandidates() {
    const all = JSON.parse(localStorage.getItem('candidatures') || '[]');
    donneesAES = groupByPoste(all.filter(c => c.type && c.type.toLowerCase() === 'aes'));
    donneesClubs = groupByClub(all.filter(c => c.type && c.type.toLowerCase() === 'club'));
    donneesClasse = groupByPoste(all.filter(c => c.type && c.type.toLowerCase() === 'classe'));
}

// ===============================
// Fonctions utilitaires pour les votes
// ===============================
function getVoteKey(type, index) {
    return `vote_${type}_${index}`;
}
function countVotes(type, data) {
    let result = [];
    for (let i = 0; i < data.length; i++) {
        const poste = data[i];
        let candidats = poste.candidats;
        let counts = candidats.map(c => ({ ...c, votes: 0 }));
        const vote = localStorage.getItem(getVoteKey(type, i));
        if (vote) {
            try {
                const v = JSON.parse(vote);
                // Recherche tolérante (trim et insensible à la casse)
                const idx = candidats.findIndex(c =>
                    c.nom && v.nom &&
                    c.prenom && v.prenom &&
                    c.nom.trim().toLowerCase() === v.nom.trim().toLowerCase() &&
                    c.prenom.trim().toLowerCase() === v.prenom.trim().toLowerCase()
                );
                if (idx !== -1) counts[idx].votes = 1; // 1 vote par navigateur
            } catch (e) {
                // Erreur de parsing, ignorer
            }
        }
        result.push({ poste: poste.poste || poste.nomClub, candidats: counts });
    }
    return result;
}
function totalVotes(type, data) {
    let count = 0;
    for (let i = 0; i < data.length; i++) {
        if (localStorage.getItem(getVoteKey(type, i))) count++;
    }
    return count;
}

// ===============================
// Vérifie si une session de vote est active et commencée pour une catégorie
// ===============================
function isVoteActive(categorie) {
    return window.isVoteActive(categorie);
}
function hasVotedAll(type) {
    if (type === 'aes') {
        return donneesAES.length > 0 && donneesAES.every((_, idx) => !!localStorage.getItem(getVoteKey('aes', idx)));
    }
    if (type === 'classe') {
        return donneesClasse.length > 0 && donneesClasse.every((_, idx) => !!localStorage.getItem(getVoteKey('classe', idx)));
    }
    if (type === 'club') {
        return donneesClubs.length > 0 && donneesClubs.every((_, idx) => !!localStorage.getItem(getVoteKey('club', idx)));
    }
    return false;
}

// ===============================
// Affichage des chiffres clés
// ===============================
function afficherStatsGlobal(type, data) {
    const nbPostes = data.length;
    const nbVotes = totalVotes(type, data);
    const taux = nbPostes === 0 ? 0 : Math.round((nbVotes / nbPostes) * 100);

    document.getElementById('stats-global').innerHTML = `
        <div class="stats-card">
            <div class="stat-value">${nbVotes}</div>
            <div class="stat-label">Nombre de votes exprimés</div>
        </div>
        <div class="stats-card">
            <div class="stat-value">${nbPostes}</div>
            <div class="stat-label">Nombre de postes/clubs</div>
        </div>
        <div class="stats-card">
            <div class="stat-value">${taux}%</div>
            <div class="stat-label">Taux de participation</div>
        </div>
    `;
}

// ===============================
// Affichage du graphique principal (barres)
// ===============================
let statsChart = null;
function afficherStatsGraph(type, data) {
    const stats = countVotes(type, data);
    const poste = stats.find(p => p.candidats.some(c => c.votes > 0)) || stats[0];
    if (!poste) {
        document.getElementById('stats-graph').style.display = "none";
        return;
    }
    document.getElementById('stats-graph').style.display = "flex";

    const labels = poste.candidats.map(c => `${c.prenom} ${c.nom}`);
    const votes = poste.candidats.map(c => c.votes);

    if (statsChart) statsChart.destroy();

    const ctx = document.getElementById('statsChart').getContext('2d');
    statsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: `Votes pour ${poste.poste}`,
                data: votes,
                backgroundColor: [
                    '#2563eb', '#38bdf8', '#fbbf24', '#64748b', '#a3e635', '#f472b6', '#f87171'
                ],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: true, text: `Répartition des votes pour "${poste.poste}"` }
            },
            scales: {
                y: { beginAtZero: true, precision: 0, stepSize: 1 }
            }
        }
    });
}

// ===============================
// Affichage du détail par poste/club/classe
// ===============================
function afficherStatsDetail(type, data) {
    const stats = countVotes(type, data);
    document.getElementById('stats-detail').innerHTML = stats.map(poste => `
        <div class="stats-poste">
            <h3>${poste.poste}</h3>
            <div class="stats-candidats">
                ${poste.candidats.map(c => `
                    <div class="stats-candidat">
                        <img src="${c.photo}" alt="${c.prenom} ${c.nom}">
                        <div class="candidat-info">
                            <div class="candidat-nom">${c.prenom} ${c.nom}</div>
                            <div class="candidat-desc">${c.classe ? c.classe : ''} ${c.nationalite ? ' - ' + c.nationalite : ''}</div>
                        </div>
                        <div class="candidat-votes">${c.votes} vote${c.votes > 1 ? 's' : ''}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

// ===============================
// Gestion du selecteur de type d'élection et affichage principal
// ===============================
function afficherStats(type) {
    loadCandidates();
    let data;
    if (type === 'aes') data = donneesAES;
    else if (type === 'club') data = donneesClubs;
    else if (type === 'classe') data = donneesClasse;
    else return;

    // L'utilisateur doit avoir voté pour tous les postes de la catégorie
    if (!hasVotedAll(type)) {
        document.getElementById('stats-global').innerHTML = `<p style="color:orange;">Vous devez voter pour tous les postes de cette catégorie pour consulter les statistiques.</p>`;
        document.getElementById('stats-graph').style.display = "none";
        document.getElementById('stats-detail').innerHTML = "";
        return;
    }

    if (!data || data.length === 0) {
        document.getElementById('stats-global').innerHTML = `<p style="color:red;">Aucune donnée disponible pour cette catégorie.</p>`;
        document.getElementById('stats-graph').style.display = "none";
        document.getElementById('stats-detail').innerHTML = "";
        return;
    }

    afficherStatsGlobal(type, data);
    afficherStatsGraph(type, data);
    afficherStatsDetail(type, data);
}

window.addEventListener('DOMContentLoaded', function() {
    loadCandidates();
    const select = document.getElementById('type-stats');
    if (!select) return;
    select.value = 'aes';
    afficherStats('aes');
    select.addEventListener('change', function() {
        afficherStats(this.value);
    });
});
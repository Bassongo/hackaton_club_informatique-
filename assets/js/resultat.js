// ===============================
// Données des élections 
// ===============================


// Données dynamiques depuis les candidatures enregistrées
let donneesAES = [];
let donneesClubs = [];
let donneesClasse = [];

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

function getVoteSessionStatus(type) {
    const state = getState();
    const session = type === 'club' ? state.vote.club : state.vote[type];
    if (!session) return { status: 'none', session: null };
    const now = Date.now();

    if (session.active && now > session.endTime) {
        session.active = false;
        saveState(state);
    }

    if (now < session.startTime) return { status: 'not_started', session };
    if (session.active && now <= session.endTime) return { status: 'active', session };
    if (now >= session.endTime) return { status: 'closed', session };
    return { status: 'none', session };
}

// Compte les votes pour chaque candidat d'un poste/club/classe
function countVotes(type, data) {
    let result = [];
    for (let i = 0; i < data.length; i++) {
        const poste = data[i];
        let candidats = poste.candidats || poste.candidats;
        let counts = candidats.map(c => ({ ...c, votes: 0 }));
        const vote = localStorage.getItem(getVoteKey(type, i));
        if (vote) {
            const v = JSON.parse(vote);
            const idx = candidats.findIndex(c => c.nom === v.nom && c.prenom === v.prenom);
            if (idx !== -1) counts[idx].votes = 1; // 1 vote par navigateur
        }
        result.push({ poste: poste.poste || poste.nomClub, candidats: counts });
    }
    return result;
}

// ===============================
// Affichage des résultats (uniquement le vainqueur ou message)
// ===============================
function afficherResultats(type) {
    let data;
    if (type === 'aes') data = donneesAES;
    else if (type === 'club') data = donneesClubs;
    else if (type === 'classe') data = donneesClasse;
    else return;

    const stats = countVotes(type, data);

    document.getElementById('resultats').innerHTML = stats.map((poste, idx) => {
        // Calcul du total de votes pour ce poste
        const total = poste.candidats.reduce((sum, c) => sum + c.votes, 0);

        if (total === 0) {
            // Aucun vote pour ce poste
            return `
                <div class="result-block">
                    <div class="result-title">${poste.poste}</div>
                    <div class="no-winner">
                        <span class="no-winner-icon">😶</span>
                        <div class="no-winner-msg">Aucun vote enregistré pour ce poste.<br><em>Pas de vainqueur pour le moment.</em></div>
                    </div>
                </div>
            `;
        }

        // Cherche le vainqueur (max votes)
        const winner = poste.candidats.reduce((a, b) => (a.votes > b.votes ? a : b));
        const winnerPercent = Math.round((winner.votes / total) * 100);

        // Bloc vainqueur uniquement
        return `
            <div class="result-block">
                <div class="result-title">${poste.poste}</div>
                <div class="winner-card" data-poste="${idx}">
                    <img src="${winner.photo}" alt="${winner.prenom} ${winner.nom}" class="winner-photo">
                    <div class="winner-info">
                        <div class="winner-name">${winner.prenom} ${winner.nom}</div>
                        <div class="winner-percent">Vainqueur - ${winnerPercent}% des voix</div>
                    </div>
                    <span class="winner-trophy" title="Vainqueur 🏆">🏆</span>
                </div>
            </div>
        `;
    }).join('');

    // Effet confetti à l'ouverture
    confetti({
        particleCount: 120,
        spread: 90,
        origin: { y: 0.2 },
        colors: ['#2563eb', '#38bdf8', '#fbbf24', '#a3e635', '#f472b6', '#f87171']
    });

    // Effet confetti au clic sur un vainqueur
    document.querySelectorAll('.winner-card').forEach(card => {
        card.addEventListener('click', () => {
            confetti({
                particleCount: 180,
                spread: 120,
                origin: { y: 0.3 },
                colors: ['#fbbf24', '#38bdf8', '#2563eb', '#a3e635', '#f472b6', '#f87171']
            });
        });
    });
}

// ===============================
// Gestion du selecteur de type d'élection
// ===============================
function updateResultats(type) {
    const container = document.getElementById('resultats');
    const { status } = getVoteSessionStatus(type);

    if (status === 'closed') {
        afficherResultats(type);
        return;
    }

    if (status === 'active') {
        container.innerHTML = '<p>Session de vote en cours.</p>';
        return;
    }

    container.innerHTML = '<p>Pas de résultats.</p>';
}

document.getElementById('type-result').addEventListener('change', function () {
    updateResultats(this.value);
});

// ===============================
// Affichage initial à l'ouverture de la page
// ===============================
window.addEventListener('DOMContentLoaded', function() {
    loadCandidates();
    const select = document.getElementById('type-result');
    if (select) {
        updateResultats(select.value);
    }
});
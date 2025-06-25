// ===============================
// Données des élections 
// ===============================

let donneesAES = [];
let donneesClubs = [];
let donneesClasse = [];

// Groupement des candidats
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
    donneesAES = groupByPoste(all.filter(c => c.type && c.type.trim().toLowerCase() === 'aes'));
    donneesClubs = groupByClub(all.filter(c => c.type && c.type.trim().toLowerCase() === 'club'));
    donneesClasse = groupByPoste(all.filter(c => c.type && c.type.trim().toLowerCase() === 'classe'));
}

// ===============================
// Variables de pagination et type courant
// ===============================
let pageAES = 0;
let pageClub = 0;
let pageClasse = 0;
let currentType = 'aes';

// ===============================
// Gestion du vote (localStorage)
// ===============================
function getVoteKey(type, index) {
    return `vote_${type}_${index}`;
}
function hasVoted(type, index) {
    return !!localStorage.getItem(getVoteKey(type, index));
}
function setVote(type, index, candidat) {
    localStorage.setItem(getVoteKey(type, index), JSON.stringify(candidat));
}
function setUserVoted(type) {}

const ONE_DAY = 24 * 60 * 60 * 1000;

function getVoteSessionStatus(type) {
    if (window.getState) {
        const state = window.getState();
        const session = state.vote && state.vote[type];
        if (!session) return { status: 'none', session: null };
        const now = Date.now();
        if (session.active && now > session.endTime) {
            session.active = false;
            window.saveState && window.saveState(state);
        }
        if (!session.active) {
            if (now - session.endTime <= ONE_DAY) return { status: 'closed_recently', session };
            return { status: 'none', session };
        }
        if (now < session.startTime) return { status: 'not_started', session };
        if (now >= session.startTime && now <= session.endTime) return { status: 'active', session };
        return { status: 'none', session };
    } else {
        // fallback localStorage (ancien code)
        let votes = JSON.parse(localStorage.getItem('votesSessions') || '{}');
        if (!votes[type]) return { status: 'none', session: null };
        const session = votes[type];
        const now = Date.now();
        if (session.active && now > session.end) {
            session.active = false;
            votes[type] = session;
            localStorage.setItem('votesSessions', JSON.stringify(votes));
        }
        if (!session.active) {
            if (now - session.end <= ONE_DAY) return { status: 'closed_recently', session };
            return { status: 'none', session };
        }
        if (now < session.start) return { status: 'not_started', session };
        if (now >= session.start && now <= session.end) return { status: 'active', session };
        return { status: 'none', session };
    }
}

// ===============================
// Vérifie si une session de vote est active pour une catégorie
function isVoteActive(categorie) {
    if (window.isVoteActive) {
        return window.isVoteActive(categorie);
    }
    let votes = JSON.parse(localStorage.getItem('votesSessions') || '{}');
    if (!votes[categorie]) return false;
    const now = Date.now();
    if (votes[categorie].active && now > votes[categorie].end) {
        votes[categorie].active = false;
        localStorage.setItem('votesSessions', JSON.stringify(votes));
        return false;
    }
    if (votes[categorie].active && now >= votes[categorie].start && now <= votes[categorie].end) {
        return true;
    }
    return false;
}

// Vérifie si l'utilisateur a voté pour tous les postes/clubs d'une catégorie
function hasVotedAll(type) {
    if (type === 'aes') {
        return donneesAES.length > 0 && donneesAES.every((_, idx) => hasVoted('aes', idx));
    }
    if (type === 'classe') {
        return donneesClasse.length > 0 && donneesClasse.every((_, idx) => hasVoted('classe', idx));
    }
    if (type === 'club') {
        return donneesClubs.length > 0 && donneesClubs.every((_, idx) => hasVoted('club', idx));
    }
    return false;
}

// ===============================
// Mise à jour de l'info de session
// ===============================
function updateVoteInfo(type) {
    const info = document.getElementById('vote-info');
    if (!info) return;
    const { status, session } = getVoteSessionStatus(type);
    if (!session || status === 'none') {
        info.textContent = `Pas de session de vote ${type.toUpperCase()}.`;
        return;
    }
    const start = new Date(session.startTime || session.start);
    const end = new Date(session.endTime || session.end);
    if (status === 'not_started') {
        info.textContent = `La session de vote ${type.toUpperCase()} commencera le ${start.toLocaleString()}.`;
    } else if (status === 'active') {
        info.textContent = `Vote ${type.toUpperCase()} : du ${start.toLocaleString()} au ${end.toLocaleString()}`;
    } else if (status === 'closed_recently') {
        info.textContent = `La session de vote ${type.toUpperCase()} est terminée.`;
    } else {
        info.textContent = `Pas de session de vote ${type.toUpperCase()}.`;
    }
}

// ===============================
// Affichage d'une photo en grand (modale)
// ===============================
function afficherPhotoGrand(url, nom, infos = "") {
    const modal = document.createElement('div');
    modal.className = "modal-overlay";
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal" aria-label="Fermer">&times;</button>
            <img src="${url}" alt="${nom}">
            <h4>${nom}</h4>
            ${infos ? `<div>${infos}</div>` : ""}
        </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('.close-modal').onclick = () => modal.remove();
    modal.onclick = e => { if (e.target === modal) modal.remove(); };
}

// ===============================
// Affichage AES (par poste)
// ===============================
function afficherAES(index = 0) {
    loadCandidates();
    pageAES = index;
    currentType = 'aes';
    const contenu = document.getElementById('contenu-vote');
    updateVoteInfo('aes');
    const { status, session } = getVoteSessionStatus('aes');
    let periode = '';
    if (session) {
        const deb = new Date(session.startTime || session.start);
        const end = new Date(session.endTime || session.end);
        periode = `<div class="periode">Vote du ${deb.toLocaleString()} au ${end.toLocaleString()}</div>`;
    }
    if (status === 'not_started') {
        contenu.innerHTML = `${periode}<div class="alert">La session de vote AES n'a pas encore commencé.</div>`;
        return;
    }
    if (status === 'closed_recently') {
        contenu.innerHTML = `${periode}<div class="alert">La session de vote AES est terminée.</div>`;
        return;
    }
    if (status === 'none') {
        contenu.innerHTML = `${periode}<div class="alert">Pas de session de vote AES.</div>`;
        return;
    }
    const poste = donneesAES[pageAES];
    if (!poste) {
        contenu.innerHTML = `<div class="alert">Aucun poste disponible.</div>`;
        return;
    }
    const voteKey = getVoteKey('aes', pageAES);
    const dejaVote = hasVoted('aes', pageAES);

    // Génère le HTML des candidats avec bouton de vote
    const candidatsHTML = poste.candidats.map((c, i) => {
        const voted = dejaVote && JSON.parse(localStorage.getItem(voteKey)).nom === c.nom && JSON.parse(localStorage.getItem(voteKey)).prenom === c.prenom;
        return `
        <div class="candidat">
            <img src="${c.photo}" alt="${c.prenom} ${c.nom}" class="photo-candidat" data-nom="${c.prenom} ${c.nom}" data-infos="<strong>Classe :</strong> ${c.classe}<br><strong>Nationalité :</strong> ${c.nationalite}<br><em>${c.mots}</em>">
            <h4>${c.prenom} ${c.nom}</h4>
            <p><strong>Classe :</strong> ${c.classe}</p>
            <p><strong>Nationalité :</strong> ${c.nationalite}</p>
            <p><em>"${c.mots}"</em></p>
            <button class="vote-btn${voted ? ' selected' : ''}" data-index="${i}" ${dejaVote ? 'disabled' : ''}>${voted ? 'Votre vote' : 'Voter'}</button>
        </div>
        `;
    }).join('');

    // Pagination
    const paginationHTML = `
        <button class="page-prev" ${pageAES === 0 ? 'disabled' : ''}>Précédent</button>
        <span>Poste ${pageAES + 1} / ${donneesAES.length}</span>
        <button class="page-next" ${pageAES === donneesAES.length - 1 ? 'disabled' : ''}>Suivant</button>
    `;

    contenu.innerHTML = `
        <h2>Élections AES</h2>
        <div class="poste">
            <h3>Poste : ${poste.poste}</h3>
            <div class="candidats">${candidatsHTML}</div>
        </div>
        <div class="pagination">${paginationHTML}</div>
        ${dejaVote ? `<div class="vote-confirm">Vous avez voté pour ce poste.<br>Merci pour votre participation !</div>` : ""}
    `;

    // Pagination
    contenu.querySelector('.page-prev')?.addEventListener('click', () => {
        if (pageAES > 0) {
            afficherAES(pageAES - 1);
        }
    });
    contenu.querySelector('.page-next')?.addEventListener('click', () => {
        if (pageAES < donneesAES.length - 1) {
            afficherAES(pageAES + 1);
        }
    });

    // Vote
    if (!dejaVote) {
        contenu.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                setVote('aes', pageAES, poste.candidats[idx]);
                setUserVoted('aes');
                if (hasVotedAll('aes')) {
                    localStorage.setItem('canSeeStats', 'aes');
                }
                afficherAES(pageAES);
            });
        });
    }

    // Affichage grand format sur chaque photo
    contenu.querySelectorAll('.photo-candidat').forEach(img => {
        img.addEventListener('click', function() {
            afficherPhotoGrand(this.src, this.dataset.nom, this.dataset.infos);
        });
    });
}

// ===============================
// Affichage Clubs (par club)
// ===============================
function afficherClub(index = 0) {
    loadCandidates();
    pageClub = index;
    currentType = 'club';
    const contenu = document.getElementById('contenu-vote');
    updateVoteInfo('club');
    const { status, session } = getVoteSessionStatus('club');
    let periode = '';
    if (session) {
        const deb = new Date(session.startTime || session.start);
        const end = new Date(session.endTime || session.end);
        periode = `<div class="periode">Vote du ${deb.toLocaleString()} au ${end.toLocaleString()}</div>`;
    }
    if (status === 'not_started') {
        contenu.innerHTML = `${periode}<div class="alert">La session de vote Club n'a pas encore commencé.</div>`;
        return;
    }
    if (status === 'closed_recently') {
        contenu.innerHTML = `${periode}<div class="alert">La session de vote Club est terminée.</div>`;
        return;
    }
    if (status === 'none') {
        contenu.innerHTML = `${periode}<div class="alert">Pas de session de vote Club.</div>`;
        return;
    }
    const club = donneesClubs[pageClub];
    if (!club) {
        contenu.innerHTML = `<div class="alert">Aucun club disponible.</div>`;
        return;
    }
    const voteKey = getVoteKey('club', pageClub);
    const dejaVote = hasVoted('club', pageClub);

    // Génère le HTML pour chaque candidat à la présidence du club
    const candidatsHTML = club.candidats.map((candidat, idx) => {
        const voted = dejaVote && JSON.parse(localStorage.getItem(voteKey)).nom === candidat.nom && JSON.parse(localStorage.getItem(voteKey)).prenom === candidat.prenom;
        return `
        <div class="candidat">
            <img src="${candidat.photo}" alt="${candidat.prenom} ${candidat.nom}" class="photo-candidat"
                data-nom="${candidat.prenom} ${candidat.nom}"
                data-infos="<strong>Classe :</strong> ${candidat.classe}<br><strong>Nationalité :</strong> ${candidat.nationalite}">
            <h4>${candidat.prenom} ${candidat.nom}</h4>
            <p><strong>Classe :</strong> ${candidat.classe}</p>
            <p><strong>Nationalité :</strong> ${candidat.nationalite}</p>
            <div class="actions">
                ${candidat.programme ? `<a href="${candidat.programme}" target="_blank" class="btn" download>Voir Programme</a>` : `<button class="btn" disabled>Programme non disponible</button>`}
                <button class="vote-btn${voted ? ' selected' : ''}" data-index="${idx}" ${dejaVote ? 'disabled' : ''}>${voted ? 'Votre vote' : 'Voter'}</button>
                <button class="btn btn-membres" data-candidat="${idx}">Membres de l’équipe</button>
            </div>
        </div>
        `;
    }).join('');

    // Pagination clubs
    const paginationHTML = `
        <button class="page-prev" ${pageClub === 0 ? 'disabled' : ''}>Précédent</button>
        <span>Club ${pageClub + 1} / ${donneesClubs.length}</span>
        <button class="page-next" ${pageClub === donneesClubs.length - 1 ? 'disabled' : ''}>Suivant</button>
    `;

    contenu.innerHTML = `
        <h2>Club : ${club.nomClub}</h2>
        <div class="poste"><h3>Poste : Président</h3></div>
        <div class="candidats">${candidatsHTML}</div>
        <div class="pagination">${paginationHTML}</div>
        ${dejaVote ? `<div class="vote-confirm">Vous avez voté pour ce club.<br>Merci pour votre participation !</div>` : ""}
    `;

    // Pagination clubs
    contenu.querySelector('.page-prev')?.addEventListener('click', () => {
        if (pageClub > 0) {
            afficherClub(pageClub - 1);
        }
    });
    contenu.querySelector('.page-next')?.addEventListener('click', () => {
        if (pageClub < donneesClubs.length - 1) {
            afficherClub(pageClub + 1);
        }
    });

    // Vote
    if (!dejaVote) {
        contenu.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                setVote('club', pageClub, club.candidats[idx]);
                setUserVoted('club');
                if (hasVotedAll('club')) {
                    localStorage.setItem('canSeeStats', 'club');
                }
                afficherClub(pageClub);
            });
        });
    }

    // Affichage grand format sur chaque photo de candidat
    contenu.querySelectorAll('.photo-candidat').forEach(img => {
        img.addEventListener('click', function() {
            afficherPhotoGrand(this.src, this.dataset.nom, this.dataset.infos);
        });
    });

    // Affichage des membres pour chaque candidat
    contenu.querySelectorAll('.btn-membres').forEach(btn => {
        btn.addEventListener('click', function() {
            const idxCandidat = parseInt(this.getAttribute('data-candidat'));
            afficherMembresClub(pageClub, idxCandidat);
        });
    });
}

// ===============================
// Affichage Classe (par poste)
// ===============================
function afficherClasse(index = 0) {
    loadCandidates();
    pageClasse = index;
    currentType = 'classe';
    const contenu = document.getElementById('contenu-vote');
    updateVoteInfo('classe');
    const { status, session } = getVoteSessionStatus('classe');
    let periode = '';
    if (session) {
        const deb = new Date(session.startTime || session.start);
        const end = new Date(session.endTime || session.end);
        periode = `<div class="periode">Vote du ${deb.toLocaleString()} au ${end.toLocaleString()}</div>`;
    }
    if (status === 'not_started') {
        contenu.innerHTML = `${periode}<div class="alert">La session de vote Classe n'a pas encore commencé.</div>`;
        return;
    }
    if (status === 'closed_recently') {
        contenu.innerHTML = `${periode}<div class="alert">La session de vote Classe est terminée.</div>`;
        return;
    }
    if (status === 'none') {
        contenu.innerHTML = `${periode}<div class="alert">Pas de session de vote Classe.</div>`;
        return;
    }
    const poste = donneesClasse[pageClasse];
    if (!poste) {
        contenu.innerHTML = `<div class="alert">Aucun poste disponible.</div>`;
        return;
    }
    const voteKey = getVoteKey('classe', pageClasse);
    const dejaVote = hasVoted('classe', pageClasse);

    // Génère le HTML des candidats
    const candidatsHTML = poste.candidats.map((c, i) => {
        const voted = dejaVote && JSON.parse(localStorage.getItem(voteKey)).nom === c.nom && JSON.parse(localStorage.getItem(voteKey)).prenom === c.prenom;
        return `
        <div class="candidat">
            <img src="${c.photo}" alt="${c.prenom} ${c.nom}" class="photo-candidat" data-nom="${c.prenom} ${c.nom}" data-infos="<strong>Classe :</strong> ${c.classe}<br><strong>Nationalité :</strong> ${c.nationalite}<br><em>${c.mots || ''}</em>">
            <h4>${c.prenom} ${c.nom}</h4>
            <p><strong>Classe :</strong> ${c.classe}</p>
            <p><strong>Nationalité :</strong> ${c.nationalite}</p>
            <p><em>"${c.mots || ''}"</em></p>
            <button class="vote-btn${voted ? ' selected' : ''}" data-index="${i}" ${dejaVote ? 'disabled' : ''}>${voted ? 'Votre vote' : 'Voter'}</button>
        </div>
        `;
    }).join('');

    // Pagination
    const paginationHTML = `
        <button class="page-prev" ${pageClasse === 0 ? 'disabled' : ''}>Précédent</button>
        <span>Poste ${pageClasse + 1} / ${donneesClasse.length}</span>
        <button class="page-next" ${pageClasse === donneesClasse.length - 1 ? 'disabled' : ''}>Suivant</button>
    `;

    contenu.innerHTML = `
        <h2>Élections Classe</h2>
        <div class="poste">
            <h3>Poste : ${poste.poste}</h3>
            <div class="candidats">${candidatsHTML}</div>
        </div>
        <div class="pagination">${paginationHTML}</div>
        ${dejaVote ? `<div class="vote-confirm">Vous avez voté pour ce poste.<br>Merci pour votre participation !</div>` : ""}
    `;

    // Pagination
    contenu.querySelector('.page-prev')?.addEventListener('click', () => {
        if (pageClasse > 0) {
            afficherClasse(pageClasse - 1);
        }
    });
    contenu.querySelector('.page-next')?.addEventListener('click', () => {
        if (pageClasse < donneesClasse.length - 1) {
            afficherClasse(pageClasse + 1);
        }
    });

    // Vote
    if (!dejaVote) {
        contenu.querySelectorAll('.vote-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                setVote('classe', pageClasse, poste.candidats[idx]);
                setUserVoted('classe');
                if (hasVotedAll('classe')) {
                    localStorage.setItem('canSeeStats', 'classe');
                }
                afficherClasse(pageClasse);
            });
        });
    }

    // Affichage grand format sur chaque photo
    contenu.querySelectorAll('.photo-candidat').forEach(img => {
        img.addEventListener('click', function() {
            afficherPhotoGrand(this.src, this.dataset.nom, this.dataset.infos);
        });
    });
}

// ===============================
// Gestion du selecteur de type d'élection
// ===============================
function handleTypeElectionChange() {
    const select = document.getElementById('type-election');
    const selection = select.value;
    currentType = selection;
    pageAES = 0;
    pageClub = 0;
    pageClasse = 0;
    if (selection === 'aes') {
        afficherAES(pageAES);
    } else if (selection === 'club') {
        afficherClub(pageClub);
    } else if (selection === 'classe') {
        afficherClasse(pageClasse);
    }
}

function initVotePage() {
    loadCandidates();
    const select = document.getElementById('type-election');
    select.value = currentType;
    handleTypeElectionChange();
    select.removeEventListener('change', handleTypeElectionChange);
    select.addEventListener('change', handleTypeElectionChange);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVotePage);
} else {
    initVotePage();
}
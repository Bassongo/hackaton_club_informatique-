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

function hasCandidates(cat) {
    if (cat === 'aes') return donneesAES.length > 0;
    if (cat === 'club') return donneesClubs.length > 0;
    if (cat === 'classe') return donneesClasse.length > 0;
    return false;
}


// ===============================
// Variables de pagination
// ===============================
let pageAES = parseInt(localStorage.getItem('pageAES')) || 0;
let pageClub = parseInt(localStorage.getItem('pageClubs')) || 0;
let pageClasse = parseInt(localStorage.getItem('pageClasse')) || 0;

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
    // Ajoute la modale juste avant le footer dynamique
    const footer = document.getElementById('footer');
    if (footer) {
        footer.parentNode.insertBefore(modal, footer);
    } else {
        document.body.appendChild(modal);
    }
    modal.querySelector('.close-modal').onclick = () => modal.remove();
    modal.onclick = e => { if (e.target === modal) modal.remove(); };
}

// ===============================
// Affichage AES (par poste)
// ===============================
function afficherAES(index = pageAES) {
    const contenu = document.getElementById('contenu-election');
    const poste = donneesAES[index];

    // Génère le HTML des candidats
    const candidatsHTML = poste.candidats.map(c => `
        <div class="candidat">
            <img src="${c.photo}" alt="${c.prenom} ${c.nom}" class="photo-candidat" data-nom="${c.prenom} ${c.nom}" data-infos="<strong>Classe :</strong> ${c.classe}<br><strong>Nationalité :</strong> ${c.nationalite}<br><em>${c.mots}</em>">
            <h4>${c.prenom} ${c.nom}</h4>
            <p><strong>Classe :</strong> ${c.classe}</p>
            <p><strong>Nationalité :</strong> ${c.nationalite}</p>
            <p><em>"${c.mots}"</em></p>
        </div>
    `).join('');

    // Pagination
    const paginationHTML = `
        <button class="page-prev" ${index === 0 ? 'disabled' : ''}>Précédent</button>
        <span>Poste ${index + 1} / ${donneesAES.length}</span>
        <button class="page-next" ${index === donneesAES.length - 1 ? 'disabled' : ''}>Suivant</button>
    `;

    // Injection du HTML
    contenu.innerHTML = `
        <h2>Élections AES</h2>
        <div class="poste">
            <h3>Poste : ${poste.poste}</h3>
            <div class="candidats">${candidatsHTML}</div>
        </div>
        <div class="pagination">${paginationHTML}</div>
    `;

    // Pagination : précédent/suivant
    contenu.querySelector('.page-prev')?.addEventListener('click', () => {
        if (index > 0) {
            pageAES = index - 1;
            localStorage.setItem('pageAES', pageAES);
            afficherAES(pageAES);
        }
    });
    contenu.querySelector('.page-next')?.addEventListener('click', () => {
        if (index < donneesAES.length - 1) {
            pageAES = index + 1;
            localStorage.setItem('pageAES', pageAES);
            afficherAES(pageAES);
        }
    });

    // Ajoute l'affichage grand format sur chaque photo
    contenu.querySelectorAll('.photo-candidat').forEach(img => {
        img.addEventListener('click', function() {
            afficherPhotoGrand(this.src, this.dataset.nom, this.dataset.infos);
        });
    });
}

// ===============================
// Affichage Clubs (par club)
// ===============================
function afficherClub(index = pageClub) {
    const contenu = document.getElementById('contenu-election');
    const club = donneesClubs[index];

    // Génère le HTML pour chaque candidat à la présidence du club
    const candidatsHTML = club.candidats.map((candidat, idx) => `
        <div class="candidat">
            <img src="${candidat.photo}" alt="${candidat.prenom} ${candidat.nom}" class="photo-candidat"
                data-nom="${candidat.prenom} ${candidat.nom}"
                data-infos="<strong>Classe :</strong> ${candidat.classe}<br><strong>Nationalité :</strong> ${candidat.nationalite}">
            <h4>${candidat.prenom} ${candidat.nom}</h4>
            <p><strong>Classe :</strong> ${candidat.classe}</p>
            <p><strong>Nationalité :</strong> ${candidat.nationalite}</p>
            <div class="actions">
                ${candidat.programme ? `<a href="${candidat.programme}" target="_blank" class="btn" download>Voir Programme</a>` : `<button class="btn" disabled>Programme non disponible</button>`}
                <button class="btn btn-membres" data-candidat="${idx}">Membres de l’équipe</button>
            </div>
        </div>
    `).join('');

    // Pagination clubs
    const paginationHTML = `
        <button class="page-prev" ${index === 0 ? 'disabled' : ''}>Précédent</button>
        <span>Club ${index + 1} / ${donneesClubs.length}</span>
        <button class="page-next" ${index === donneesClubs.length - 1 ? 'disabled' : ''}>Suivant</button>
    `;

    contenu.innerHTML = `
        <h2>Club : ${club.nomClub}</h2>
        <div class="poste"><h3>Poste : Président</h3></div>
        <div class="candidats">${candidatsHTML}</div>
        <div class="pagination">${paginationHTML}</div>
    `;

    // Pagination clubs
    contenu.querySelector('.page-prev')?.addEventListener('click', () => {
        if (index > 0) {
            pageClub = index - 1;
            localStorage.setItem('pageClubs', pageClub);
            afficherClub(pageClub);
        }
    });
    contenu.querySelector('.page-next')?.addEventListener('click', () => {
        if (index < donneesClubs.length - 1) {
            pageClub = index + 1;
            localStorage.setItem('pageClubs', pageClub);
            afficherClub(pageClub);
        }
    });

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
            afficherMembresClub(index, idxCandidat);
        });
    });
}

// ===============================
// Affichage des membres d'un club
// ===============================
function afficherMembresClub(indexClub, indexCandidat) {
    const club = donneesClubs[indexClub];
    const candidat = club.candidats[indexCandidat];
    const contenu = document.getElementById('contenu-election');

    const membresHTML = (candidat.membres || []).map(m => `
        <div class="membre">
            <img src="${m.photo}" alt="${m.prenom} ${m.nom}" class="photo-candidat"
                data-nom="${m.prenom} ${m.nom}"
                data-infos="<strong>Classe :</strong> ${m.classe}<br><strong>Nationalité :</strong> ${m.nationalite}">
            <h4>${m.prenom} ${m.nom}</h4>
            <p><strong>Classe :</strong> ${m.classe}</p>
            <p><strong>Nationalité :</strong> ${m.nationalite}</p>
        </div>
    `).join('');

    contenu.innerHTML = `
        <h2>Club : ${club.nomClub}</h2>
        <h3>Candidat : ${candidat.prenom} ${candidat.nom}</h3>
        <h4>Membres de l’équipe</h4>
        <div class="membres">${membresHTML}</div>
        <button class="btn btn-retour">Retour</button>
    `;

    contenu.querySelector('.btn-retour')?.addEventListener('click', () => {
        afficherClub(indexClub);
    });

    contenu.querySelectorAll('.photo-candidat').forEach(img => {
        img.addEventListener('click', function() {
            afficherPhotoGrand(this.src, this.dataset.nom, this.dataset.infos);
        });
    });
}

// ===============================
// Affichage Classe (par poste)
// ===============================
function afficherClasse(index = pageClasse) {
    const contenu = document.getElementById('contenu-election');
    const poste = donneesClasse[index];

    // Génère le HTML des candidats
    const candidatsHTML = poste.candidats.map(c => `
        <div class="candidat">
            <img src="${c.photo}" alt="${c.prenom} ${c.nom}" class="photo-candidat" data-nom="${c.prenom} ${c.nom}" data-infos="<strong>Classe :</strong> ${c.classe}<br><strong>Nationalité :</strong> ${c.nationalite}<br><em>${c.mots || ''}</em>">
            <h4>${c.prenom} ${c.nom}</h4>
            <p><strong>Classe :</strong> ${c.classe}</p>
            <p><strong>Nationalité :</strong> ${c.nationalite}</p>
            <p><em>"${c.mots || ''}"</em></p>
        </div>
    `).join('');

    // Pagination
    const paginationHTML = `
        <button class="page-prev" ${index === 0 ? 'disabled' : ''}>Précédent</button>
        <span>Poste ${index + 1} / ${donneesClasse.length}</span>
        <button class="page-next" ${index === donneesClasse.length - 1 ? 'disabled' : ''}>Suivant</button>
    `;

    contenu.innerHTML = `
        <h2>Élections Classe</h2>
        <div class="poste">
            <h3>Poste : ${poste.poste}</h3>
            <div class="candidats">${candidatsHTML}</div>
        </div>
        <div class="pagination">${paginationHTML}</div>
    `;

    // Pagination : précédent/suivant
    contenu.querySelector('.page-prev')?.addEventListener('click', () => {
        if (index > 0) {
            pageClasse = index - 1;
            localStorage.setItem('pageClasse', pageClasse);
            afficherClasse(pageClasse);
        }
    });
    contenu.querySelector('.page-next')?.addEventListener('click', () => {
        if (index < donneesClasse.length - 1) {
            pageClasse = index + 1;
            localStorage.setItem('pageClasse', pageClasse);
            afficherClasse(pageClasse);
        }
    });

    // Affichage grand format sur chaque photo
    contenu.querySelectorAll('.photo-candidat').forEach(img => {
        img.addEventListener('click', function() {
            afficherPhotoGrand(this.src, this.dataset.nom, this.dataset.infos);
        });
    });
}

function afficherCategorie(cat) {
    loadCandidates();
    const info = document.getElementById('campagne-info');
    const contenu = document.getElementById('contenu-election');
    if (!hasCandidates(cat)) {
        if (info) info.textContent = 'Pas de candidat pour ' + cat.toUpperCase();
        if (contenu) contenu.innerHTML = '';
        return;
    }
    if (info) info.textContent = 'Candidats ' + cat.toUpperCase();
    if (cat === 'aes') {
        pageAES = parseInt(localStorage.getItem('pageAES')) || 0;
        afficherAES(pageAES);
    } else if (cat === 'club') {
        pageClub = parseInt(localStorage.getItem('pageClubs')) || 0;
        afficherClub(pageClub);
    } else if (cat === 'classe') {
        pageClasse = parseInt(localStorage.getItem('pageClasse')) || 0;
        afficherClasse(pageClasse);
    }
}

// ===============================
// Gestion du selecteur de type d'élection
// ===============================
document.getElementById('type-election').addEventListener('change', function () {
    afficherCategorie(this.value);
});

// ===============================
// Affichage initial à l'ouverture de la page
// ===============================
window.addEventListener('DOMContentLoaded', function() {
    loadCandidates();
    const select = document.getElementById('type-election');
    afficherCategorie(select.value);
});

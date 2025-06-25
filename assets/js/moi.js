document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('profileContainer');
  const actionsContainer = document.getElementById('committeeActions');

  const userData = JSON.parse(localStorage.getItem('currentUser') || 'null');
  if (!userData) {
    container.innerHTML = '<p>Aucun utilisateur connecté.</p>';
    return;
  }

  const inscription = userData.inscritDepuis ? new Date(userData.inscritDepuis).toLocaleDateString() : '';
  container.innerHTML = `
    <h2>Mon profil</h2>
    <div class="profile-info">
      ${userData.photo ? `<img src="${userData.photo}" class="profile-photo" alt="photo">` : ''}
      ${userData.nom ? `<p><strong>Nom:</strong> ${userData.nom}</p>` : ''}
      ${userData.prenom ? `<p><strong>Prénom:</strong> ${userData.prenom}</p>` : ''}
      <p><strong>Nom d'utilisateur:</strong> ${userData.username}</p>
      <p><strong>Email:</strong> ${userData.email}</p>
      ${userData.classe ? `<p><strong>Classe:</strong> ${userData.classe}</p>` : ''}
      ${userData.nationalite ? `<p><strong>Nationalité:</strong> ${userData.nationalite}</p>` : ''}
      <p><strong>Inscrit depuis:</strong> ${inscription}</p>
    </div>
  `;

  const comites = JSON.parse(localStorage.getItem('comites') || '{}');
  const categories = Object.keys(comites).filter(cat =>
    (comites[cat] || []).some(m => m.email === userData.email)
  );

  if (categories.length > 0) {
    showCommitteeActions(actionsContainer, categories);
    setupModals(categories);
  }
});

function showCommitteeActions(container, categories) {
  container.innerHTML = `
    <div class="committee-section">
      <button class="admin-btn" id="startCandBtn">Démarrer les candidatures</button>
      <button class="admin-btn danger" id="stopCandBtn">Fermer les candidatures</button>
      <button class="admin-btn" id="startVoteBtn">Démarrer les votes</button>
      <button class="admin-btn danger" id="stopVoteBtn">Fermer les votes</button>
    </div>
  `;

  document.getElementById('startCandBtn').onclick = () => {
    window.resetCandModal();
    const candType = document.getElementById('candType');
    const candStep1 = document.getElementById('candStep1');
    const candStep2 = document.getElementById('candStep2');
    if (categories.length === 1 && candType && candStep1 && candStep2) {
      candType.value = categories[0];
      candStep1.style.display = 'none';
      candStep2.style.display = 'block';
    } else {
      candStep1.style.display = 'block';
      candStep2.style.display = 'none';
    }
    document.getElementById('startCandModal').style.display = 'flex';
  };

  document.getElementById('stopCandBtn').onclick = () => window.openCloseSession('candidature');

  document.getElementById('startVoteBtn').onclick = () => {
    window.resetVoteModal();
    const voteType = document.getElementById('voteType');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    if (categories.length === 1 && voteType && step1 && step2) {
      voteType.value = categories[0];
      step1.style.display = 'none';
      step2.style.display = 'block';
    } else {
      step1.style.display = 'block';
      step2.style.display = 'none';
    }
    document.getElementById('startVotesModal').style.display = 'flex';
  };

  document.getElementById('stopVoteBtn').onclick = () => window.openCloseSession('vote');
}

function setupModals(categories) {
  const startVotesModal = document.getElementById('startVotesModal');
  const closeStartVotes = document.getElementById('closeStartVotes');
  const nextToDates = document.getElementById('nextToDates');
  const validateVoteModal = document.getElementById('validateVoteModal');
  const cancelVoteModal = document.getElementById('cancelVoteModal');
  const step1 = document.getElementById('step1');
  const step2 = document.getElementById('step2');
  const voteType = document.getElementById('voteType');
  const startVoteInput = document.getElementById('startVote');
  const endVoteInput = document.getElementById('endVote');

  const startCandModal = document.getElementById('startCandModal');
  const closeStartCand = document.getElementById('closeStartCand');
  const candNextToDate = document.getElementById('candNextToDate');
  const validateCandModal = document.getElementById('validateCandModal');
  const cancelCandModal = document.getElementById('cancelCandModal');
  const candStep1 = document.getElementById('candStep1');
  const candStep2 = document.getElementById('candStep2');
  const candType = document.getElementById('candType');
  const startCandDate = document.getElementById('startCandDate');
  const endCandDate = document.getElementById('endCandDate');

  const closeSessionModal = document.getElementById('closeSessionModal');
  const closeCloseSession = document.getElementById('closeCloseSession');
  const closeSessionCategory = document.getElementById('closeSessionCategory');
  const cancelCloseSession = document.getElementById('cancelCloseSession');
  const validateCloseSession = document.getElementById('validateCloseSession');
  let closeType = null;

  function fillCategories(select) {
    select.innerHTML = '<option value="" selected disabled>Choisir un type</option>';
    categories.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c.toUpperCase();
      select.appendChild(opt);
    });
  }

  fillCategories(voteType);
  fillCategories(candType);

  function fillCloseOptions(type) {
    closeSessionCategory.innerHTML = '<option value="" selected disabled>Choisir une catégorie</option>';
    const state = getState();
    categories.forEach(c => {
      const sess = type === 'vote' ? (c === 'club' ? state.vote.club : state.vote[c]) : (c === 'club' ? state.candidature.club : state.candidature[c]);
      if (sess && sess.active) {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c.toUpperCase();
        closeSessionCategory.appendChild(opt);
      }
    });
  }

  function openCloseSession(type) {
    closeType = type;
    fillCloseOptions(type);
    if (closeSessionCategory.children.length === 1) { // only placeholder
      alert('Aucune session ouverte à fermer.');
      return;
    }
    closeSessionCategory.value = '';
    closeSessionModal.style.display = 'flex';
  }

  window.openCloseSession = openCloseSession;

  function resetVoteModal() {
    step1.style.display = 'block';
    step2.style.display = 'none';
    voteType.value = '';
    startVoteInput.value = '';
    endVoteInput.value = '';
  }

  function resetCandModal() {
    candStep1.style.display = 'block';
    candStep2.style.display = 'none';
    candType.value = '';
    startCandDate.value = '';
    endCandDate.value = '';
  }

  window.resetVoteModal = resetVoteModal;
  window.resetCandModal = resetCandModal;

  closeStartVotes.onclick = () => { startVotesModal.style.display = 'none'; resetVoteModal(); };
  cancelVoteModal.onclick = () => { startVotesModal.style.display = 'none'; resetVoteModal(); };
  nextToDates.onclick = () => {
    if (!voteType.value) { alert('Choisissez un type'); return; }
    step1.style.display = 'none';
    step2.style.display = 'block';
  };
  validateVoteModal.onclick = () => {
    const categorie = voteType.value;
    const debut = Date.parse(startVoteInput.value);
    const fin = Date.parse(endVoteInput.value);
    if (!categorie) { alert('Type manquant'); return; }
    if (isNaN(debut) || isNaN(fin) || debut >= fin) { alert('Dates invalides'); return; }
    if (window.isVoteActive && window.isVoteActive(categorie)) { alert('Une session de vote est déjà ouverte pour cette catégorie'); return; }
    const candidatures = JSON.parse(localStorage.getItem('candidatures') || '[]');
    const candidats = candidatures.filter(c => c.type && c.type.toLowerCase() === categorie);
    if (candidats.length === 0) {
      alert('Impossible de démarrer le vote : aucun candidat pour cette catégorie.');
      return;
    }
    if (window.isCandidatureActive && window.isCandidatureActive(categorie)) {
      alert('Impossible de démarrer le vote : la session de candidature pour cette catégorie est encore ouverte.');
      return;
    }
    if (window.startVote) {
      window.startVote(categorie, debut, fin);
      alert('Votes démarrés pour ' + categorie.toUpperCase());
      startVotesModal.style.display = 'none';
      resetVoteModal();
    } else {
      alert("Fonction de démarrage de vote non disponible.");
    }
  };

  closeStartCand.onclick = () => { startCandModal.style.display = 'none'; resetCandModal(); };
  cancelCandModal.onclick = () => { startCandModal.style.display = 'none'; resetCandModal(); };
  candNextToDate.onclick = () => {
    if (!candType.value) { alert('Choisissez une catégorie'); return; }
    candStep1.style.display = 'none';
    candStep2.style.display = 'block';
  };
  validateCandModal.onclick = () => {
    const categorie = candType.value;
    const debut = Date.parse(startCandDate.value);
    const fin = Date.parse(endCandDate.value);
    if (!categorie) { alert('Catégorie manquante'); return; }
    if (isNaN(debut) || isNaN(fin) || debut >= fin) { alert('Dates invalides'); return; }
    if (isCandidatureSessionActive(categorie)) { alert('Cette catégorie possède déjà une session active'); return; }
    window.startCandidature(categorie, debut, fin);
    alert('Candidatures ouvertes pour ' + categorie.toUpperCase());
    startCandModal.style.display = 'none';
    resetCandModal();
  };

  closeCloseSession.onclick = () => { closeSessionModal.style.display = 'none'; };
  cancelCloseSession.onclick = () => { closeSessionModal.style.display = 'none'; };
  validateCloseSession.onclick = () => {
    const cat = closeSessionCategory.value;
    if (!cat) { alert('Choisissez une catégorie'); return; }
    if (closeType === 'vote') {
      if (!isVoteActive(cat)) { alert('Pas de session de vote ouverte pour cette catégorie'); return; }
      window.endVote(cat);
      alert('Votes fermés pour ' + cat.toUpperCase());
    } else {
      if (!isCandidatureSessionActive(cat)) { alert('Pas de session ouverte pour cette catégorie'); return; }
      endCandidatureSession(cat);
      alert('Candidatures fermées pour ' + cat.toUpperCase());
    }
    closeSessionModal.style.display = 'none';
  };
}

function isCandidatureSessionActive(categorie) {
  return window.isCandidatureActive(categorie);
}

function startCandidatureSession(categorie, debut, fin) {
  window.startCandidature(categorie, debut, fin);
}

function endCandidatureSession(categorie) {
  window.endCandidature(categorie);
}

function isVoteActive(categorie) {
  return window.isVoteActive(categorie);
}
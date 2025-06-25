const defaultState = {
  candidature: {
    aes: { active: false, startTime: null, endTime: null },
    classe: { active: false, startTime: null, endTime: null },
    club: { active: false, club: null, startTime: null, endTime: null }
  },
  vote: {
    aes: { active: false, startTime: null, endTime: null },
    classe: { active: false, startTime: null, endTime: null },
    club: { active: false, club: null, startTime: null, endTime: null }
  }
};

function loadState() {
  const data = localStorage.getItem('electionState');
  return data ? JSON.parse(data) : JSON.parse(JSON.stringify(defaultState));
}

function saveState(state) {
  localStorage.setItem('electionState', JSON.stringify(state));
  // Notifie les autres scripts qu'un changement a été effectué
  document.dispatchEvent(new Event('stateChanged'));
}
function getState() {
  const state = loadState();
  let changed = false;

  // Vérifie l'expiration des candidatures et des votes pour chaque catégorie
  ['aes', 'classe'].forEach(cat => {
    const cand = state.candidature[cat];
    if (cand && cand.active && Date.now() > cand.endTime) {
      cand.active = false;
      changed = true;
    }
    const vot = state.vote[cat];
    if (vot && vot.active && Date.now() > vot.endTime) {
      vot.active = false;
      changed = true;
    }
  });

  if (state.candidature.club && state.candidature.club.active && Date.now() > state.candidature.club.endTime) {
    state.candidature.club.active = false;
    state.candidature.club.club = null;
    changed = true;
  }

  if (state.vote.club && state.vote.club.active && Date.now() > state.vote.club.endTime) {
    state.vote.club.active = false;
    state.vote.club.club = null;
    changed = true;
  }

  if (changed) saveState(state);
  return state;
}


// --- Candidatures ---
function isCandidatureActive(category) {
  const s = getState();
  if (!category) return false;
  const c = category === 'club' ? s.candidature.club : s.candidature[category];
  if (!c) return false;
  return c.active && Date.now() >= c.startTime && Date.now() < c.endTime;
}

function startCandidature(category, startTime, endTime, club = null) {
  const state = getState();
  if (category === 'club') {
    state.candidature.club = { active: true, club, startTime, endTime };
  } else if (category === 'aes' || category === 'classe') {
    state.candidature[category] = { active: true, startTime, endTime };
  }
  saveState(state);
}

function endCandidature(category) {
  const state = getState();
  if (category === 'club') {
    state.candidature.club.active = false;
    state.candidature.club.club = null;
  } else if (category === 'aes' || category === 'classe') {
    state.candidature[category].active = false;
  }
  saveState(state);
}

// --- Votes ---
function isVoteActive(category) {
  const s = getState();
  if (!category) return false;

  const v = category === 'club' ? s.vote.club : s.vote[category];
  if (!v) return false;
  return v.active && Date.now() >= v.startTime && Date.now() < v.endTime;
}

function startVote(category, startTime, endTime, club = null) {
  const state = getState();
  if (category === 'club') {
    state.vote.club = { active: true, club, startTime, endTime };
  } else if (category === 'aes' || category === 'classe') {
    state.vote[category] = { active: true, startTime, endTime };
  }
  saveState(state);
}

function endVote(category) {
  const state = getState();
  if (category === 'club') {
    state.vote.club.active = false;
    state.vote.club.club = null;
  } else if (category === 'aes' || category === 'classe') {
    state.vote[category].active = false;
  }
  saveState(state);
}

// --- Fermeture automatique ---
function autoCloseSessions() {
  const state = getState();
  let changed = false;
  // Candidatures & votes
  ['aes', 'classe'].forEach(cat => {
    const c = state.candidature[cat];
    if (c && c.active && Date.now() > c.endTime) {
      c.active = false;
      changed = true;
    }
    const v = state.vote[cat];
    if (v && v.active && Date.now() > v.endTime) {
      v.active = false;
      changed = true;
    }
  });

  if (state.candidature.club && state.candidature.club.active && Date.now() > state.candidature.club.endTime) {
    state.candidature.club.active = false;
    state.candidature.club.club = null;
    changed = true;
  }
  if (state.vote.club && state.vote.club.active && Date.now() > state.vote.club.endTime) {
    state.vote.club.active = false;
    state.vote.club.club = null;
    changed = true;
  }
  if (changed) saveState(state);
}

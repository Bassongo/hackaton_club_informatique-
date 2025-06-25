// Stockage des candidatures (simulation d'une base de données)
let candidatures = JSON.parse(localStorage.getItem('candidatures') || '[]');

document.addEventListener("DOMContentLoaded", function () {
  // Éléments de la page
  const accueilBtn = document.getElementById("accueilBtn");
  const candidatBtn = document.getElementById("candidatBtn");
  const accueilPage = document.getElementById("accueil");
  const candidatPage = document.getElementById("candidatPage");
  const mesCandidaturesPage = document.getElementById("mesCandidaturesPage");
  const candidatureForm = document.getElementById("candidatureForm");
  const candidaturesList = document.getElementById("candidaturesList");

  // Boutons de filtrage des élections
  const aesBtn = document.getElementById("aesBtn");
  const clubBtn = document.getElementById("clubBtn");
  const classeBtn = document.getElementById("classeBtn");
  const candidaterBtn = document.getElementById("candidaterBtn");

  // Boutons d'actions de candidature
  const mesCandidaturesBtn = document.getElementById("mesCandidaturesBtn");
  const validerCandidatureBtn = document.getElementById("validerCandidatureBtn");
  const retourCandidatureBtn = document.getElementById("retourCandidatureBtn");
  const retourDepuisMesCandidaturesBtn = document.getElementById("retourDepuisMesCandidaturesBtn");

  // Gestion de la navigation
  if (accueilBtn) {
    accueilBtn.addEventListener("click", function() {
      showPage("accueil");
    });
  }

  if (candidatBtn) {
    candidatBtn.addEventListener("click", function() {
      showPage("candidat");
    });
  }

  // Gestion du bouton "Mes candidatures" dans le formulaire
  if (mesCandidaturesBtn) {
    mesCandidaturesBtn.addEventListener("click", function() {
      showPage("mesCandidatures");
      displayCandidatures();
    });
  }

  // Bouton de retour depuis la page "Mes candidatures"
  if (retourDepuisMesCandidaturesBtn) {
    retourDepuisMesCandidaturesBtn.addEventListener("click", function() {
      showPage("candidat");
    });
  }

  // Fonction pour afficher une page spécifique
  function showPage(page) {
    accueilPage.style.display = "none";
    candidatPage.style.display = "none";
    mesCandidaturesPage.style.display = "none";

    if (page === "accueil") {
      accueilPage.style.display = "block";
      candidatPage.style.display = "none";
      mesCandidaturesPage.style.display = "none";
    } else if (page === "candidat") {
      candidatPage.style.display = "block";
      accueilPage.style.display = "none";
      mesCandidaturesPage.style.display = "none";
    } else if (page === "mesCandidatures") {
      mesCandidaturesPage.style.display = "block";
      candidatPage.style.display = "none";
      accueilPage.style.display = "none";
    }
  }

  // Gestion des boutons de filtrage
  if (aesBtn) {
    aesBtn.addEventListener("click", function() {
      showCandidatureForm("AES");
    });
  }

  if (clubBtn) {
    clubBtn.addEventListener("click", function() {
      showCandidatureForm("CLUB");
    });
  }

  if (classeBtn) {
    classeBtn.addEventListener("click", function() {
      showCandidatureForm("CLASSE");
    });
  }

  if (candidaterBtn) {
    candidaterBtn.addEventListener("click", function() {
      alert("Veuillez d'abord sélectionner un type d'élection (AES, CLUB ou CLASSE)");
    });
  }

  // Boutons d'actions
  if (validerCandidatureBtn) {
    validerCandidatureBtn.addEventListener("click", function() {
      if (validateCandidatureForm()) {
        saveCandidature();
        alert("Candidature soumise avec succès!");
        resetCandidatureForm();
        // Rediriger vers "Mes candidatures" après soumission
        showPage("mesCandidatures");
        displayCandidatures();
      }
    });
  }

  if (retourCandidatureBtn) {
    retourCandidatureBtn.addEventListener("click", function() {
      resetCandidatureForm();
    });
  }

  // Fonction pour afficher le formulaire de candidature
  function showCandidatureForm(type) {
    document.getElementById("electionType").value = type;
    candidatureForm.style.display = "block";
  }

  // Fonction pour valider le formulaire
  function validateCandidatureForm() {
    const nom = document.getElementById("nom").value;
    const prenom = document.getElementById("prenom").value;
    const classe = document.getElementById("classe").value;
    const poste = document.getElementById("poste").value;
    const programme = document.getElementById("programme").value;

    if (!nom || !prenom || !classe || !poste || !programme) {
      alert("Veuillez remplir tous les champs obligatoires");
      return false;
    }
    return true;
  }

  // Fonction pour sauvegarder la candidature
  function saveCandidature() {
    const photoInput = document.getElementById("photo");
    let photoUrl = "";

    if (photoInput.files && photoInput.files[0]) {
      // Dans un environnement réel, il faudrait uploader l'image vers un serveur
      // Ici on utilise un URL temporaire pour la démonstration
      photoUrl = URL.createObjectURL(photoInput.files[0]);
    }

    const newCandidature = {
      id: Date.now(),
      type: document.getElementById("electionType").value,
      nom: document.getElementById("nom").value,
      prenom: document.getElementById("prenom").value,
      classe: document.getElementById("classe").value,
      poste: document.getElementById("poste").value,
      programme: document.getElementById("programme").value,
      photo: photoUrl,
      date: new Date().toLocaleDateString()
    };

    candidatures.push(newCandidature);
    localStorage.setItem('candidatures', JSON.stringify(candidatures));
  }

  // Fonction pour réinitialiser le formulaire
  function resetCandidatureForm() {
    document.getElementById("newCandidature").reset();
    candidatureForm.style.display = "none";
  }

  // Fonction pour afficher les candidatures
  function displayCandidatures() {
    candidaturesList.innerHTML = "";

    if (candidatures.length === 0) {
      candidaturesList.innerHTML = "<p>Aucune candidature soumise pour le moment.</p>";
      return;
    }

    candidatures.forEach(candidature => {
      const card = document.createElement("div");
      card.className = "candidature-card";
      card.innerHTML = `
        ${candidature.photo ? `<img src="${candidature.photo}" class="candidature-photo" alt="Photo de profil">` : ''}
        <div class="candidature-title">${candidature.prenom} ${candidature.nom}</div>
        <div class="candidature-type">${candidature.type} - ${candidature.poste || 'Non spécifié'}</div>
        <div class="candidature-details">${candidature.classe} • ${candidature.date}</div>
        <div class="candidature-programme">${candidature.programme}</div>
      `;

      card.addEventListener("click", () => showCandidatureDetail(candidature));
      candidaturesList.appendChild(card);
    });
  }

  // Fonction pour afficher les détails d'une candidature
  function showCandidatureDetail(candidature) {
    document.getElementById("detailNom").textContent = `${candidature.prenom} ${candidature.nom}`;
    document.getElementById("detailType").textContent = `${candidature.type} - ${candidature.poste || 'Non spécifié'}`;
    document.getElementById("detailClasse").textContent = candidature.classe;
    document.getElementById("detailProgramme").textContent = candidature.programme;

    const detailPhoto = document.getElementById("detailPhoto");
    if (candidature.photo) {
      detailPhoto.src = candidature.photo;
      detailPhoto.style.display = "block";
    } else {
      detailPhoto.style.display = "none";
    }

    document.getElementById("candidatureDetailModal").style.display = "flex";
  }

  // Fermer le modal de détails
  const closeDetailModal = document.getElementById("closeDetailModal");
  if (closeDetailModal) {
    closeDetailModal.addEventListener("click", function() {
      const detailModal = document.getElementById("candidatureDetailModal");
      if (detailModal) detailModal.style.display = "none";
    });
  }

  // Gestion du modal de connexion
  const loginBtn = document.getElementById("loginBtn");
  const loginModal = document.getElementById("loginModal");
  const closeLogin = document.getElementById("closeLogin");
  const cancelLogin = document.getElementById("cancelLogin");

  if (loginBtn && loginModal) {
    loginBtn.addEventListener("click", function() {
      loginModal.style.display = "flex";
    });
  }

  if (closeLogin && loginModal) {
    closeLogin.addEventListener("click", function() {
      loginModal.style.display = "none";
    });
  }

  if (cancelLogin && loginModal) {
    cancelLogin.addEventListener("click", function() {
      loginModal.style.display = "none";
    });
  }

  window.addEventListener("click", function(event) {
    if (loginModal && event.target === loginModal) {
      loginModal.style.display = "none";
    }
    const detailModal = document.getElementById("candidatureDetailModal");
    if (detailModal && event.target === detailModal) {
      detailModal.style.display = "none";
    }
  });

  document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();
    alert("Connexion réussie!");
    loginModal.style.display = "none";
  });

  // Initialiser la page d'accueil au chargement
  showPage("accueil");
});

// Gestion de la connexion sur la page login.html
document.addEventListener('DOMContentLoaded', function() {
  const loginForm = document.getElementById('loginForm');
  // Vérifie qu'on est bien sur la page login.html
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const email = document.getElementById('login-identifiant').value.trim();
      const password = document.getElementById('login-password').value;

      // Vérifie les utilisateurs enregistrés
      const users = JSON.parse(localStorage.getItem('utilisateurs') || '[]');
      const user = users.find(u => u.email === email && atob(u.password) === password);

      if (user) {
        // Connexion réussie, sauvegarde de l'utilisateur actif et redirection
        localStorage.setItem('currentUser', JSON.stringify(user));
        window.location.href = 'accueil.html';
      } else {
        alert('Identifiants invalides');
      }
    });
  }
});
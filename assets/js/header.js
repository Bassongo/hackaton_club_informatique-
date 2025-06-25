function initHeader() {
  // Activation onglet courant
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function (e) {
      if (this.classList.contains('disabled-link')) {
        e.preventDefault();
        alert("Cette page est désactivée pour le moment.");
        return;
      }
      document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Menu mobile toggle
  const menuToggle = document.querySelector('.menu-toggle');
  const navMenu = document.querySelector('.nav-menu');

  if (menuToggle && navMenu) {
    menuToggle.addEventListener('click', function () {
      menuToggle.classList.toggle('active');
      navMenu.classList.toggle('active');
    });
  }

  // Dropdown menus
  document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      const parent = this.closest('.dropdown');
      parent.classList.toggle('show');
    });
  });

  // Fermer les dropdowns en cliquant ailleurs
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.dropdown').forEach(dropdown => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });
  });

  // Panel de profil
  const profileBtn = document.getElementById('profileBtn');
  const panel = document.getElementById('profilePanel');
  const panelBg = document.getElementById('profilePanelBg');
  const closePanelBtn = document.getElementById('closeProfilePanel');

  function renderProfile() {
    const info = document.getElementById('profilePanelInfo');
    const actions = document.getElementById('panelActions');
    const dateEl = document.getElementById('panelCreationDate');
    
    if (!info || !actions || !dateEl) return;

    // Récupérer les données utilisateur depuis les variables PHP
    const userData = window.currentUser || {};
    
    if (!userData.id) {
      info.innerHTML = '<p>Aucun utilisateur connecté.</p>';
      actions.innerHTML = '';
      dateEl.textContent = '';
      return;
    }

    // Construction du profil
    info.innerHTML = `
      <div class="profile-block">
        <p class="profile-username"><strong>@</strong>${userData.email || ''}</p>
        ${userData.photo ? `<img src="../uploads/${userData.photo}" class="profile-photo" alt="photo">` : ''}
        <p class="profile-nom">${userData.nom || ''}</p>
        <p class="profile-prenom">${userData.prenom || ''}</p>
        <p class="profile-classe">${userData.classe || ''}</p>
        <p class="profile-role">${userData.role || ''}</p>
      </div>
    `;

    // Actions du profil
    let html = '';
    
    // Si l'utilisateur est membre d'un comité, afficher les actions de comité
    if (userData.isCommitteeMember) {
      html += `<div class="committee-section" style="text-align:center;margin:1.5rem 0;">
        <a href="role_comite.php" class="admin-btn">Gérer les candidatures</a>
      </div>`;
    }

    // Actions générales
    html += `
      <div class="profile-actions-bottom">
        <button class="admin-btn" id="editProfileBtn">Modifier mes infos</button>
        <button class="admin-btn" id="changePwdBtn">Changer mon mot de passe</button>
      </div>
    `;

    actions.innerHTML = html;

    // Date d'inscription (si disponible)
    dateEl.innerHTML = userData.date_inscription ? 
      `Inscrit depuis : ${new Date(userData.date_inscription).toLocaleDateString()}` : '';

    // Gestionnaires d'événements pour les boutons
    document.getElementById('editProfileBtn')?.addEventListener('click', () => {
      alert('Fonctionnalité à implémenter : Modifier mes infos');
    });

    document.getElementById('changePwdBtn')?.addEventListener('click', () => {
      alert('Fonctionnalité à implémenter : Changer mon mot de passe');
    });
  }

  function openPanel() {
    renderProfile();
    if (panel) panel.classList.add('open');
    if (panelBg) panelBg.classList.add('open');
  }

  function closePanel() {
    if (panel) panel.classList.remove('open');
    if (panelBg) panelBg.classList.remove('open');
  }

  // Événements du panel
  if (profileBtn) profileBtn.addEventListener('click', e => { 
    e.preventDefault(); 
    openPanel(); 
  });
  
  if (closePanelBtn) closePanelBtn.addEventListener('click', closePanel);
  if (panelBg) panelBg.addEventListener('click', closePanel);

  // Fermer le panel avec la touche Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closePanel();
    }
  });

  // Gestion responsive du menu mobile
  function handleResize() {
    if (window.innerWidth > 900) {
      navMenu?.classList.remove('active');
      menuToggle?.classList.remove('active');
    }
  }

  window.addEventListener('resize', handleResize);

  // Initialisation des liens actifs
  function setActiveLink() {
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop();
    
    document.querySelectorAll('.nav-link').forEach(link => {
      const href = link.getAttribute('href');
      if (href && href.includes(currentPage)) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  // Appeler setActiveLink au chargement
  setActiveLink();
}

// Initialisation
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHeader);
} else {
  initHeader();
}

document.addEventListener('DOMContentLoaded', () => {
  const list = document.getElementById('candidaturesList');
  const candidatures = JSON.parse(localStorage.getItem('candidatures') || '[]');

  if (candidatures.length === 0) {
    list.innerHTML = '<p>Aucune candidature trouvée</p>';
    return;
  }

  candidatures.forEach(c => {
    const card = document.createElement('div');
    card.className = 'candidature-card';
    card.innerHTML = `
      ${c.photo ? `<img src="${c.photo}" class="candidature-photo">` : ''}
      <div class="candidature-title">${c.prenom} ${c.nom}</div>
      <div class="candidature-type">${c.type}${c.club ? ' - ' + c.club : ''} - ${c.poste}</div>
      <div class="candidature-details">${c.classe} • ${c.date}</div>
      <div class="candidature-programme">${c.programme}</div>
    `;
    list.appendChild(card);
  });
});

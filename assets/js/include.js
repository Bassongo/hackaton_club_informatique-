// assets/js/include.js

// Fonction pour inclure un composant HTML dans un élément du DOM (comme un header ou un footer)
function includeComponent(selector, url) {

    // fetch() permet de charger le contenu du fichier spécifié par l'URL
    fetch(url)
        .then(response => response.text()) // Une fois le fichier récupéré, on le transforme en texte HTML
        .then(data => {
            const container = document.querySelector(selector);
            if (!container) return;
            container.innerHTML = data;

            // Exécute les balises script insérées dynamiquement
            container.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.head.appendChild(newScript);
                oldScript.remove();
            });
        })
        .catch(error => {
            console.error(`Erreur lors du chargement de ${url} :`, error);
            const container = document.querySelector(selector);
            if (container) {
                container.innerHTML = `<div class="error-message">Impossible de charger ${url}</div>`;
            }
        });
}

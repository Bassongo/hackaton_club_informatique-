document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const userData = {
            email: document.getElementById('reg-email').value.trim(),
            username: document.getElementById('reg-username').value.trim(),
            password: document.getElementById('reg-password').value,
            classe: document.getElementById('reg-classe').value,
            role: 'electeur',
            inscritDepuis: new Date().toISOString()
        };

        if (validateForm(userData)) {
            if (registerUser(userData)) {
                showSuccess('Inscription réussie ! Redirection...');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            }
        }
    });

    function validateForm(data) {
        // Vérification mot de passe
        const confirmPassword = document.getElementById('reg-confirm-password').value;
        if (data.password !== confirmPassword) {
            showError('Les mots de passe ne correspondent pas');
            return false;
        }

        // Vérification champs requis
        const requiredFields = ['email', 'username', 'password', 'classe'];
        if (!requiredFields.every(field => data[field])) {
            showError('Tous les champs sont obligatoires');
            return false;
        }

        // Vérification format email
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            showError('Format email invalide');
            return false;
        }

        return true;
    }

    function registerUser(userData) {
        const users = JSON.parse(localStorage.getItem('utilisateurs') || '[]');
        
        // Vérifier si l'utilisateur existe déjà
        if (users.some(u => u.email === userData.email)) {
            showError('Cet email est déjà enregistré');
            return false;
        }

        // Hasher le mot de passe (version simplifiée)
        userData.password = btoa(userData.password); // À remplacer par bcrypt en production
        users.push(userData);
        localStorage.setItem('utilisateurs', JSON.stringify(users));
        
        return true;
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        const formActions = form.querySelector('.form-actions');
        const existingError = form.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        formActions.after(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }

    function showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;

        const formActions = form.querySelector('.form-actions');
        const existingSuccess = form.querySelector('.success-message');
        if (existingSuccess) existingSuccess.remove();

        formActions.after(successDiv);
        setTimeout(() => successDiv.remove(), 5000);
    }
});
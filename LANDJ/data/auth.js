document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('.login-form');

    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevenir el envío del formulario por defecto

            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            const username = usernameInput.value;
            const password = passwordInput.value;

            try {
                const response = await fetch('./data/db.json');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const db = await response.json();
                const users = db.users;

                const user = users.find(u => u.username === username && u.password === password);

                if (user) {
                    // Guardar el nombre de usuario en localStorage
                    localStorage.setItem('pcwp_username', username);
                    
                    alert('Inicio de sesión exitoso!');
                    window.location.href = 'cpanel.html'; // Redirigir al cPanel
                } else {
                    alert('Nombre de usuario o contraseña incorrectos.');
                }
            } catch (error) {
                console.error('Error al cargar la base de datos o al iniciar sesión:', error);
                alert('Ocurrió un error al intentar iniciar sesión. Por favor, inténtalo de nuevo más tarde.');
            }
        });
    }
});
// public/js/login_success.js
(function () {
    try {
        // If server-side session contains user info, we can optionally fetch it via AJAX.
        // Simpler: request minimal info by calling an endpoint or using inline data.
        // Here we attempt to set from a fetch to /api/me (optional). If not available, skip.
    } catch (e) {
        // ignore
    }

    // If you prefer to set from session via an API:
    // fetch('/api/me').then(r=>r.json()).then(data => { ... })
    // For now, try to just redirect to list_users.php.
    try {
        // Optionally clean previous storage keys
        // localStorage.removeItem('login_message'); // keep if you want
        localStorage.setItem('login_message', 'Login successful');
    } catch (e) {
        // storage not available or blocked
    }

    // final redirect
    window.location.href = '/list_users.php';
})();

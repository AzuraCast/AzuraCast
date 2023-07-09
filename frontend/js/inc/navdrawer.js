(() => {
    window.addEventListener('DOMContentLoaded', () => {
        document.querySelector('#navbar-toggle').addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelector('#sidebar').classList.toggle('show');
        });
    });
})();

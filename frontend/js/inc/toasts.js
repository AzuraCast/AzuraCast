(() => {
    window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.toast-notification').forEach((el) => {
            const toast = new bootstrap.Toast(el);
            toast.show();
        });
    });
})();

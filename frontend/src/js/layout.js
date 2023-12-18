import '~/scss/style.scss';

import * as bootstrap from 'bootstrap';

const ready = (callback) => {
    if (document.readyState !== "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
}

// Theme setting
const getStoredTheme = () => localStorage.getItem('theme');

const getPreferredTheme = () => {
    const storedTheme = getStoredTheme();
    if (storedTheme) {
        return storedTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

const currentTheme = document.documentElement.getAttribute('data-bs-theme');
if (currentTheme !== 'light' && currentTheme !== 'dark') {
    document.documentElement.setAttribute('data-bs-theme', getPreferredTheme());
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme();
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
        document.documentElement.setAttribute('data-bs-theme', getPreferredTheme());
    }
});

ready(() => {
    // Toasts
    document.querySelectorAll('.toast-notification').forEach((el) => {
        const toast = new bootstrap.Toast(el);
        toast.show();
    });
});

export default bootstrap;

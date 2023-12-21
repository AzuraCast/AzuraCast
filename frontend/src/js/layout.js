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

    // If in a frame, notify the parent frame of the frame dimensions.
    if (window.self !== window.top) {
        let docHeight = 0;
        let docWidth = 0;

        const postSizeToParent = () => {
            if (document.body.scrollHeight !== docHeight || document.body.scrollWidth !== docWidth) {
                docHeight = document.body.scrollHeight;
                docWidth = document.body.scrollWidth;

                const message = {height: docHeight, width: docWidth};
                window.top.postMessage(message, "*");
            }
        }

        postSizeToParent();
        document.addEventListener("vue-ready", postSizeToParent);

        const mainElem = document.querySelector('main');
        const resizeObserver = new ResizeObserver(postSizeToParent);
        resizeObserver.observe(mainElem);
    }
});

export default bootstrap;

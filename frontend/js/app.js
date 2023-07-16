const ready = (callback) => {
    if (document.readyState !== "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
}

// Theme setting
const getStoredTheme = () => localStorage.getItem('theme')
const setStoredTheme = theme => localStorage.setItem('theme', theme)

const getPreferredTheme = () => {
    const storedTheme = getStoredTheme()
    if (storedTheme) {
        return storedTheme
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

const setTheme = theme => {
    document.documentElement.setAttribute('data-bs-theme', theme);

    document.documentElement.dispatchEvent(new CustomEvent(
        "theme-change",
        {
            detail: theme
        }
    ));
}

const currentTheme = document.documentElement.getAttribute('data-bs-theme');
if (currentTheme !== 'light' && currentTheme !== 'dark') {
    setTheme(getPreferredTheme());
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const storedTheme = getStoredTheme()
    if (storedTheme !== 'light' && storedTheme !== 'dark') {
        setTheme(getPreferredTheme());
    }
});

ready(() => {
    // Theme switcher
    document.querySelectorAll('.theme-switcher').forEach(
        toggle => {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();

                const currentTheme = getPreferredTheme();
                if (currentTheme === 'light') {
                    setStoredTheme('dark');
                    setTheme('dark');
                } else {
                    setStoredTheme('light');
                    setTheme('light');
                }
            });
        });

    // Toasts
    document.querySelectorAll('.toast-notification').forEach((el) => {
        const toast = new bootstrap.Toast(el);
        toast.show();
    });
});

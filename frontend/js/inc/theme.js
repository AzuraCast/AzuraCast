/*!
 * Color mode toggler for Bootstrap's docs (https://getbootstrap.com/)
 * Copyright 2011-2023 The Bootstrap Authors
 * Licensed under the Creative Commons Attribution 3.0 Unported License.
 */

(() => {
    'use strict'

    const getStoredTheme = () => localStorage.getItem('theme')
    const setStoredTheme = theme => localStorage.setItem('theme', theme)

    const getPreferredTheme = () => {
        const storedTheme = getStoredTheme()
        if (storedTheme) {
            return storedTheme
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = theme => {
        document.documentElement.setAttribute('data-theme', theme)

        document.documentElement.dispatchEvent(new CustomEvent(
            "theme-change",
            {
                detail: theme
            }
        ));
    }

    const currentTheme = document.documentElement.getAttribute('data-theme');
    if (currentTheme !== 'light' && currentTheme !== 'dark') {
        setTheme(getPreferredTheme())
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const storedTheme = getStoredTheme()
        if (storedTheme !== 'light' && storedTheme !== 'dark') {
            setTheme(getPreferredTheme())
        }
    })

    window.addEventListener('DOMContentLoaded', () => {
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
    })
})()

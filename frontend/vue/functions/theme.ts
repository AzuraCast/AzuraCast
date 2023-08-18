import {computed, ComputedRef, onMounted, ref} from "vue";
import {useLocalStorage} from "@vueuse/core";

export enum Theme {
    Light = 'light',
    Dark = 'dark'
}

export default function useTheme() {
    const page: HTMLElement = document.documentElement;
    const currentTheme: Theme | null = ref(null);

    onMounted((): void => {
        currentTheme.value = page.getAttribute('data-bs-theme');
    });

    const storedTheme = useLocalStorage('theme', null);

    const getPreferredTheme = (): Theme => {
        return (storedTheme.value)
            ? storedTheme.value
            : window.matchMedia('(prefers-color-scheme: dark)').matches ? Theme.Dark : Theme.Light;
    }

    const setTheme = (newTheme: Theme): void => {
        page.setAttribute('data-bs-theme', newTheme);
        currentTheme.value = newTheme;
        storedTheme.value = newTheme;
    };

    const toggleTheme = (): void => {
        const preferredTheme: Theme = getPreferredTheme();

        if (preferredTheme === Theme.Light) {
            setTheme(Theme.Dark);
        } else {
            setTheme(Theme.Light);
        }
    };

    const isDark: ComputedRef<boolean> = computed((): boolean => currentTheme.value === Theme.Dark);
    const isLight: ComputedRef<boolean> = computed((): boolean => currentTheme.value === Theme.Light);

    return {
        theme: currentTheme,
        isDark,
        isLight,
        setTheme,
        toggleTheme
    };
}

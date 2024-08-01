import {computed, ComputedRef, onMounted, Ref, ref} from "vue";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";
import {createGlobalState} from "@vueuse/core";

export enum Theme {
    Light = 'light',
    Dark = 'dark'
}

export default createGlobalState(
    () => {
        const page: HTMLElement = document.documentElement;
        const currentTheme: Ref<Theme | string | null> = ref<Theme | string | null>(null);

        onMounted((): void => {
            currentTheme.value = page.getAttribute('data-bs-theme');
        });

        const storedTheme = useOptionalStorage('theme', null);

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
            currentTheme,
            isDark,
            isLight,
            setTheme,
            toggleTheme
        };
    }
);

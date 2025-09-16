import {computed, ComputedRef, onMounted, ref} from "vue";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";
import {defineStore} from "pinia";

type Theme = 'light' | 'dark'

export const useTheme = defineStore(
    'global-theme',
    () => {
        const page: HTMLElement = document.documentElement;
        const currentTheme = ref<Theme | null>(null);

        onMounted((): void => {
            currentTheme.value = page.getAttribute('data-bs-theme') as Theme;
        });

        const storedTheme = useOptionalStorage<Theme | null>('theme', null);

        const getPreferredTheme = (): Theme => {
            return (storedTheme.value)
                ? storedTheme.value
                : window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        const setTheme = (newTheme: Theme): void => {
            page.setAttribute('data-bs-theme', newTheme);
            currentTheme.value = newTheme;
            storedTheme.value = newTheme;
        };

        const toggleTheme = (): void => {
            const preferredTheme: Theme = getPreferredTheme();

            if (preferredTheme === 'light') {
                setTheme('dark');
            } else {
                setTheme('light');
            }
        };

        const isDark: ComputedRef<boolean> = computed((): boolean => currentTheme.value === 'dark');

        const isLight: ComputedRef<boolean> = computed((): boolean => currentTheme.value === 'light');

        return {
            currentTheme,
            isDark,
            isLight,
            setTheme,
            toggleTheme
        };
    }
);

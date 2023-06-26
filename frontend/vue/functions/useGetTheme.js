import {computed, onMounted, ref} from "vue";
import {useEventListener} from "@vueuse/core";

export default function useGetTheme() {
    const htmlElement = document.documentElement;
    const theme = ref(null);

    onMounted(() => {
        theme.value = htmlElement.getAttribute('data-theme');
    });

    useEventListener(htmlElement, 'theme-change', (evt) => {
        theme.value = evt.detail;
    });

    const isDark = computed(() => theme.value === 'dark');
    const isLight = computed(() => theme.value === 'light');

    return {
        theme,
        isDark,
        isLight
    };
}

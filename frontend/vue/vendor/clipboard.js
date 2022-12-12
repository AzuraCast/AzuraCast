import VueClipboard, {copyText} from 'vue3-clipboard';

export function copyToClipboard(text) {
    copyText(
        text,
        undefined,
        (error) => {
            if (error) {
                console.error(error)
            }
        }
    );
}

export default function useVueClipboard(vueApp) {
    vueApp.use(
        VueClipboard,
        {
            autoSetContainer: true,
            appendToBody: true,
        }
    );
};

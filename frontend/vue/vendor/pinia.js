import {createPinia} from 'pinia';

const pinia = createPinia();

export default function usePinia(vueApp) {
    vueApp.use(pinia);
}

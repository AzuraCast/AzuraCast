import {createPinia} from 'pinia';

const pinia = createPinia();

export function installPinia(vueApp) {
    vueApp.use(pinia);
}

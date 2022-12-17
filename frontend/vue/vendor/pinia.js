import {createPinia} from 'pinia';

const pinia = createPinia();

export default function installPinia(vueApp) {
    vueApp.use(pinia);
}

import {createPinia, Pinia} from 'pinia';
import { App } from 'vue';

const pinia: Pinia = createPinia();

export function installPinia(vueApp: App): void {
    vueApp.use(pinia);
}

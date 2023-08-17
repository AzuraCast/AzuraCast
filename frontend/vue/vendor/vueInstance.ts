import {App} from "vue";

export let currentVueInstance: App;

export const installCurrentVueInstance = (vueApp: App): void => {
    currentVueInstance = vueApp;
};

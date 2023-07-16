export let currentVueInstance;

export const installCurrentVueInstance = (vueApp) => {
    currentVueInstance = vueApp;
};

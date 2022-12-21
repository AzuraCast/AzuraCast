import axios from "axios";
import VueAxios from "vue-axios";
import {inject} from "vue";
import useAzuraCast from "~/vendor/azuracast";

/* Composition API Axios utilities */
export function useAxios() {
    return {
        axios: inject('axios')
    };
}

export default function installAxios(vueApp) {
    // Configure auto-CSRF on requests
    const {apiCsrf} = useAzuraCast();

    if (typeof apiCsrf !== 'undefined') {
        axios.defaults.headers.common['X-API-CSRF'] = apiCsrf;
    }

    vueApp.use(VueAxios, axios);
    
    vueApp.provide('axios', axios);
}

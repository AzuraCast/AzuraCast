import initBase from '~/base.js';
import {createApp} from "vue";
import useSweetAlert from '~/vendor/sweetalert.js';
import useBootstrapVue from '~/vendor/bootstrapVue.js';

import AdminApiKeys from '~/components/Admin/ApiKeys.vue';

const vueApp = createApp(AdminApiKeys);

useSweetAlert(vueApp);
useBootstrapVue(vueApp);

export default initBase(vueApp);

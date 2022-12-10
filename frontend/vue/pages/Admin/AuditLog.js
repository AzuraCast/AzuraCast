import initBase from '~/base.js';
import {createApp} from "vue";
import useBootstrapVue from '~/vendor/bootstrapVue.js';
import '~/vendor/luxon.js';

import AuditLog from '~/components/Admin/AuditLog.vue';

const vueApp = createApp(AuditLog);

useBootstrapVue(vueApp);

export default initBase(vueApp);

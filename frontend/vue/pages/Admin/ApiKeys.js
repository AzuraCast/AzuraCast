import AdminApiKeys from '~/components/Admin/ApiKeys.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AdminApiKeys));

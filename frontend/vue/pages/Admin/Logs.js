import AdminLogs from '~/components/Admin/Logs.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AdminLogs));

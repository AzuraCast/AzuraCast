import AdminPermissions from '~/components/Admin/Permissions.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AdminPermissions));

import AdminCustomFields from '~/components/Admin/CustomFields.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AdminCustomFields));

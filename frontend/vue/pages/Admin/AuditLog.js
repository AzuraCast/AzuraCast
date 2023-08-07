import AuditLog from '~/components/Admin/AuditLog.vue';
import initApp from "~/layout";
import useAdminPanelLayout from "~/layouts/AdminPanelLayout";

initApp(useAdminPanelLayout(AuditLog));

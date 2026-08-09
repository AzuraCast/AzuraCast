import { GlobalPermissions } from "~/entities/ApiInterfaces.ts";
import { useAzuraCastUser } from "~/vendor/azuracast.ts";

export function useUserAllowed() {
    const { permissions } = useAzuraCastUser();

    return {
        userAllowed: (permission: GlobalPermissions): boolean => {
            try {
                if (permissions.global.indexOf(GlobalPermissions.All) !== -1) {
                    return true;
                }

                return permissions.global.indexOf(permission) !== -1;
            } catch {
                return false;
            }
        },
    };
}

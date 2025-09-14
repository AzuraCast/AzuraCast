import {ApiAdminRole, ApiAdminRolePermissions, ApiAdminRoleStationPermission} from "~/entities/ApiInterfaces.ts";

export type AdminRoleRequired = Required<
    Omit<
        ApiAdminRole,
        | 'permissions'
    > & {
    permissions: Required<
        Omit<
            ApiAdminRolePermissions,
            | 'station'
        > & {
        station: Required<ApiAdminRoleStationPermission>[]
    }
    >
}
>

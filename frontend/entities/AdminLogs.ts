import {ApiAdminLogList, ApiAdminStationLogList, ApiLogType} from "~/entities/ApiInterfaces.ts";

export type LogListRequired = Required<
    Omit<
        ApiAdminLogList,
        | 'globalLogs'
        | 'stationLogs'
    > & {
    globalLogs: Required<ApiLogType>[],
    stationLogs: Required<
        Omit<
            ApiAdminStationLogList,
            | 'logs'
        >
        & {
        logs: Required<ApiLogType>[]
    }
    >[]
}
>

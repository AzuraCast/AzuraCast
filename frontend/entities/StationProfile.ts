import {ApiStationProfile, ApiStationSchedule} from "~/entities/ApiInterfaces.ts";

export type StationProfileRequired = Required<
    Omit<
        ApiStationProfile,
        | 'schedule'
    > & {
    schedule: Required<ApiStationSchedule>[]
}
>

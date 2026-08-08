import {
    ApiStationScheduleGroupMember,
    ApiStationSchedulePlaylistEvent,
    ApiStationScheduleStreamerEvent,
} from "~/entities/ApiInterfaces.ts";

// Union of the two schedule-view event payloads returned by GET /playlists/schedule
// and GET /streamers/schedule (discriminated on `type`) with dynamically built "name" field.
export type ScheduleEventDetails = (
    | ApiStationSchedulePlaylistEvent
    | ApiStationScheduleStreamerEvent
) & {
    name: string;
};

export type ScheduleGroupMember = ApiStationScheduleGroupMember;

import {ApiListener, ApiListenerDevice, ApiListenerLocation} from "~/entities/ApiInterfaces.ts";

export type ListenerRequired = Required<
    Omit<
        ApiListener,
        | 'device'
        | 'location'
    > & {
    device: Required<ApiListenerDevice>
    location: Required<ApiListenerLocation>
}
>

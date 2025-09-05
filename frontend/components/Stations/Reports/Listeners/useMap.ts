import {InjectionKey, provide, ShallowRef} from "vue";

import {Map} from "leaflet";
import injectRequired from "~/functions/injectRequired.ts";

export type MapRef = ShallowRef<Map | null>;

export const useMap = () => {
    const mapKey = Symbol() as InjectionKey<MapRef>;

    const provideMap = ($map: MapRef): void => {
        provide(mapKey, $map);
    }

    const injectMap = (): MapRef => {
        return injectRequired(mapKey);
    }

    return {
        provideMap,
        injectMap
    }
};





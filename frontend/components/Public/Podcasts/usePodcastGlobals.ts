import {createInjectionState} from "@vueuse/shared";

export interface PodcastLayoutProps {
    stationId: string | number,
    stationName: string | null,
    stationTz: string,
    baseUrl: string,
    groupLayout?: string,
}

const [useProvidePodcastGlobals, usePodcastGlobals] = createInjectionState(
    (props: PodcastLayoutProps) => props,
);

export {useProvidePodcastGlobals, usePodcastGlobals};

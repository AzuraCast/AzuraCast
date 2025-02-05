import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

export interface PodcastLayoutProps {
    stationId: string | number,
    stationName: string | null,
    stationTz: string,
    baseUrl: string,
    groupLayout?: string,
}

export const [useProvidePodcastGlobals, usePodcastGlobals] = createRequiredInjectionState(
    (props: PodcastLayoutProps) => props,
);

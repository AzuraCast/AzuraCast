import {VueAppGlobals, VueDashboardGlobals, VueUserGlobals} from "~/entities/ApiInterfaces.ts";
import {defineStore} from "pinia";
import {inject, InjectionKey} from "vue";

export const globalConstantsKey: InjectionKey<VueAppGlobals> = Symbol() as InjectionKey<VueAppGlobals>;

export const useAzuraCastStore = defineStore(
    'global-props',
    (): {
        props: VueAppGlobals
    } => {
        return {
            props: inject(globalConstantsKey)
        };
    }
);

export const useAzuraCast = (): VueAppGlobals => {
    const {props} = useAzuraCastStore();
    return props;
};

export const useAzuraCastDashboardGlobals = (): VueDashboardGlobals => {
    const {dashboardProps} = useAzuraCast();
    if (!dashboardProps) {
        throw new Error("Dashboard properties are undefined in this request.");
    }

    return dashboardProps;
}

export const useAzuraCastUser = (): VueUserGlobals => {
    const {user} = useAzuraCast();

    if (!user) {
        throw Error("User is not logged in.");
    }

    return user;
}

import {computed, InjectionKey, onBeforeMount, onBeforeUnmount, provide, reactive, UnwrapNestedRefs, watch} from "vue";
import injectRequired from "~/functions/injectRequired.ts";
import {reactiveComputed} from "@vueuse/core";

type VueClass = string | Record<string, boolean> | VueClass[];

export interface TabChildProps {
    id?: string | number,
    label: string,
    itemHeaderClass?: VueClass
}

export interface TabParentProps {
    modelValue?: string,
    navTabsClass?: VueClass,
    contentClass?: VueClass,
    destroyOnHide?: boolean
}

interface TabChild {
    computedId: string,
    [key: string | number]: any
}

interface TabParent {
    lazy: boolean,
    active: string | null,
    tabs: TabChild[],
    add (tab: TabChild): void,
    update(computedId: string, data: TabChild): void,
    delete(computedId: string): void
}

const tabStateKey: InjectionKey<UnwrapNestedRefs<TabParent>> = Symbol() as InjectionKey<UnwrapNestedRefs<TabParent>>;

export function useTabParent(originalProps: TabParentProps) {
    const props = reactiveComputed<
        Required<
            Pick<TabParentProps, 'destroyOnHide'>
        > & Omit<TabParentProps, 'destroyOnHide'>
    >(() => ({
        destroyOnHide: false,
        ...originalProps,
    }));

    const state = reactive<TabParent>({
        lazy: props.destroyOnHide,
        active: null,
        tabs: [],
        add(tab: TabChild): void {
            this.tabs.push(tab)
        },
        update(computedId: string, data: TabChild): void {
            const tabIndex = this.tabs.findIndex((tab: TabChild) => tab.computedId === computedId);
            this.tabs[tabIndex] = data;
        },
        delete(computedId: string): void {
            const tabIndex = this.tabs.findIndex((tab: TabChild) => tab.computedId === computedId)
            this.tabs.splice(tabIndex, 1)
        }
    });

    provide(tabStateKey, state);
    return state;
}

export function useTabChild(props: TabChildProps) {
    const tabState = injectRequired(tabStateKey);

    const computedId = computed(() => {
        return String(
            props.id ?? props.label.toLowerCase().replace(/ /g, "-")
        );
    });

    const isLazy = tabState.lazy;

    const isActive = computed(() => {
        return tabState.active === computedId.value;
    });

    watch(
        () => ({...props}),
        () => tabState.update(computedId.value, {
            ...props,
            computedId: computedId.value
        })
    );

    onBeforeMount(() => tabState.add({
        ...props,
        computedId: computedId.value
    }));

    onBeforeUnmount(() => tabState.delete(computedId.value));

    return {
        computedId,
        isActive,
        isLazy
    }
}

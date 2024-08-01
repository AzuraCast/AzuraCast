import {InjectionKey, UnwrapNestedRefs, computed, inject, onBeforeMount, onBeforeUnmount, provide, reactive, watch} from "vue";

interface TabChild {
    computedId: string,
    [key: string | number]: any
}

interface TabParent {
    lazy: boolean,
    active: string,
    tabs: TabChild[],
    add (tab: TabChild): void,
    update(computedId: string, data: TabChild): void,
    delete(computedId: string): void
}

const tabStateKey: InjectionKey<UnwrapNestedRefs<TabParent>> = Symbol() as InjectionKey<UnwrapNestedRefs<TabParent>>;

export function useTabParent(props) {
    const state = reactive<TabParent>({
        lazy: props.destroyOnHide,
        active: null,
        tabs: [],
        add(tab: TabChild): void {
            this.tabs.push(tab)
        },
        update(computedId: string, data: TabChild): void {
            const tabIndex = this.tabs.findIndex((tab) => tab.computedId === computedId);
            this.tabs[tabIndex] = data;
        },
        delete(computedId: string): void {
            const tabIndex = this.tabs.findIndex((tab) => tab.computedId === computedId)
            this.tabs.splice(tabIndex, 1)
        }
    });

    provide(tabStateKey, state);
    return state;
}

export function useTabChild(props) {
    const tabState = inject(tabStateKey);

    const computedId = props.id ?? props.label.toLowerCase().replace(/ /g, "-");

    const isLazy = tabState.lazy;

    const isActive = computed(() => {
        return tabState.active === computedId;
    });

    watch(
        () => ({...props}),
        () => tabState.update(computedId, {
            ...props,
            computedId: computedId
        })
    );

    onBeforeMount(() => tabState.add({
        ...props,
        computedId: computedId
    }));

    onBeforeUnmount(() => tabState.delete(computedId));

    return {
        computedId,
        isActive,
        isLazy
    }
}

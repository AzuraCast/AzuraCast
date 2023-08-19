import {computed, inject, onBeforeMount, onBeforeUnmount, provide, reactive, watch} from "vue";

const tabStateKey = Symbol();
const addTabKey = Symbol();
const updateTabKey = Symbol();
const deleteTabKey = Symbol();

export function useTabParent(props) {
    const state = reactive({
        lazy: props.destroyOnHide,
        active: null,
        tabs: []
    });

    provide(tabStateKey, state);

    provide(addTabKey, (tab) => {
        state.tabs.push(tab)
    });

    provide(updateTabKey, (computedId: string, data) => {
        const tabIndex = state.tabs.findIndex((tab) => tab.computedId === computedId)
        state.tabs[tabIndex] = data
    })

    provide(deleteTabKey, (computedId: string) => {
        const tabIndex = state.tabs.findIndex((tab) => tab.computedId === computedId)
        state.tabs.splice(tabIndex, 1)
    })

    return state;
}

export function useTabChild(props) {
    const tabState = inject(tabStateKey);
    const addTab = inject(addTabKey);
    const updateTab = inject(updateTabKey);
    const deleteTab = inject(deleteTabKey);

    const computedId = props.id ?? props.label.toLowerCase().replace(/ /g, "-");

    const isLazy = tabState.lazy;

    const isActive = computed(() => {
        return tabState.active === computedId;
    });

    watch(
        () => ({...props}),
        () => updateTab(computedId, {
            ...props,
            computedId: computedId
        })
    );

    onBeforeMount(() => addTab({
        ...props,
        computedId: computedId
    }));

    onBeforeUnmount(() => deleteTab(computedId));

    return {
        computedId,
        isActive,
        isLazy
    }
}

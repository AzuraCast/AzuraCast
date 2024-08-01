<template>
    <nav
        class="nav nav-default"
        :class="navTabsClass"
        role="tablist"
        aria-orientation="horizontal"
    >
        <div
            v-for="(childItem) in state.tabs"
            :key="childItem.computedId"
            class="nav-item"
        >
            <button
                :id="`${childItem.computedId}-tab`"
                class="nav-link"
                :class="[
                    (activeId === childItem.computedId) ? 'active' : '',
                    childItem.itemHeaderClass
                ]"
                role="tab"
                :aria-controls="`${childItem.computedId}-content`"
                type="button"
                :aria-selected="(activeId === childItem.computedId) ? 'true' : 'false'"
                @click="selectTab(childItem.computedId)"
            >
                {{ childItem.label }}
            </button>
        </div>
    </nav>
    <section
        class="nav-content"
        :class="contentClass"
    >
        <slot />
    </section>
</template>

<script setup lang="ts">
import {onMounted, ref} from "vue";
import {useTabParent} from "~/functions/tabs.ts";

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    navTabsClass: {
        type: [String, Function, Array],
        default: () => 'nav-tabs'
    },
    contentClass: {
        type: [String, Function, Array],
        default: () => 'mt-3'
    },
    destroyOnHide: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const activeId = ref(props.modelValue);

const state = useTabParent(props);

const selectTab = (computedId: string): void => {
    state.active = computedId;
    activeId.value = computedId;
    emit('update:modelValue', computedId);
}

onMounted(() => {
    if (!state.tabs.length) {
        return;
    }

    selectTab(state.tabs[0].computedId);
});
</script>

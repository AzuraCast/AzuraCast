<template>
    <input
        v-model="text"
        type="text"
        class="form-control form-control-sm"
        :aria-label="item.label"
        :placeholder="item.placeholder"
        @keydown="onKeydown"
    >
</template>

<script setup lang="ts">
import { ref } from "vue";
import {
    MenuInputItem,
    useDropdownMenu,
} from "~/components/Common/DropdownMenu/useDropdownMenu.ts";

const props = defineProps<{
    item: MenuInputItem;
}>();

const menuState = useDropdownMenu();

const text = ref<string>(props.item.value ?? "");

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape" || event.key === "Tab") {
        return;
    }

    event.stopPropagation();

    if (event.key === "Enter") {
        event.preventDefault();
        props.item.onSelect(text.value.trim());
        menuState.closeAll();
    }
};
</script>

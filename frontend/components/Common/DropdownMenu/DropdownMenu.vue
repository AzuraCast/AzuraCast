<template>
    <div ref="$root">
        <button
            :id="triggerId"
            ref="$trigger"
            type="button"
            :class="triggerClass"
            :title="title"
            aria-haspopup="menu"
            :aria-expanded="open"
            :aria-controls="open ? panelId : undefined"
            @click="onTriggerClick"
            @keydown="onTriggerKeydown"
        >
            <slot />
        </button>
        <dropdown-menu-panel
            v-if="open && $trigger"
            :id="panelId"
            ref="$panel"
            :items="items"
            :depth="0"
            :reference="$trigger"
            :labelled-by="triggerId"
        />
    </div>
</template>

<script setup lang="ts">
import { useEventListener } from "@vueuse/core";
import { useId, useTemplateRef, watch } from "vue";
import DropdownMenuPanel from "~/components/Common/DropdownMenu/DropdownMenuPanel.vue";
import {
    InteractionMode,
    MenuItem,
    useProvideDropdownMenu,
} from "~/components/Common/DropdownMenu/useDropdownMenu.ts";

withDefaults(
    defineProps<{
        items: MenuItem[];
        title?: string;
        triggerClass?: string;
    }>(),
    {
        triggerClass: "btn btn-secondary dropdown-toggle",
    },
);

const open = defineModel<boolean>("open", { default: false });

const triggerId = useId();
const panelId = useId();

const $root = useTemplateRef<HTMLElement>("$root");
const $trigger = useTemplateRef<HTMLButtonElement>("$trigger");
const $panel = useTemplateRef<{ focusFirst: () => void }>("$panel");

const menuState = useProvideDropdownMenu((restoreFocus: boolean) => {
    if (restoreFocus) {
        $trigger.value?.focus({ preventScroll: true });
    }

    open.value = false;
});

const onTriggerClick = (event: MouseEvent) => {
    menuState.lastInteraction.value =
        event.detail === 0 ? InteractionMode.Keyboard : InteractionMode.Pointer;

    open.value = !open.value;
};

const onTriggerKeydown = (event: KeyboardEvent) => {
    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        event.stopPropagation();

        menuState.lastInteraction.value = InteractionMode.Keyboard;

        if (open.value) {
            $panel.value?.focusFirst();
        } else {
            open.value = true;
        }
        return;
    }

    if (event.key === "Escape" && open.value) {
        event.preventDefault();
        event.stopPropagation();
        open.value = false;
    }
};

watch(open, (isOpen) => {
    if (!isOpen) {
        menuState.cancelTimers();
        menuState.openSubmenuKeyByDepth.value = [];
    }
});

useEventListener(document, "pointerdown", (event: PointerEvent) => {
    if (!open.value) {
        return;
    }

    const path = event.composedPath();
    if (
        ($root.value !== null && path.includes($root.value)) ||
        menuState.containsPath(path)
    ) {
        return;
    }

    menuState.closeAll();
});
</script>

<template>
    <Teleport to="body">
        <ul
            :id="id"
            ref="$panel"
            class="menu-panel"
            role="menu"
            tabindex="-1"
            :aria-labelledby="labelledBy"
            @keydown="onKeydown"
            @pointerdown="onPointerDown"
            @pointerenter="onPanelEnter"
            @pointerleave="onPanelLeave"
        >
            <template
                v-for="item in items"
                :key="item.key"
            >
                <li
                    v-if="item.type === MenuItemType.Separator"
                    role="separator"
                >
                    <hr class="dropdown-divider">
                </li>

                <li
                    v-else-if="item.type === MenuItemType.Submenu"
                    role="none"
                    @pointerenter="onItemEnter(item, $event)"
                >
                    <button
                        :id="subTriggerId(item)"
                        :ref="(el) => setItemElement(item.key, el)"
                        type="button"
                        class="dropdown-item"
                        role="menuitem"
                        aria-haspopup="menu"
                        :aria-expanded="menuState.isOpen(depth, item.key)"
                        :aria-controls="childPanelId(item)"
                        :tabindex="tabindexFor(item)"
                        @click="onSubmenuClick(item, $event)"
                        @focus="onItemFocus(item)"
                    >
                        <component
                            :is="item.icon()"
                            v-if="item.icon"
                            class="sm"
                        />
                        <span class="menu-item-label">{{ item.label }}</span>
                        <span
                            v-if="item.indicator"
                            class="menu-item-indicator"
                            aria-hidden="true"
                        />
                        <icon-ic-chevron-right class="menu-item-chevron sm"/>
                    </button>
                </li>

                <li
                    v-else-if="item.type === MenuItemType.Input"
                    role="none"
                    class="menu-input"
                    @pointerenter="onItemEnter(item, $event)"
                >
                    <dropdown-menu-input
                        :ref="(el) => setItemElement(item.key, el)"
                        :item="item"
                        :tabindex="tabindexFor(item)"
                        @focus="onItemFocus(item)"
                    />
                </li>

                <li
                    v-else-if="item.type === MenuItemType.Checkbox"
                    role="none"
                    @pointerenter="onItemEnter(item, $event)"
                >
                    <label class="dropdown-item menu-item-checkbox form-check">
                        <form-checkbox
                            :ref="(el) => setItemElement(item.key, el)"
                            role="menuitemcheckbox"
                            :model-value="item.checked"
                            :tabindex="tabindexFor(item)"
                            @update:model-value="onCheckboxClick(item)"
                            @keydown.enter.prevent="onCheckboxClick(item)"
                            @focus="onItemFocus(item)"
                        />
                        <component
                            :is="item.icon()"
                            v-if="item.icon"
                            class="sm ms-1"
                        />
                        <span class="menu-item-label">{{ item.label }}</span>
                    </label>
                </li>

                <li
                    v-else
                    role="none"
                    @pointerenter="onItemEnter(item, $event)"
                >
                    <button
                        :ref="(el) => setItemElement(item.key, el)"
                        type="button"
                        class="dropdown-item"
                        :class="{ active: item.checked }"
                        :role="item.checked === undefined ? 'menuitem' : 'menuitemcheckbox'"
                        :aria-checked="item.checked"
                        :tabindex="tabindexFor(item)"
                        @click="onActionClick(item)"
                        @focus="onItemFocus(item)"
                    >
                        <component
                            :is="item.icon()"
                            v-if="item.icon"
                            class="sm"
                        />
                        <span class="menu-item-label">{{ item.label }}</span>
                    </button>
                </li>
            </template>
        </ul>
        <dropdown-menu-panel
            v-if="openChild"
            :id="childPanelId(openChild.item)"
            ref="$childPanel"
            :items="openChild.item.items"
            :depth="depth + 1"
            :reference="openChild.reference"
            :labelled-by="subTriggerId(openChild.item)"
        />
    </Teleport>
</template>

<script setup lang="ts">
import { createPopper, Instance } from "@popperjs/core";
import {
    ComponentPublicInstance,
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    useTemplateRef,
    watch,
} from "vue";
import DropdownMenuInput from "~/components/Common/DropdownMenu/DropdownMenuInput.vue";
import {
    InteractionMode,
    MenuActionItem,
    MenuCheckboxItem,
    MenuItem,
    MenuItemType,
    MenuSubmenuItem,
    PointerType,
    useDropdownMenu,
} from "~/components/Common/DropdownMenu/useDropdownMenu.ts";
import FormCheckbox from "~/components/Form/FormCheckbox.vue";
import IconIcChevronRight from "~icons/ic/baseline-chevron-right";

const props = defineProps<{
    id: string;
    items: MenuItem[];
    depth: number;
    reference: HTMLElement;
    labelledBy: string;
}>();

const menuState = useDropdownMenu();

const $panel = useTemplateRef<HTMLElement>("$panel");
const $childPanel = useTemplateRef<{ focusFirst: () => void }>("$childPanel");

const itemElements = new Map<string, HTMLElement>();

const setItemElement = (
    key: string,
    el: Element | ComponentPublicInstance | null,
) => {
    const element = el instanceof Element ? el : el?.$el;

    element instanceof HTMLElement
        ? itemElements.set(key, element)
        : itemElements.delete(key);
};

const subTriggerId = (item: MenuSubmenuItem): string => {
    return `${props.id}-${item.key}`;
};

const childPanelId = (item: MenuSubmenuItem): string => {
    return `${props.id}-${item.key}-panel`;
};

const focusableItems = computed<MenuItem[]>(() => {
    return props.items.filter((item) => {
        return item.type !== MenuItemType.Separator;
    });
});

const activeKey = ref<string | null>(null);

const currentKey = computed<string | null>(() => {
    return activeKey.value ?? focusableItems.value[0]?.key ?? null;
});

const currentItem = computed<MenuItem | undefined>(() => {
    return focusableItems.value.find((item) => {
        return item.key === currentKey.value;
    });
});

const tabindexFor = (item: MenuItem): number => {
    return item.key === currentKey.value ? 0 : -1;
};

const onItemFocus = (item: MenuItem) => {
    activeKey.value = item.key;
};

const focusItem = (key: string) => {
    activeKey.value = key;
    itemElements.get(key)?.focus({ preventScroll: true });
};

const focusFirst = () => {
    const first = focusableItems.value[0];
    if (first) {
        focusItem(first.key);
    }
};

const focusLast = () => {
    const last = focusableItems.value[focusableItems.value.length - 1];
    if (last) {
        focusItem(last.key);
    }
};

const moveFocus = (delta: 1 | -1) => {
    const list = focusableItems.value;
    if (list.length === 0) {
        return;
    }

    const index = list.findIndex((item) => {
        return item.key === currentKey.value;
    });

    if (index === -1) {
        if (delta === 1) {
            focusFirst();
        } else {
            focusLast();
        }
        return;
    }

    focusItem(list[(index + delta + list.length) % list.length].key);
};

type OpenChild = {
    item: MenuSubmenuItem;
    reference: HTMLElement;
};

const openChild = computed<OpenChild | null>(() => {
    const key = menuState.openSubmenuKeyByDepth.value[props.depth];
    if (key === undefined) {
        return null;
    }

    const item = props.items.find(
        (candidate): candidate is MenuSubmenuItem =>
            candidate.type === MenuItemType.Submenu && candidate.key === key,
    );

    const reference = itemElements.get(key);

    return item !== undefined && reference !== undefined
        ? { item: item, reference: reference }
        : null;
});

const openSubmenu = (item: MenuSubmenuItem, viaKeyboard: boolean) => {
    menuState.cancelTimers();
    menuState.lastInteraction.value = viaKeyboard
        ? InteractionMode.Keyboard
        : InteractionMode.Pointer;

    if (menuState.isOpen(props.depth, item.key)) {
        if (viaKeyboard) {
            $childPanel.value?.focusFirst();
        }
        return;
    }

    menuState.openAt(props.depth, item.key);
};

const closeToParent = () => {
    props.reference.focus({ preventScroll: true });
    menuState.closeChildren(props.depth - 1);
};

const onKeydown = (event: KeyboardEvent) => {
    switch (event.key) {
        case "ArrowDown":
            moveFocus(1);
            break;

        case "ArrowUp":
            moveFocus(-1);
            break;

        case "Home":
            focusFirst();
            break;

        case "End":
            focusLast();
            break;

        case "ArrowRight": {
            const item = currentItem.value;
            if (item?.type === MenuItemType.Submenu) {
                openSubmenu(item, true);
            }
            break;
        }

        case "ArrowLeft":
            if (props.depth > 0) {
                closeToParent();
            }
            break;

        case "Escape":
            if (props.depth > 0) {
                closeToParent();
            } else {
                menuState.closeAll();
            }
            break;

        case "Tab":
            menuState.closeAll();
            break;

        default:
            return;
    }

    event.preventDefault();
    event.stopPropagation();
};

let lastPointerType: string = PointerType.Mouse;

const onPointerDown = (event: PointerEvent) => {
    lastPointerType = event.pointerType;
    menuState.lastInteraction.value = InteractionMode.Pointer;
};

const onPanelEnter = () => {
    menuState.cancelTimers();
};

const onPanelLeave = (event: PointerEvent) => {
    if (event.pointerType === PointerType.Mouse) {
        menuState.scheduleClose(props.depth);
    }
};

const onItemEnter = (item: MenuItem, event: PointerEvent) => {
    if (event.pointerType !== PointerType.Mouse) {
        return;
    }

    menuState.lastInteraction.value = InteractionMode.Pointer;

    if (item.type === MenuItemType.Submenu) {
        if (menuState.isOpen(props.depth, item.key)) {
            menuState.cancelTimers();
        } else {
            menuState.scheduleOpen(props.depth, item.key);
        }
        return;
    }

    if (menuState.openSubmenuKeyByDepth.value.length > props.depth) {
        menuState.scheduleClose(props.depth);
    } else {
        menuState.cancelTimers();
    }
};

const onSubmenuClick = (item: MenuSubmenuItem, event: MouseEvent) => {
    if (event.detail === 0) {
        openSubmenu(item, true);
        return;
    }

    if (
        lastPointerType !== PointerType.Mouse &&
        menuState.isOpen(props.depth, item.key)
    ) {
        menuState.cancelTimers();
        menuState.closeChildren(props.depth);
        return;
    }

    openSubmenu(item, false);
};

const onActionClick = (item: MenuActionItem) => {
    item.onSelect();
    menuState.closeAll();
};

const onCheckboxClick = (item: MenuCheckboxItem) => {
    item.onToggle();
};

let popper: Instance | null = null;

onMounted(() => {
    const panel = $panel.value;
    if (!panel) {
        return;
    }

    menuState.registerPanel(panel);

    const isRoot = props.depth === 0;
    popper = createPopper(props.reference, panel, {
        placement: isRoot ? "bottom-start" : "right-start",
        modifiers: [
            {
                name: "flip",
                options: {
                    fallbackPlacements: isRoot
                        ? ["bottom-end", "top-start", "top-end"]
                        : ["left-start", "right-end", "left-end"],
                },
            },
            { name: "preventOverflow", options: { padding: 8 } },
            { name: "offset", options: { offset: [0, isRoot ? 2 : 0] } },
        ],
    });
    popper.forceUpdate();

    const first = focusableItems.value[0];
    const onlyInput =
        focusableItems.value.length === 1 && first?.type === MenuItemType.Input;

    if (
        first &&
        (menuState.lastInteraction.value === InteractionMode.Keyboard ||
            onlyInput)
    ) {
        focusItem(first.key);
    }
});

onBeforeUnmount(() => {
    if ($panel.value) {
        menuState.unregisterPanel($panel.value);
    }

    popper?.destroy();
    popper = null;
});

watch(
    () => props.items,
    (items) => {
        const key = menuState.openSubmenuKeyByDepth.value[props.depth];
        if (
            key !== undefined &&
            !items.some(
                (item) =>
                    item.type === MenuItemType.Submenu && item.key === key,
            )
        ) {
            menuState.closeChildren(props.depth);
        }
    },
);

defineExpose({
    focusFirst,
});
</script>

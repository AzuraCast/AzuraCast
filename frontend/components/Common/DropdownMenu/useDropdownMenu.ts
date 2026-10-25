import { useTimeoutFn } from "@vueuse/core";
import { Component, ref } from "vue";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

export enum MenuItemType {
    Action = "action",
    Checkbox = "checkbox",
    Submenu = "submenu",
    Input = "input",
    Separator = "separator",
}

interface MenuItemBase {
    key: string;
}

export interface MenuActionItem extends MenuItemBase {
    type: MenuItemType.Action;
    label: string;
    icon?: () => Component;
    checked?: boolean;
    onSelect: () => void;
}

export interface MenuCheckboxItem extends MenuItemBase {
    type: MenuItemType.Checkbox;
    label: string;
    icon?: () => Component;
    checked: boolean;
    onToggle: () => void;
}

export interface MenuSubmenuItem extends MenuItemBase {
    type: MenuItemType.Submenu;
    label: string;
    icon?: () => Component;
    indicator?: boolean;
    items: MenuItem[];
}

export interface MenuInputItem extends MenuItemBase {
    type: MenuItemType.Input;
    label: string;
    value?: string;
    placeholder?: string;
    onSelect: (value: string) => void;
}

export interface MenuSeparatorItem extends MenuItemBase {
    type: MenuItemType.Separator;
}

export type MenuItem =
    | MenuActionItem
    | MenuCheckboxItem
    | MenuSubmenuItem
    | MenuInputItem
    | MenuSeparatorItem;

export enum PointerType {
    Mouse = "mouse",
    Pen = "pen",
    Touch = "touch",
}

export enum InteractionMode {
    Pointer = "pointer",
    Keyboard = "keyboard",
}

const OPEN_DELAY = 120;
const CLOSE_DELAY = 150;

export const [useProvideDropdownMenu, useDropdownMenu] =
    createRequiredInjectionState(
        (closeRoot: (restoreFocus: boolean) => void) => {
            const openSubmenuKeyByDepth = ref<string[]>([]);
            const lastInteraction = ref<InteractionMode>(
                InteractionMode.Pointer,
            );
            const mountedPanelElements = new Set<HTMLElement>();

            const registerPanel = (element: HTMLElement): void => {
                mountedPanelElements.add(element);
            };

            const unregisterPanel = (element: HTMLElement): void => {
                mountedPanelElements.delete(element);
            };

            const isMountedPanel = (target: EventTarget): boolean => {
                return (
                    target instanceof HTMLElement &&
                    mountedPanelElements.has(target)
                );
            };

            const containsPath = (path: EventTarget[]): boolean => {
                return path.some(isMountedPanel);
            };

            const containsActiveElement = (): boolean => {
                const active = document.activeElement;
                return (
                    active !== null &&
                    [...mountedPanelElements].some((panel) => {
                        return panel.contains(active);
                    })
                );
            };

            const isOpen = (depth: number, key: string): boolean => {
                return openSubmenuKeyByDepth.value[depth] === key;
            };

            const openAt = (depth: number, key: string): void => {
                openSubmenuKeyByDepth.value = [
                    ...openSubmenuKeyByDepth.value.slice(0, depth),
                    key,
                ];
            };

            const closeChildren = (depth: number): void => {
                if (openSubmenuKeyByDepth.value.length > depth) {
                    openSubmenuKeyByDepth.value =
                        openSubmenuKeyByDepth.value.slice(0, depth);
                }
            };

            const closeAll = (): void => {
                const restoreFocus = containsActiveElement();
                openSubmenuKeyByDepth.value = [];
                closeRoot(restoreFocus);
            };

            const { start: startOpenTimer, stop: stopOpenTimer } = useTimeoutFn(
                (depth: number, key: string) => openAt(depth, key),
                OPEN_DELAY,
                { immediate: false },
            );

            const { start: startCloseTimer, stop: stopCloseTimer } =
                useTimeoutFn(
                    (depth: number) => closeChildren(depth),
                    CLOSE_DELAY,
                    { immediate: false },
                );

            const cancelTimers = (): void => {
                stopOpenTimer();
                stopCloseTimer();
            };

            const scheduleOpen = (depth: number, key: string): void => {
                cancelTimers();
                startOpenTimer(depth, key);
            };

            const scheduleClose = (depth: number): void => {
                cancelTimers();
                startCloseTimer(depth);
            };

            return {
                openSubmenuKeyByDepth,
                lastInteraction,
                isOpen,
                openAt,
                closeChildren,
                closeAll,
                cancelTimers,
                scheduleOpen,
                scheduleClose,
                registerPanel,
                unregisterPanel,
                containsPath,
            };
        },
    );

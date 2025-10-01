import {Component} from "vue";

export type MenuRouteBasedUrl = {
    name: string,
    params?: Record<string, any>
}

export type MenuRouteUrl = string | MenuRouteBasedUrl;

type MenuBase = {
    key: string,
    label: string,
    icon?: () => Component,
    url?: MenuRouteUrl,
    external?: boolean,
    title?: string,
    "class"?: string,
}

type RawMenuSubCategory = MenuBase & {
    visible?: () => boolean
}

export type RawMenuCategory = RawMenuSubCategory & {
    items?: RawMenuSubCategory[]
}

export type MenuSubCategory = MenuBase;

export type MenuCategory = MenuBase & {
    items?: MenuSubCategory[]
}

export const filterMenu = (originalMenu: RawMenuCategory[]): MenuCategory[] => {
    return originalMenu.map(
        (menuRow): MenuCategory | null => {
            const newRow: MenuCategory = {
                ...menuRow,
            };

            if ('visible' in menuRow && menuRow.visible) {
                const itemIsVisible = menuRow.visible();
                if (!itemIsVisible) {
                    return null;
                }
            }

            if ('items' in menuRow && menuRow.items) {
                newRow.items = menuRow.items.map(
                    (itemRow): MenuBase | null => {
                        if ('visible' in itemRow && itemRow.visible) {
                            const itemIsVisible = itemRow.visible();
                            if (!itemIsVisible) {
                                return null;
                            }
                        }

                        return {
                            ...itemRow
                        };
                    }
                ).filter(
                    (row) => row !== null
                );

                if (newRow.items.length === 0) {
                    return null;
                }
            }

            return newRow;
        }
    ).filter(
        (row) => row !== null
    );
}

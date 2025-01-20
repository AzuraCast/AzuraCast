import {cloneDeep, filter, get, map} from "lodash";
import {ComputedRef, toRaw} from "vue";
import {Icon} from "../components/Common/icons";
import {reactiveComputed} from "@vueuse/core";

export interface MenuRouteBasedUrl {
    name: string,
    params?: Record<string, any>
}

export type MenuRouteUrl = string | MenuRouteBasedUrl;

export interface MenuSubCategory {
    key: string,
    label: ComputedRef<string>,
    icon?: Icon,
    visible?: boolean,
    url?: MenuRouteUrl,
    external?: boolean,
    title?: string,
    "class"?: string,
}

export interface MenuCategory extends MenuSubCategory {
    items?: MenuSubCategory[]
}

export interface ReactiveMenu {
    categories: MenuCategory[]
}

export default function filterMenu(originalMenu: ReactiveMenu): ReactiveMenu {
    return reactiveComputed(
        () => ({
            categories: filter(
                map(
                    cloneDeep(toRaw(originalMenu.categories)),
                    (menuRow: MenuCategory): MenuCategory | null => {
                        const itemIsVisible: boolean = get(menuRow, 'visible', true);
                        if (!itemIsVisible) {
                            return null;
                        }

                        if ('items' in menuRow) {
                            menuRow.items = filter(menuRow.items, (item) => {
                                return get(item, 'visible', true);
                            });

                            if (menuRow.items.length === 0) {
                                return null;
                            }
                        }

                        return menuRow;
                    }
                ),
                (row: MenuCategory | null) => null !== row
            ),
        })
    ) as unknown as ReactiveMenu;
}

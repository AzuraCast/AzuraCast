import {cloneDeep, filter, get, map} from "lodash";
import {ComputedRef, Reactive} from "vue";
import {Icon} from "../components/Common/icons";
import {RouteLocationRaw} from "vue-router";
import {reactiveComputed} from "@vueuse/core";

export type ReactiveMenu = Reactive<Array<MenuCategory>>;

export interface MenuSubCategory {
    key: string,
    label: ComputedRef<string>,
    url?: RouteLocationRaw | string | null,
    icon?: Icon | null,
    visible?: boolean | null,
    external?: boolean | null,
    title?: string,
    class?: string,
}

export interface MenuCategory extends MenuSubCategory {
    items?: MenuSubCategory[] | null
}

export default function filterMenu(menuItems: ReactiveMenu): ReactiveMenu {
    return reactiveComputed(
        () => filter(
            map(
                cloneDeep(menuItems),
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
        )
    );
}

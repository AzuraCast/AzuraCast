import {filter, get, map} from "lodash";
import { ComputedRef, UnwrapNestedRefs } from "vue";
import { Icon } from "../components/Common/icons";
import { RouteLocationRaw } from "vue-router";

export type ReactiveMenu = UnwrapNestedRefs<Array<MenuCategory>>;

export interface MenuSubCategory {
    key: string,
    label: ComputedRef<string>,
    url?: RouteLocationRaw | string | null,
    icon?: Icon | null,
    visible?: boolean | null,
    external?: boolean | null,
}

export interface MenuCategory extends MenuSubCategory {
    items?: MenuSubCategory[] | null
}

export default function filterMenu(menuItems: ReactiveMenu): ReactiveMenu {
    return filter(map(
        menuItems,
        (menuRow: MenuCategory) => {
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
    ));
}

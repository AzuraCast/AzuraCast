import {cloneDeep, filter, get, map} from "lodash";
import {ComputedRef, Reactive, toRaw} from "vue";
import {Icon} from "../components/Common/icons";
import {reactiveComputed} from "@vueuse/core";

export type ReactiveMenu = Reactive<MenuCategory[]>;

interface RouteBasedUrl {
    name: string,
    params?: Record<string, any>
}

export interface MenuSubCategory {
    key: string,
    label: ComputedRef<string>,
    url?: string | RouteBasedUrl,
    icon?: Icon,
    visible?: boolean,
    external?: boolean,
    title?: string,
    "class"?: string,
}

export interface MenuCategory extends MenuSubCategory {
    items?: MenuSubCategory[]
}

export default function filterMenu(menuItems: ReactiveMenu): ReactiveMenu {
    return reactiveComputed(
        () => filter(
            map(
                cloneDeep(toRaw(menuItems as unknown as MenuCategory[])),
                (menuRow): MenuCategory | null => {
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

import {filter, get, map} from "lodash";

export default function filterMenu(menuItems) {
    return filter(map(
        menuItems,
        (menuRow) => {
            const itemIsVisible: boolean = get(menuRow, 'visible', true);
            if (!itemIsVisible) {
                return false;
            }

            if ('items' in menuRow) {
                menuRow.items = filter(menuRow.items, (item) => {
                    return get(item, 'visible', true);
                });

                if (menuRow.items.length === 0) {
                    return false;
                }
            }

            return menuRow;
        }
    ));
}

import {cloneDeep, filter, forEach, get} from "lodash";

export default function filterMenu(menuItems) {
    const newMenu = [];

    forEach(cloneDeep(menuItems), (menuRow) => {
        const itemIsVisible: boolean = get(menuRow, 'visible', true);
        if (!itemIsVisible) {
            return;
        }

        const newMenuRow = {
            ...menuRow
        };

        if ('items' in menuRow) {
            const newMenuRowItems = filter(menuRow.items, (item) => {
                return get(item, 'visible', true);
            });

            if (newMenuRowItems.length === 0) {
                return;
            }

            newMenuRow.items = newMenuRowItems;
        }

        newMenu.push(newMenuRow);
    });

    return newMenu;
}

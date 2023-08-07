<template>
    <ul class="offcanvas-body navdrawer-nav">
        <li
            v-for="(category, category_id) in menu"
            :key="category_id"
            class="nav-item"
        >
            <a
                v-bind="getCategoryLink(category, category_id)"
                class="nav-link"
            >
                <icon
                    class="navdrawer-nav-icon"
                    :icon="category.icon"
                />
                {{ category.label }}
                <icon
                    v-if="category.external"
                    class="sm ms-2"
                    icon="open_in_new"
                    :aria-label="$gettext('External')"
                />
            </a>

            <div
                v-if="category.items"
                :id="'sidebar-submenu-'+category_id"
                class="collapse pb-2"
            >
                <ul class="navdrawer-nav">
                    <li
                        v-for="(item, item_id) in category.items"
                        :key="item_id"
                        class="nav-item"
                    >
                        <a
                            class="nav-link ps-4 py-2"
                            :class="item.class"
                            :href="item.url"
                            :target="(item.external) ? '_blank' : ''"
                            :title="item.title"
                        >
                            {{ item.label }}
                            <icon
                                v-if="item.external"
                                class="sm ms-2"
                                icon="open_in_new"
                                :aria-label="$gettext('External')"
                            />
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</template>

<script setup>
import Icon from "~/components/Common/Icon.vue";

const props = defineProps({
    menu: {
        type: Object,
        required: true
    },
    active: {
        type: String,
        default: null
    }
});

const getCategoryLink = (category, category_id) => {
    const linkAttrs = {
        class: [
            category.class,
            (props.active === category_id) ? 'active' : ''
        ]
    };

    if (category.items) {
        linkAttrs['data-bs-toggle'] = 'collapse';
        linkAttrs.href = '#sidebar-submenu-' + category_id;
    } else {
        linkAttrs.href = category.url;
    }

    if (category.external) {
        linkAttrs.target = '_blank';
    }
    if (category.title) {
        linkAttrs.title = category.title;
    }

    return linkAttrs;
}
</script>

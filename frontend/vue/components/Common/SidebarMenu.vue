<template>
    <ul class="navdrawer-nav">
        <li
            v-for="category in menu"
            :key="category.key"
            class="nav-item"
        >
            <router-link
                v-if="isRouteLink(category)"
                :class="getLinkClass(category)"
                :to="category.url"
                class="nav-link"
            >
                <icon
                    class="navdrawer-nav-icon"
                    :icon="category.icon"
                />
                {{ category.label }}
            </router-link>
            <a
                v-else
                v-bind="getCategoryLink(category)"
                class="nav-link"
                :class="getLinkClass(category)"
            >
                <icon
                    class="navdrawer-nav-icon"
                    :icon="category.icon"
                />
                {{ category.label }}
                <icon
                    v-if="category.external"
                    class="sm ms-2"
                    :icon="IconOpenInNew"
                    :aria-label="$gettext('External')"
                />
            </a>

            <div
                v-if="category.items"
                :id="'sidebar-submenu-'+category.key"
                class="collapse pb-2"
                :class="(isActiveItem(category)) ? 'show' : ''"
            >
                <ul class="navdrawer-nav">
                    <li
                        v-for="item in category.items"
                        :key="item.key"
                        class="nav-item"
                    >
                        <router-link
                            v-if="isRouteLink(item)"
                            :to="item.url"
                            class="nav-link ps-4 py-2"
                            :class="getLinkClass(item)"
                        >
                            {{ item.label }}
                        </router-link>
                        <a
                            v-else
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
                                :icon="IconOpenInNew"
                                :aria-label="$gettext('External')"
                            />
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {useRoute} from "vue-router";
import {some} from "lodash";
import {IconOpenInNew} from "~/components/Common/icons.ts";

const props = defineProps({
    menu: {
        type: Object,
        required: true
    },
});

const currentRoute = useRoute();

const isRouteLink = (item) => {
    return (typeof (item.url) !== 'undefined')
        && (typeof (item.url) !== 'string');
};

const isActiveItem = (item) => {
    if (item.items && some(item.items, isActiveItem)) {
        return true;
    }

    return isRouteLink(item) && !('params' in item.url) && item.url.name === currentRoute.name;
};

const getLinkClass = (item) => {
    return [
        item.class ?? null,
        isActiveItem(item) ? 'active' : ''
    ];
}

const getCategoryLink = (item) => {
    const linkAttrs: {
        [key: string]: any
    } = {};

    if (item.items) {
        linkAttrs['data-bs-toggle'] = 'collapse';
        linkAttrs.href = '#sidebar-submenu-' + item.key;
    } else {
        linkAttrs.href = item.url;
    }

    if (item.external) {
        linkAttrs.target = '_blank';
    }
    if (item.title) {
        linkAttrs.title = item.title;
    }

    return linkAttrs;
}
</script>

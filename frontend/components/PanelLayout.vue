<template>
    <a
        class="visually-hidden-focusable"
        href="#content"
    >
        {{ $gettext('Skip to main content') }}
    </a>

    <header class="navbar bg-primary-dark shadow-sm fixed-top">
        <template v-if="slots.sidebar">
            <button
                id="navbar-toggle"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebar"
                aria-controls="sidebar"
                aria-expanded="false"
                :aria-label="$gettext('Toggle Sidebar')"
                class="navbar-toggler d-inline-flex d-lg-none me-3"
            >
                <icon
                    :icon="IconMenu"
                    class="lg"
                />
            </button>
        </template>

        <router-link
            class="navbar-brand ms-0 me-auto"
            :to="{ name: 'dashboard' }"
        >
            azura<strong>cast</strong>
            <small v-if="instanceName">{{ instanceName }}</small>
        </router-link>

        <div id="radio-player-controls">
            <inline-player class="ms-3"/>
        </div>

        <div class="dropdown ms-3 d-inline-flex align-items-center">
            <div class="me-2">
                {{ displayName }}
            </div>

            <button
                aria-expanded="false"
                aria-haspopup="true"
                class="navbar-toggler"
                :aria-label="$gettext('Toggle Menu')"
                data-bs-toggle="dropdown"
                type="button"
            >
                <icon
                    :icon="IconMenuOpen"
                    class="lg"
                />
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <router-link
                        class="dropdown-item"
                        :to="{ name: 'dashboard' }"
                    >
                        <icon :icon="IconHome" />
                        {{ $gettext('Dashboard') }}
                    </router-link>
                </li>
                <li class="dropdown-divider">
&nbsp;
                </li>
                <li v-if="showAdmin">
                    <router-link
                        class="dropdown-item"
                        :to="{ name: 'admin:index'}"
                    >
                        <icon :icon="IconSettings" />
                        {{ $gettext('System Administration') }}
                    </router-link>
                </li>
                <li>
                    <router-link
                        class="dropdown-item"
                        :to="{name: 'profile:index'}"
                    >
                        <icon :icon="IconAccountCircle" />
                        {{ $gettext('My Account') }}
                    </router-link>
                </li>
                <li>
                    <a
                        class="dropdown-item theme-switcher"
                        href="#"
                        @click.prevent="toggleTheme"
                    >
                        <icon :icon="IconInvertColors" />
                        {{ $gettext('Switch Theme') }}
                    </a>
                </li>
                <li class="dropdown-divider">
                    &nbsp;
                </li>
                <li>
                    <a
                        class="dropdown-item"
                        href="/docs/"
                        target="_blank"
                    >
                        <icon :icon="IconSupport" />
                        {{ $gettext('Documentation') }}
                    </a>
                </li>
                <li>
                    <a
                        class="dropdown-item"
                        href="/docs/help/troubleshooting/"
                        target="_blank"
                    >
                        <icon :icon="IconHelp" />
                        {{ $gettext('Help') }}
                    </a>
                </li>
                <li class="dropdown-divider">
&nbsp;
                </li>
                <li>
                    <a
                        class="dropdown-item"
                        :href="logoutUrl"
                    >
                        <icon :icon="IconExitToApp" />
                        {{ $gettext('Sign Out') }}
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <nav
        v-if="slots.sidebar"
        id="sidebar"
        class="navdrawer offcanvas offcanvas-start"
        tabindex="-1"
        :aria-label="$gettext('Sidebar')"
    >
        <slot name="sidebar" />
    </nav>

    <div
        id="page-wrapper"
        :class="[(slots.sidebar) ? 'has-sidebar' : '']"
    >
        <main id="main">
            <div class="container" id="content">
                <slot />

                <lightbox ref="$lightbox"/>
            </div>
        </main>

        <PanelFooter/>
    </div>
</template>

<script setup lang="ts">
import {nextTick, onMounted, useSlots, useTemplateRef, watch} from "vue";
import Icon from "~/components/Common/Icon.vue";
import {useTheme} from "~/functions/theme.ts";
import {
    IconAccountCircle,
    IconExitToApp,
    IconHelp,
    IconHome,
    IconInvertColors,
    IconMenu,
    IconMenuOpen,
    IconSettings,
    IconSupport
} from "~/components/Common/icons";
import {useProvidePlayerStore} from "~/functions/usePlayerStore.ts";
import Lightbox from "~/components/Common/Lightbox.vue";
import {useAzuraCastDashboardGlobals, useAzuraCastUser} from "~/vendor/azuracast.ts";
import {useProvideLightbox} from "~/vendor/lightbox.ts";
import PanelFooter from "~/components/Common/PanelFooter.vue";
import {userAllowed} from "~/acl.ts";
import {GlobalPermissions} from "~/entities/ApiInterfaces.ts";
import InlinePlayer from "~/components/InlinePlayer.vue";

const {
    instanceName,
    logoutUrl
} = useAzuraCastDashboardGlobals();

const {displayName} = useAzuraCastUser();

const showAdmin = userAllowed(GlobalPermissions.View);

const slots = useSlots();

const handleSidebar = () => {
    if (slots.sidebar) {
        document.body.classList.add('has-sidebar');
    } else {
        document.body.classList.remove('has-sidebar');
    }
}

const {toggleTheme} = useTheme();

watch(
    () => slots.sidebar,
    handleSidebar,
    {
        immediate: true
    }
);

useProvidePlayerStore('global');

const $lightbox = useTemplateRef('$lightbox');
useProvideLightbox($lightbox);

onMounted(() => {
    void nextTick(() => {
        document.dispatchEvent(new CustomEvent("vue-ready"));
    });
});
</script>

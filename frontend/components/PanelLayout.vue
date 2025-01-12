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

        <a
            class="navbar-brand ms-0 me-auto"
            :href="homeUrl"
        >
            azura<strong>cast</strong>
            <small v-if="instanceName">{{ instanceName }}</small>
        </a>

        <div id="radio-player-controls" />

        <div class="dropdown ms-3 d-inline-flex align-items-center">
            <div class="me-2">
                {{ userDisplayName }}
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
                    <a
                        class="dropdown-item"
                        :href="homeUrl"
                    >
                        <icon :icon="IconHome" />
                        {{ $gettext('Dashboard') }}
                    </a>
                </li>
                <li class="dropdown-divider">
&nbsp;
                </li>
                <li v-if="showAdmin">
                    <a
                        class="dropdown-item"
                        :href="adminUrl"
                    >
                        <icon :icon="IconSettings" />
                        {{ $gettext('System Administration') }}
                    </a>
                </li>
                <li>
                    <a
                        class="dropdown-item"
                        :href="profileUrl"
                    >
                        <icon :icon="IconAccountCircle" />
                        {{ $gettext('My Account') }}
                    </a>
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
            <div class="container">
                <slot />
            </div>
        </main>

        <footer id="footer">
            {{ $gettext('Powered by') }}
            <a
                href="https://www.azuracast.com/"
                target="_blank"
            >AzuraCast</a>
            &bull;
            <span v-html="version" />
            &bull;
            <span v-html="platform" /><br>
            {{ $gettext('Like our software?') }}
            <a
                href="https://donate.azuracast.com/"
                target="_blank"
            >
                {{ $gettext('Donate to support AzuraCast!') }}
            </a>
        </footer>
    </div>
</template>

<script lang="ts">
export interface PanelLayoutProps {
    instanceName: string,
    userDisplayName: string,
    homeUrl: string,
    profileUrl: string,
    adminUrl: string,
    logoutUrl: string,
    showAdmin: boolean,
    version: string,
    platform: string
}
</script>

<script setup lang="ts">
import {nextTick, onMounted, useSlots, watch} from "vue";
import Icon from "~/components/Common/Icon.vue";
import useTheme from "~/functions/theme";
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

const props = defineProps<PanelLayoutProps>();

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

onMounted(async () => {
    await nextTick();
    document.dispatchEvent(new CustomEvent("vue-ready"));
});
</script>

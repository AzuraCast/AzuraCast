<template>
    <a
        class="visually-hidden-focusable"
        href="#content"
    >
        {{ $gettext('Skip to main content') }}
    </a>

    <header class="navbar bg-primary-dark shadow-sm fixed-top">
        <a
            class="navbar-brand ms-0 me-auto"
            :href="homeUrl"
        >
            azura<strong>cast</strong>
            <small v-if="instanceName">{{ instanceName }}</small>
        </a>

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
                    <a
                        class="dropdown-item theme-switcher"
                        href="#"
                        @click.prevent="toggleTheme"
                    >
                        <icon :icon="IconInvertColors"/>
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
                        <icon :icon="IconSupport"/>
                        {{ $gettext('Documentation') }}
                    </a>
                </li>
                <li>
                    <a
                        class="dropdown-item"
                        href="/docs/help/troubleshooting/"
                        target="_blank"
                    >
                        <icon :icon="IconHelp"/>
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
                        <icon :icon="IconExitToApp"/>
                        {{ $gettext('Sign Out') }}
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <div id="page-wrapper">
        <main id="main">
            <div class="container" id="content">
                <slot/>
            </div>
        </main>

        <PanelFooter/>
    </div>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {useTheme} from "~/functions/theme.ts";
import {IconExitToApp, IconHelp, IconInvertColors, IconMenuOpen, IconSupport} from "~/components/Common/icons";
import {useAzuraCastDashboardGlobals, useAzuraCastUser} from "~/vendor/azuracast.ts";
import PanelFooter from "~/components/Common/PanelFooter.vue";

const {
    homeUrl,
    instanceName,
    logoutUrl
} = useAzuraCastDashboardGlobals();

const {displayName} = useAzuraCastUser();

const {toggleTheme} = useTheme();
</script>

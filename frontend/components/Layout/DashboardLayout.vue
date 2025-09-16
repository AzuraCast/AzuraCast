<template>
    <a
        class="visually-hidden-focusable"
        href="#content"
    >
        {{ $gettext('Skip to main content') }}
    </a>

    <header class="navbar bg-primary-dark shadow-sm fixed-top">
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

    <slot/>

    <lightbox ref="$lightbox"/>
</template>

<script setup lang="ts">
import {useTemplateRef} from "vue";
import Icon from "~/components/Common/Icons/Icon.vue";
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
} from "~/components/Common/Icons/icons.ts";
import {useAzuraCastDashboardGlobals, useAzuraCastUser} from "~/vendor/azuracast.ts";
import {useProvideLightbox} from "~/vendor/lightbox.ts";
import {userAllowed} from "~/acl.ts";
import {GlobalPermissions} from "~/entities/ApiInterfaces.ts";
import InlinePlayer from "~/components/InlinePlayer.vue";
import Lightbox from "~/components/Common/Lightbox.vue";

const {
    instanceName,
    logoutUrl
} = useAzuraCastDashboardGlobals();

const {displayName} = useAzuraCastUser();

const showAdmin = userAllowed(GlobalPermissions.View);

const {toggleTheme} = useTheme();

const $lightbox = useTemplateRef('$lightbox');
useProvideLightbox($lightbox);
</script>

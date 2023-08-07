<template>
    <div class="navdrawer-header offcanvas-header">
        <div class="d-flex align-items-center">
            <a
                class="navbar-brand px-0 flex-fill"
                :href="profileUrl"
            >
                <div>{{ name }}</div>
                <div
                    id="station-time"
                    class="fs-6"
                    :title="$gettext('Station Time')"
                >
                    {{ clock }}
                </div>
            </a>

            <a
                v-if="showEditProfile"
                class="navbar-brand ms-0 flex-shrink-0"
                :href="editProfileUrl"
            >
                <icon icon="edit" />
                <span class="visually-hidden">{{ $gettext('Edit Profile') }}</span>
            </a>
        </div>
    </div>

    <sidebar-menu
        :menu="menu"
        :active="active"
    />
</template>

<script setup>
import {onMounted, ref} from "vue";
import Icon from "~/components/Common/Icon.vue";
import SidebarMenu from "~/components/Common/SidebarMenu.vue";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast";
import {useIntervalFn} from "@vueuse/core";

const props = defineProps({
    profileUrl: {
        type: String,
        required: true
    },
    editProfileUrl: {
        type: String,
        required: true
    },
    showEditProfile: {
        type: Boolean,
        default: false
    },
    menu: {
        type: Object,
        required: true
    },
    active: {
        type: String,
        default: null
    }
});

const {timeConfig, localeWithDashes} = useAzuraCast();
const {name, timezone} = useAzuraCastStation();

const clock = ref('');

useIntervalFn(() => {
    const d = new Date();
    clock.value = d.toLocaleString(
        localeWithDashes,
        {
            timeConfig,
            timeZone: timezone,
            timeStyle: 'long'
        }
    );
}, 1000, {
    immediate: true,
    immediateCallback: true
});

onMounted(() => {
    document.addEventListener('station-needs-restart', () => {
        document.querySelectorAll('.btn-restart-station').forEach((el) => {
            el.classList.remove('d-none');
        });
    });
});
</script>

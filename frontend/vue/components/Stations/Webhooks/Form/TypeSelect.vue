<template>
    <div class="row g-3">
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Generic Web Hooks')"
                :types="buildTypeInfo([
                    WEBHOOK_TYPE_GENERIC,
                    WEBHOOK_TYPE_EMAIL
                ])"
                @select="selectType"
            />

            <type-select-section
                :title="$gettext('Social Media')"
                :types="buildTypeInfo([
                    WEBHOOK_TYPE_DISCORD,
                    WEBHOOK_TYPE_TELEGRAM,
                    WEBHOOK_TYPE_MASTODON,
                    WEBHOOK_TYPE_TWITTER
                ])"
                @select="selectType"
            />
        </div>
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Station Directories')"
                :types="buildTypeInfo([
                    WEBHOOK_TYPE_TUNEIN
                ])"
                @select="selectType"
            />

            <type-select-section
                :title="$gettext('Analytics')"
                :types="buildTypeInfo([
                    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3,
                    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4,
                    WEBHOOK_TYPE_MATOMO_ANALYTICS
                ])"
                @select="selectType"
            />
        </div>
    </div>
</template>

<script setup>
import {
    WEBHOOK_TYPE_DISCORD, WEBHOOK_TYPE_EMAIL, WEBHOOK_TYPE_GENERIC,
    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3,
    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4, WEBHOOK_TYPE_MASTODON,
    WEBHOOK_TYPE_MATOMO_ANALYTICS, WEBHOOK_TYPE_TELEGRAM, WEBHOOK_TYPE_TUNEIN, WEBHOOK_TYPE_TWITTER
} from "~/components/Entity/Webhooks";
import TypeSelectSection from "~/components/Stations/Webhooks/Form/TypeSelectSection.vue";
import {get, map} from "lodash";

const props = defineProps({
    typeDetails: {
        type: Object,
        required: true
    }
});

const buildTypeInfo = (types) => map(
    types,
    (type) => {
        return {
            ...get(props.typeDetails, type),
            key: type
        };
    }
);

const emit = defineEmits(['select']);
const selectType = (type) => {
    emit('select', type);
}
</script>

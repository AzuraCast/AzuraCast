<template>
    <div class="row g-3">
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Generic Web Hooks')"
                :types="buildTypeInfo([
                    WebhookType.Generic,
                    WebhookType.Email
                ])"
                @select="selectType"
            />

            <type-select-section
                :title="$gettext('Social Media')"
                :types="buildTypeInfo([
                    WebhookType.Discord,
                    WebhookType.Telegram,
                    WebhookType.Mastodon
                ])"
                @select="selectType"
            />
        </div>
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Station Directories')"
                :types="buildTypeInfo([
                    WebhookType.TuneIn,
                    WebhookType.RadioDe,
                    WebhookType.GetMeRadio
                ])"
                @select="selectType"
            />

            <type-select-section
                :title="$gettext('Analytics')"
                :types="buildTypeInfo([
                    WebhookType.GoogleAnalyticsV4,
                    WebhookType.MatomoAnalytics
                ])"
                @select="selectType"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import {WebhookType} from "~/components/Entity/Webhooks";
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

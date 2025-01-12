<template>
    <div class="row g-3">
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Generic Web Hooks')"
                :types="reactivePick(typeDetails, [
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
                    WebhookType.GroupMe,
                    WebhookType.Mastodon,
                    WebhookType.Bluesky
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
                    WebhookType.RadioReg,
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
import {WebhookType, WebhookTypeDetail} from "~/entities/Webhooks";
import TypeSelectSection from "~/components/Stations/Webhooks/Form/TypeSelectSection.vue";
import {reactivePick} from "@vueuse/core";

const props = defineProps<{
    typeDetails: WebhookTypeDetail[]
}>();

const emit = defineEmits<{
    (e: 'select', type: WebhookType): void
}>();

const selectType = (type: WebhookType) => {
    emit('select', type);
}
</script>

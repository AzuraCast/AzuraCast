<template>
    <div class="row g-3">
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Generic Web Hooks')"
                :types="reactivePick(typeDetails, [
                    WebhookTypes.Generic,
                    WebhookTypes.Email
                ])"
                @select="selectType"
            />

            <type-select-section
                :title="$gettext('Social Media')"
                :types="reactivePick(typeDetails, [
                    WebhookTypes.Discord,
                    WebhookTypes.Telegram,
                    WebhookTypes.GroupMe,
                    WebhookTypes.Mastodon,
                    WebhookTypes.Bluesky
                ])"
                @select="selectType"
            />
        </div>
        <div class="col-md-6">
            <type-select-section
                :title="$gettext('Station Directories')"
                :types="reactivePick(typeDetails, [
                    WebhookTypes.TuneIn,
                    WebhookTypes.RadioDe,
                    WebhookTypes.RadioReg,
                    WebhookTypes.GetMeRadio
                ])"
                @select="selectType"
            />

            <type-select-section
                :title="$gettext('Analytics')"
                :types="reactivePick(typeDetails, [
                    WebhookTypes.GoogleAnalyticsV4,
                    WebhookTypes.MatomoAnalytics
                ])"
                @select="selectType"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import {WebhookTypeDetails} from "~/entities/Webhooks";
import TypeSelectSection from "~/components/Stations/Webhooks/Form/TypeSelectSection.vue";
import {reactivePick} from "@vueuse/core";
import {WebhookTypes} from "~/entities/ApiInterfaces.ts";

defineProps<{
    typeDetails: WebhookTypeDetails
}>();

const emit = defineEmits<{
    (e: 'select', type: WebhookTypes): void
}>();

const selectType = (type: WebhookTypes) => {
    emit('select', type);
}
</script>

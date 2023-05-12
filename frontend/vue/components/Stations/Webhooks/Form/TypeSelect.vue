<template>
    <b-form-group>
        <template #label>
            {{ $gettext('Select Web Hook Type') }}
        </template>

        <div class="form-row">
            <div class="col-md-6">
                <h6>{{ $gettext('Generic Web Hooks') }}</h6>
                <b-list-group class="mb-3">
                    <type-select-row
                        :type="WEBHOOK_TYPE_GENERIC"
                        :types="typeDetails"
                        @select="selectType"
                    />
                    <type-select-row
                        :type="WEBHOOK_TYPE_EMAIL"
                        :types="typeDetails"
                        @select="selectType"
                    />
                </b-list-group>

                <h6>{{ $gettext('Social Media') }}</h6>
                <b-list-group class="mb-3">
                    <type-select-row
                        :type="WEBHOOK_TYPE_DISCORD"
                        :types="typeDetails"
                        @select="selectType"
                    />
                    <type-select-row
                        :type="WEBHOOK_TYPE_TELEGRAM"
                        :types="typeDetails"
                        @select="selectType"
                    />
                    <type-select-row
                        :type="WEBHOOK_TYPE_MASTODON"
                        :types="typeDetails"
                        @select="selectType"
                    />
                    <type-select-row
                        :type="WEBHOOK_TYPE_TWITTER"
                        :types="typeDetails"
                        @select="selectType"
                    />
                </b-list-group>
            </div>
            <div class="col-md-6">
                <h6>{{ $gettext('Station Directories') }}</h6>
                <b-list-group class="mb-3">
                    <type-select-row
                        :type="WEBHOOK_TYPE_TUNEIN"
                        :types="typeDetails"
                        @select="selectType"
                    />
                </b-list-group>

                <h6>{{ $gettext('Analytics') }}</h6>
                <b-list-group class="mb-3">
                    <type-select-row
                        :type="WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3"
                        :types="typeDetails"
                        @select="selectType"
                    />
                    <type-select-row
                        :type="WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4"
                        :types="typeDetails"
                        @select="selectType"
                    />
                    <type-select-row
                        :type="WEBHOOK_TYPE_MATOMO_ANALYTICS"
                        :types="typeDetails"
                        @select="selectType"
                    />
                </b-list-group>
            </div>
        </div>
    </b-form-group>
</template>

<script setup>
import TypeSelectRow from "~/components/Stations/Webhooks/Form/TypeSelectRow.vue";
import {
    WEBHOOK_TYPE_DISCORD, WEBHOOK_TYPE_EMAIL, WEBHOOK_TYPE_GENERIC,
    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V3,
    WEBHOOK_TYPE_GOOGLE_ANALYTICS_V4, WEBHOOK_TYPE_MASTODON,
    WEBHOOK_TYPE_MATOMO_ANALYTICS, WEBHOOK_TYPE_TELEGRAM, WEBHOOK_TYPE_TUNEIN, WEBHOOK_TYPE_TWITTER
} from "~/components/Entity/Webhooks";

const props = defineProps({
    typeDetails: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['select']);
const selectType = (type) => {
    emit('select', type);
}
</script>

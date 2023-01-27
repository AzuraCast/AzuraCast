<template>
    <b-modal
        id="embed_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Embed Widgets')"
        hide-footer
        no-enforce-focus
    >
        <b-row>
            <b-col md="7">
                <b-card
                    class="mb-3"
                    no-body
                >
                    <div class="card-header bg-primary-dark">
                        <h2 class="card-title">
                            {{ $gettext('Customize') }}
                        </h2>
                    </div>
                    <b-card-body>
                        <b-row>
                            <b-col md="6">
                                <b-form-group :label="$gettext('Widget Type')">
                                    <b-form-radio-group
                                        id="embed_type"
                                        v-model="selectedType"
                                        :options="types"
                                        name="embed_type"
                                        stacked
                                    />
                                </b-form-group>
                            </b-col>
                            <b-col md="6">
                                <b-form-group :label="$gettext('Theme')">
                                    <b-form-radio-group
                                        id="embed_theme"
                                        v-model="selectedTheme"
                                        :options="themes"
                                        name="embed_theme"
                                        stacked
                                    />
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col md="5">
                <b-card
                    class="mb-3"
                    no-body
                >
                    <div class="card-header bg-primary-dark">
                        <h2 class="card-title">
                            {{ $gettext('Embed Code') }}
                        </h2>
                    </div>
                    <b-card-body>
                        <textarea
                            class="full-width form-control text-preformatted"
                            spellcheck="false"
                            style="height: 100px;"
                            readonly
                            :value="embedCode"
                        />
                        <copy-to-clipboard-button :text="embedCode" />
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-card
            class="mb-3"
            no-body
        >
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Preview') }}
                </h2>
            </div>
            <b-card-body :body-bg-variant="selectedTheme">
                <iframe
                    width="100%"
                    :src="embedUrl"
                    frameborder="0"
                    style="width: 100%; border: 0;"
                    :style="{ 'min-height': embedHeight }"
                />
            </b-card-body>
        </b-card>
    </b-modal>
</template>

<script setup>
import CopyToClipboardButton from '~/components/Common/CopyToClipboardButton';
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import embedModalProps from "./embedModalProps";

const props = defineProps({
    ...embedModalProps
});

const selectedType = ref('player');
const selectedTheme = ref('light');

const {$gettext} = useTranslate();

const types = computed(() => {
    let types = [
        {
            value: 'player',
            text: $gettext('Radio Player')
        },
        {
            value: 'history',
            text: $gettext('History')
        },
        {
            value: 'schedule',
            text: $gettext('Schedule')
        }
    ];

    if (props.stationSupportsRequests && props.enableRequests) {
        types.push({
            value: 'requests',
            text: $gettext('Requests')
        });
    }

    if (props.enableOnDemand) {
        types.push({
            value: 'ondemand',
            text: $gettext('On-Demand Media')
        });
    }

    return types;
});

const themes = computed(() => {
    return [
        {
            value: 'browser',
            text: $gettext('Browser Default')
        },
        {
            value: 'light',
            text: $gettext('Light')
        },
        {
            value: 'dark',
            text: $gettext('Dark')
        }
    ];
});

const baseEmbedUrl = computed(() => {
    switch (selectedType.value) {
        case 'history':
            return props.publicHistoryEmbedUri;

        case 'ondemand':
            return props.publicOnDemandEmbedUri;

        case 'requests':
            return props.publicRequestEmbedUri;

        case 'schedule':
            return props.publicScheduleEmbedUri;

        case 'player':
        default:
            return props.publicPageEmbedUri;
    }
});

const embedUrl = computed(() => {
    return (selectedTheme.value !== "browser")
        ? baseEmbedUrl.value + '?theme=' + selectedTheme.value
        : baseEmbedUrl.value;
});

const embedHeight = computed(() => {
    switch (selectedType.value) {
        case 'ondemand':
            return '400px';

        case 'requests':
            return '850px';

        case 'history':
            return '300px';

        case 'schedule':
            return '800px'

        case 'player':
        default:
            return '150px';
    }
});

const embedCode = computed(() => {
    return '<iframe src="' + embedUrl.value + '" frameborder="0" allowtransparency="true" style="width: 100%; min-height: ' + embedHeight.value + '; border: 0;"></iframe>';
});

const $modal = ref(); // Template Ref

const open = () => {
    $modal.value.show();
};

defineExpose({
    open
});
</script>

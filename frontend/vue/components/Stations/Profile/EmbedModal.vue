<template>
    <modal
        id="embed_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Embed Widgets')"
        hide-footer
        no-enforce-focus
    >
        <div class="row">
            <div class="col-md-7">
                <section
                    class="card mb-3"
                    role="region"
                >
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title">
                            {{ $gettext('Customize') }}
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form-group-multi-check
                                    id="embed_type"
                                    v-model="selectedType"
                                    :label="$gettext('Widget Type')"
                                    :options="types"
                                    stacked
                                    radio
                                />
                            </div>
                            <div class="col-md-6">
                                <form-group-multi-check
                                    id="embed_theme"
                                    v-model="selectedTheme"
                                    :label="$gettext('Theme')"
                                    name="embed_theme"
                                    :options="themes"
                                    stacked
                                    radio
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-md-5">
                <section
                    class="card mb-3"
                    role="region"
                >
                    <div class="card-header text-bg-primary">
                        <h2 class="card-title">
                            {{ $gettext('Embed Code') }}
                        </h2>
                    </div>
                    <div class="card-body">
                        <textarea
                            class="full-width form-control text-preformatted"
                            spellcheck="false"
                            style="height: 100px;"
                            readonly
                            :value="embedCode"
                        />
                        <copy-to-clipboard-button :text="embedCode" />
                    </div>
                </section>
            </div>
        </div>

        <section
            class="card mb-3"
            role="region"
            :data-bs-theme="selectedTheme"
        >
            <div class="card-header text-bg-primary">
                <h2 class="card-title">
                    {{ $gettext('Preview') }}
                </h2>
            </div>
            <div class="card-body">
                <iframe
                    width="100%"
                    :src="embedUrl"
                    frameborder="0"
                    style="width: 100%; border: 0;"
                    :style="{ 'min-height': embedHeight }"
                />
            </div>
        </section>
    </modal>
</template>

<script setup>
import CopyToClipboardButton from '~/components/Common/CopyToClipboardButton';
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import embedModalProps from "./embedModalProps";
import Modal from "~/components/Common/Modal.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

const props = defineProps({
    ...embedModalProps
});

const selectedType = ref('player');
const selectedTheme = ref('light');

const {$gettext} = useTranslate();

const types = computed(() => {
    const types = [
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

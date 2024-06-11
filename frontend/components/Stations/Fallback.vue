<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_fallback_file"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_fallback_file"
                class="card-title"
            >
                {{ $gettext('Custom Fallback File') }}
            </h2>
        </div>

        <info-card>
            <p class="card-text">
                {{ $gettext('This file will be played on your radio station any time no media is scheduled to play or a critical error occurs that interrupts regular broadcasting.') }}
            </p>
        </info-card>

        <div class="card-body">
            <div class="row g-3">
                <form-group
                    id="intro_file"
                    class="col-md-6"
                >
                    <template #label>
                        {{ $gettext('Select Custom Fallback File') }}
                    </template>

                    <flow-upload
                        :target-url="apiUrl"
                        :valid-mime-types="['audio/*']"
                        @success="relist"
                    />
                </form-group>

                <form-markup
                    id="current_custom_fallback_file"
                    class="col-md-6"
                >
                    <template #label>
                        {{ $gettext('Current Custom Fallback File') }}
                    </template>

                    <div v-if="downloadUrl">
                        <div class="block-buttons pt-3">
                            <a
                                class="btn btn-block btn-dark"
                                :href="downloadUrl"
                                target="_blank"
                            >
                                {{ $gettext('Download') }}
                            </a>
                            <button
                                type="button"
                                class="btn btn-block btn-danger"
                                @click="deleteFallback"
                            >
                                {{ $gettext('Clear File') }}
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        {{ $gettext('There is no existing custom fallback file associated with this station.') }}
                    </div>
                </form-markup>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import FlowUpload from '~/components/Common/FlowUpload.vue';
import InfoCard from "~/components/Common/InfoCard.vue";
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import FormMarkup from "~/components/Form/FormMarkup.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import {getStationApiUrl} from "~/router";
import {useNotify} from "~/functions/useNotify";

const apiUrl = getStationApiUrl('/fallback');

const downloadUrl = ref(null);

const {axios} = useAxios();

const relist = () => {
    axios.get(apiUrl.value).then((resp) => {
        downloadUrl.value = resp.data.links.download;
    });
};

onMounted(relist);

const {notifySuccess} = useNotify();

const deleteFallback = () => {
    axios.delete(apiUrl.value).then(() => {
        notifySuccess();
        relist();
    });
};
</script>

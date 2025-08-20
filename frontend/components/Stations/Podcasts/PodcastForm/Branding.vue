<template>
    <tab
        :label="$gettext('Branding')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-checkbox
                id="edit_form_enable_op3_prefix"
                class="col-md-12"
                :field="r$.branding_config.enable_op3_prefix"
                :label="$gettext('Enable Open Podcast Prefix Project (OP3) Analytics Support')"
            >
                <template #description>
                    {{
                        $gettext('Enable to prefix all podcast episode URLs with the OP3 analytics URL, allowing you to view statistics about your podcast audience via the OP3 service.')
                    }}
                    <a
                        href="https://op3.dev/"
                        target="_blank"
                    >
                        {{ $gettext('Open Podcast Prefix Project (OP3)') }}
                    </a>
                </template>
            </form-group-checkbox>

            <form-group-field
                id="edit_form_public_custom_html"
                class="col-md-12"
                :field="r$.branding_config.public_custom_html"
                :label="$gettext('Custom HTML for Public Pages')"
            >
                <template #default="{id, model}">
                    <codemirror-textarea
                        :id="id"
                        v-model="model.$model"
                        mode="html"
                    />
                </template>
            </form-group-field>
        </div>
    </tab>
</template>

<script setup lang="ts">
import Tab from "~/components/Common/Tab.vue";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {storeToRefs} from "pinia";
import {useStationsPodcastsForm} from "~/components/Stations/Podcasts/PodcastForm/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {computed} from "vue";

const {r$} = storeToRefs(useStationsPodcastsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.brandingTab));
</script>

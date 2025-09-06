<template>
    <tr>
        <td>
            <form-group-field
                :id="'form_edit_title_'+index"
                :field="r$.title"
            />
        </td>
        <td>
            <form-group-field
                :id="'form_edit_publish_at_'+index"
                :field="r$.publish_at"
            >
                <template #default="{id, model, fieldClass}">
                    <publish-at-fields
                        :id="id"
                        v-model="model.$model"
                        :class="fieldClass"
                    />
                </template>
            </form-group-field>
        </td>
        <td>
            <form-group-checkbox
                :id="'form_edit_explicit_'+index"
                :field="r$.explicit"
            />
        </td>
        <td>
            <form-group-field
                :id="'form_edit_season_number_'+index"
                :field="r$.season_number"
                input-type="number"
                :input-attrs="{ step: '1' }"
                clearable
            />
        </td>
        <td>
            <form-group-field
                :id="'form_edit_episode_number_'+index"
                :field="r$.episode_number"
                input-type="number"
                :input-attrs="{ step: '1' }"
                clearable
            />
        </td>
    </tr>
</template>

<script setup lang="ts">
import {required} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import PublishAtFields from "~/components/Stations/Podcasts/Common/PublishAtFields.vue";
import {useAppScopedRegle} from "~/vendor/regle.ts";
import {BatchPodcastEpisode} from "./BatchEditModal.vue";
import {Ref} from "vue";

defineProps<{
    index: number,
}>();

const row = defineModel<BatchPodcastEpisode>('row');

const {r$} = useAppScopedRegle(
    row as Ref<BatchPodcastEpisode>,
    {
        id: {required},
        title: {required},
    },
    {
        namespace: 'podcasts-batch-edit'
    }
);
</script>

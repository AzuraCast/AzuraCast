<template>
    <tr>
        <td>
            <form-group-field
                :id="'form_edit_title_'+index"
                :field="v$.title"
            />
        </td>
        <td>
            <form-group-field
                :id="'form_edit_publish_at_'+index"
                :field="v$.publish_at"
            >
                <template #default="slotProps">
                    <publish-at-fields
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        :class="slotProps.class"
                    />
                </template>
            </form-group-field>
        </td>
        <td>
            <form-group-checkbox
                :id="'form_edit_explicit_'+index"
                :field="v$.explicit"
            />
        </td>
        <td>
            <form-group-field
                :id="'form_edit_season_number_'+index"
                :field="v$.season_number"
                input-type="number"
                :input-attrs="{ step: '1' }"
                clearable
            />
        </td>
        <td>
            <form-group-field
                :id="'form_edit_episode_number_'+index"
                :field="v$.episode_number"
                input-type="number"
                :input-attrs="{ step: '1' }"
                clearable
            />
        </td>
    </tr>
</template>

<script setup lang="ts">
import {required} from "@vuelidate/validators";
import useVuelidate from "@vuelidate/core";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useVModel} from "@vueuse/core";
import PublishAtFields from "~/components/Stations/Podcasts/Common/PublishAtFields.vue";

const props = defineProps({
    index: {
        type: Number,
        required: true
    },
    row: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:row']);

const row = useVModel(props, 'row', emit);

const v$ = useVuelidate(
    {
        id: {required},
        title: {required},
        publish_at: {},
        explicit: {},
        season_number: {},
        episode_number: {}
    },
    row
);
</script>

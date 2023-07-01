<template>
    <b-form-group>
        <b-row>
            <form-group-field
                id="edit_form_playlists"
                class="col-md-12"
                :field="form.playlists"
            >
                <template #label>
                    {{ $gettext('Playlists') }}
                </template>
                <template #default="slotProps">
                    <b-form-checkbox-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        :options="options"
                        stacked
                    />
                </template>
            </form-group-field>
        </b-row>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import {map} from "lodash";
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    playlists: {
        type: Array,
        required: true
    }
});

const options = computed(() => {
    return map(props.playlists, function (row) {
        return {
            text: row.name,
            value: row.id
        };
    });
});
</script>

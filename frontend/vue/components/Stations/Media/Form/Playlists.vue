<template>
    <b-form-group>
        <b-row>
            <b-wrapped-form-group
                id="edit_form_playlists"
                class="col-md-12"
                :field="form.playlists"
            >
                <template #label>
                    {{ $gettext('Playlists') }}
                </template>
                <template #default="props">
                    <b-form-checkbox-group
                        :id="props.id"
                        v-model="props.field.$model"
                        :options="options"
                        stacked
                    />
                </template>
            </b-wrapped-form-group>
        </b-row>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {map} from "lodash";
import {computed} from "vue";

const props = defineProps({
    form: Object,
    playlists: Array
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

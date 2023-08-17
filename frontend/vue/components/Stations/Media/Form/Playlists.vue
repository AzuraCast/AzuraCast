<template>
    <div class="row">
        <form-group-multi-check
            id="edit_form_playlists"
            class="col-md-12"
            :field="form.playlists"
            :options="options"
            stacked
            :label="$gettext('Playlists')"
        />
    </div>
</template>

<script setup>
import {map} from "lodash";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

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

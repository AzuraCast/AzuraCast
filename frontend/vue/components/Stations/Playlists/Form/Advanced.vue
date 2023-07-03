<template>
    <o-tab-item :label="$gettext('Advanced')">
        <div class="row g-3">
            <form-group-multi-check
                id="edit_form_backend_options"
                class="col-md-6"
                :field="form.backend_options"
                :options="backendOptions"
                stacked
                :label="$gettext('Advanced Manual AutoDJ Scheduling Options')"
                :description="$gettext('Control how this playlist is handled by the AutoDJ software.')"
            />
        </div>
    </o-tab-item>
</template>

<script setup>
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const backendOptions = [
    {
        value: 'interrupt',
        text: $gettext('Interrupt other songs to play at scheduled time.')
    },
    {
        value: 'single_track',
        text: $gettext('Only play one track at scheduled time.')
    },
    {
        value: 'merge',
        text: $gettext('Merge playlist to play as a single track.')
    }
];
</script>

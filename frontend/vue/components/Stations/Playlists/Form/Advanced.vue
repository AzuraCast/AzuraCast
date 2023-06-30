<template>
    <o-tab-item :label="$gettext('Advanced')">
        <b-form-group>
            <div class="row g-3">
                <b-wrapped-form-group
                    id="edit_form_backend_options"
                    class="col-md-6"
                    :field="form.backend_options"
                >
                    <template #label>
                        {{ $gettext('Advanced Manual AutoDJ Scheduling Options') }}
                    </template>
                    <template #description>
                        {{ $gettext('Control how this playlist is handled by the AutoDJ software.') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-checkbox-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                        >
                            <b-form-checkbox value="interrupt">
                                {{ $gettext('Interrupt other songs to play at scheduled time.') }}
                            </b-form-checkbox>
                            <b-form-checkbox value="single_track">
                                {{ $gettext('Only play one track at scheduled time.') }}
                            </b-form-checkbox>
                            <b-form-checkbox value="merge">
                                {{ $gettext('Merge playlist to play as a single track.') }}
                            </b-form-checkbox>
                        </b-form-checkbox-group>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

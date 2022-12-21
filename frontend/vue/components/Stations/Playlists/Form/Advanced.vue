<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <div class="form-row">
                <b-wrapped-form-group class="col-md-6" id="edit_form_backend_options" :field="form.backend_options">
                    <template #label>
                        {{ $gettext('Advanced Manual AutoDJ Scheduling Options') }}
                    </template>
                    <template #description>
                        {{ $gettext('Control how this playlist is handled by the AutoDJ software.') }}
                    </template>
                    <template #default="props">
                        <b-form-checkbox-group stacked :id="props.id" v-model="props.field.$model">
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
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'PlaylistEditAdvanced',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Advanced');
        }
    }
};
</script>

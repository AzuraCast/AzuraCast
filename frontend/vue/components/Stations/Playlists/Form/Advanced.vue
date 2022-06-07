<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="edit_form_backend_options" :field="form.backend_options">
                    <template #label="{lang}">
                        <translate :key="lang">Advanced Manual AutoDJ Scheduling Options</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Control how this playlist is handled by the AutoDJ software.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox-group stacked :id="props.id" v-model="props.field.$model">
                            <b-form-checkbox value="interrupt">
                                <translate key="lang_backend_option_interrupt">Interrupt other songs to play at scheduled time.</translate>
                            </b-form-checkbox>
                            <b-form-checkbox value="single_track">
                                <translate
                                    key="lang_backend_option_single_track">Only play one track at scheduled time.</translate>
                            </b-form-checkbox>
                            <b-form-checkbox value="merge">
                                <translate
                                    key="lang_backend_option_merge">Merge playlist to play as a single track.</translate>
                            </b-form-checkbox>
                        </b-form-checkbox-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
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

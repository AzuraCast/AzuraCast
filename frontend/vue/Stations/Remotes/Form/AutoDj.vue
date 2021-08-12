<template>
    <b-tab :title="langTabTitle">
        <b-row class="mb-3">
            <b-form-group class="col-md-12" label-for="edit_form_enable_autodj">
                <template #description>
                    <translate key="lang_edit_form_enable_autodj_desc">If enabled, the AutoDJ on this installation will automatically play music to this mount point.</translate>
                </template>
                <b-form-checkbox id="edit_form_enable_autodj" v-model="form.enable_autodj.$model">
                    <translate key="lang_edit_form_enable_autodj">Broadcast AutoDJ to Remote Station</translate>
                </b-form-checkbox>
            </b-form-group>
        </b-row>

        <b-row v-if="form.enable_autodj.$model">
            <b-form-group class="col-md-6" label-for="edit_form_autodj_format">
                <template #label>
                    <translate key="lang_edit_form_autodj_format">AutoDJ Format</translate>
                </template>
                <b-form-radio-group
                    stacked
                    id="edit_form_autodj_format"
                    v-model="form.autodj_format.$model"
                    :options="formatOptions"
                ></b-form-radio-group>
            </b-form-group>

            <b-form-group class="col-md-6" label-for="edit_form_autodj_bitrate" v-if="formatSupportsBitrateOptions">
                <template #label>
                    <translate key="lang_edit_form_autodj_bitrate">AutoDJ Bitrate (kbps)</translate>
                </template>
                <b-form-radio-group
                    stacked
                    id="edit_form_autodj_bitrate"
                    v-model="form.autodj_bitrate.$model"
                    :options="bitrateOptions"
                ></b-form-radio-group>
            </b-form-group>

            <b-form-group class="col-md-6" label-for="edit_form_source_port">
                <template #label>
                    <translate key="lang_edit_form_source_port">Remote Station Source Port</translate>
                </template>
                <template #description>
                    <translate key="lang_edit_form_source_port_desc">If the port you broadcast to is different from the one you listed in the URL above, specify the source port here.</translate>
                </template>
                <b-form-input type="text" id="edit_form_source_port" v-model="form.source_port.$model"
                              :state="form.source_port.$dirty ? !form.source_port.$error : null"></b-form-input>
                <b-form-invalid-feedback>
                    <translate key="lang_error_required">This field is required.</translate>
                </b-form-invalid-feedback>
            </b-form-group>

            <b-form-group class="col-md-6" label-for="edit_form_source_mount">
                <template #label>
                    <translate key="lang_edit_form_source_mount">Remote Station Source Mountpoint/SID</translate>
                </template>
                <template #description>
                    <translate key="lang_edit_form_source_mount_desc">If the mountpoint (i.e. <code>/radio.mp3</code>) or Shoutcast SID (i.e. <code>2</code>) you broadcast to is different from the one listed above, specify the source mount point here.</translate>
                </template>
                <b-form-input type="text" id="edit_form_source_mount" v-model="form.source_mount.$model"
                              :state="form.source_mount.$dirty ? !form.source_mount.$error : null"></b-form-input>
                <b-form-invalid-feedback>
                    <translate key="lang_error_required">This field is required.</translate>
                </b-form-invalid-feedback>
            </b-form-group>

            <b-form-group class="col-md-6" label-for="edit_form_source_username">
                <template #label>
                    <translate key="lang_edit_form_source_username">Remote Station Source Username</translate>
                </template>
                <template #description>
                    <translate key="lang_edit_form_source_username_desc">If you are broadcasting using AutoDJ, enter the source username here. This may be blank.</translate>
                </template>
                <b-form-input type="text" id="edit_form_source_username" v-model="form.source_username.$model"
                              :state="form.source_username.$dirty ? !form.source_username.$error : null"></b-form-input>
                <b-form-invalid-feedback>
                    <translate key="lang_error_required">This field is required.</translate>
                </b-form-invalid-feedback>
            </b-form-group>

            <b-form-group class="col-md-6" label-for="edit_form_source_password">
                <template #label>
                    <translate key="lang_edit_form_source_password">Remote Station Source Password</translate>
                </template>
                <template #description>
                    <translate key="lang_edit_form_source_password_desc">If you are broadcasting using AutoDJ, enter the source password here.</translate>
                </template>
                <b-form-input type="text" id="edit_form_source_password" v-model="form.source_password.$model"
                              :state="form.source_password.$dirty ? !form.source_password.$error : null"></b-form-input>
                <b-form-invalid-feedback>
                    <translate key="lang_error_required">This field is required.</translate>
                </b-form-invalid-feedback>
            </b-form-group>

            <b-form-group class="col-md-6" label-for="edit_form_is_public">
                <template #description>
                    <translate key="lang_edit_form_is_public_desc">Enable to advertise this relay on "Yellow Pages" public radio directories.</translate>
                </template>
                <b-form-checkbox id="edit_form_is_public" v-model="form.is_public.$model">
                    <translate key="lang_edit_form_is_public">Publish to "Yellow Pages" Directories</translate>
                </b-form-checkbox>
            </b-form-group>
        </b-row>
    </b-tab>
</template>

<script>

export default {
    name: 'RemoteFormAutoDj',
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle() {
            return this.$gettext('AutoDJ');
        },
        formatOptions() {
            return [
                {
                    value: 'mp3',
                    text: 'MP3'
                },
                {
                    value: 'ogg',
                    text: 'OGG Vorbis'
                },
                {
                    value: 'opus',
                    text: 'OGG Opus'
                },
                {
                    value: 'aac',
                    text: 'AAC+ (MPEG4 HE-AAC v2)'
                },
                {
                    value: 'flac',
                    text: 'FLAC (OGG FLAC)'
                }
            ];
        },
        bitrateOptions() {
            let options = [];
            [32, 48, 64, 96, 128, 192, 256, 320].forEach((val) => {
                options.push({
                    value: val,
                    text: val
                });
            });
            return options;
        },
        formatSupportsBitrateOptions() {
            switch (this.form.autodj_format.$model) {
                case 'flac':
                    return false;

                case 'mp3':
                case 'ogg':
                case 'opus':
                case 'aac':
                default:
                    return true;
            }
        }
    }
};
</script>

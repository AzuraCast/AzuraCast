<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row class="mb-3">
                <b-form-group class="col-md-12" label-for="edit_form_enable_autodj">
                    <template #description>
                        <translate key="lang_edit_form_enable_autodj_desc">If enabled, the AutoDJ will automatically play music to this mount point.</translate>
                    </template>
                    <b-form-checkbox id="edit_form_enable_autodj" v-model="form.enable_autodj.$model">
                        <translate key="lang_edit_form_enable_autodj">Enable AutoDJ</translate>
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
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import { FRONTEND_ICECAST, FRONTEND_SHOUTCAST } from '../../../Entity/RadioAdapters';

export default {
    name: 'MountFormAutoDj',
    props: {
        form: Object,
        stationFrontendType: String
    },
    computed: {
        langTabTitle () {
            return this.$gettext('AutoDJ');
        },
        formatOptions () {
            switch (this.stationFrontendType) {
                case FRONTEND_ICECAST:
                    return {
                        'mp3': 'MP3',
                        'ogg': 'OGG Vorbis',
                        'opus': 'OGG Opus',
                        'aac': 'AAC+ (MPEG4 HE-AAC v2)',
                        'flac': 'FLAC (OGG FLAC)',
                    };

                case FRONTEND_SHOUTCAST:
                    return {
                        'mp3': 'MP3',
                        'ogg': 'OGG Vorbis',
                        'opus': 'OGG Opus',
                        'aac': 'AAC+ (MPEG4 HE-AAC v2)',
                        'flac': 'FLAC (OGG FLAC)',
                    };

                default:
                    return {};
            }
        },
        bitrateOptions () {
            return {
                32: '32',
                48: '48',
                64: '64',
                96: '96',
                128: '128',
                192: '192',
                256: '256',
                320: '320'
            };
        },
        formatSupportsBitrateOptions () {
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

<template>
    <b-modal size="lg" id="embed_modal" ref="modal" :title="langTitle" hide-footer>
        <b-row>
            <b-col md="7">
                <b-card class="mb-3" no-body>
                    <div class="card-header bg-primary-dark">
                        <h2 class="card-title" v-translate key="lang_embed_options">Customize</h2>
                    </div>
                    <b-card-body>
                        <b-row>
                            <b-col md="6">
                                <b-form-group :label="langEmbedType">
                                    <b-form-radio-group
                                        id="embed_type"
                                        v-model="selectedType"
                                        :options="types"
                                        name="embed_type"
                                        stacked
                                    ></b-form-radio-group>
                                </b-form-group>
                            </b-col>
                            <b-col md="6">
                                <b-form-group :label="langTheme">
                                    <b-form-radio-group
                                        id="embed_theme"
                                        v-model="selectedTheme"
                                        :options="themes"
                                        name="embed_theme"
                                        stacked
                                    ></b-form-radio-group>
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col md="5">
                <b-card class="mb-3" no-body>
                    <div class="card-header bg-primary-dark">
                        <h2 class="card-title" v-translate key="lang_embed_code">Embed Code</h2>
                    </div>
                    <b-card-body>
                        <textarea id="request_embed_url" class="full-width form-control text-preformatted" spellcheck="false" style="height: 100px;">{{ embedCode }}</textarea>
                        <copy-to-clipboard-button target="#request_embed_url"></copy-to-clipboard-button>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-card class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title" v-translate key="lang_embed_preview">Preview</h2>
            </div>
            <b-card-body :body-bg-variant="selectedTheme">
                <iframe width="100%" :src="embedUrl" frameborder="0" style="width: 100%; border: 0;" :style="{ 'min-height': this.embedHeight }"></iframe>
            </b-card-body>
        </b-card>
    </b-modal>
</template>

<script>
import CopyToClipboardButton from '../../Common/CopyToClipboardButton';

export const profileEmbedModalProps = {
    props: {
        stationSupportsStreamers: Boolean,
        stationSupportsRequests: Boolean,
        enablePublicPage: Boolean,
        enableStreamers: Boolean,
        enableOnDemand: Boolean,
        enableRequests: Boolean,
        publicPageEmbedUri: String,
        publicOnDemandEmbedUri: String,
        publicRequestEmbedUri: String,
        publicHistoryEmbedUri: String,
        publicScheduleEmbedUri: String
    }
};

export default {
    components: { CopyToClipboardButton },
    mixins: [profileEmbedModalProps],
    data () {
        let types = [
            {
                value: 'player',
                text: this.$gettext('Radio Player')
            },
            {
                value: 'history',
                text: this.$gettext('History')
            },
            {
                value: 'schedule',
                text: this.$gettext('Schedule')
            }
        ];

        if (this.stationSupportsRequests && this.enableRequests) {
            types.push({
                value: 'requests',
                text: this.$gettext('Requests')
            });
        }

        if (this.enableOnDemand) {
            types.push({
                value: 'ondemand',
                text: this.$gettext('On-Demand Media')
            });
        }

        return {
            selectedType: 'player',
            types: types,
            selectedTheme: 'light',
            themes: [
                {
                    value: 'light',
                    text: this.$gettext('Light')
                },
                {
                    value: 'dark',
                    text: this.$gettext('Dark')
                }
            ]
        };
    },
    computed: {
        langTitle () {
            return this.$gettext('Embed Widgets');
        },
        langEmbedType () {
            return this.$gettext('Widget Type');
        },
        langTheme () {
            return this.$gettext('Theme');
        },
        baseEmbedUrl () {
            switch (this.selectedType) {
                case 'history':
                    return this.publicHistoryEmbedUri;

                case 'ondemand':
                    return this.publicOnDemandEmbedUri;

                case 'requests':
                    return this.publicRequestEmbedUri;

                case 'schedule':
                    return this.publicScheduleEmbedUri;

                case 'player':
                default:
                    return this.publicPageEmbedUri;
            }
        },
        embedUrl () {
            return this.baseEmbedUrl + '?theme=' + this.selectedTheme;
        },
        bgVariant () {
            switch (this.selectedTheme) {
                case 'light':
                    return 'dark';

                case 'dark':
                    return 'light';
            }
        },
        embedHeight () {
            switch (this.selectedType) {
                case 'ondemand':
                    return '400px';

                case 'requests':
                    return '850px';

                case 'history':
                    return '300px';

                case 'schedule':
                    return '800px'

                case 'player':
                default:
                    return '150px';
            }
        },
        embedCode () {
            return '<iframe src="' + this.embedUrl + '" frameborder="0" allowtransparency="true" style="width: 100%; min-height: ' + this.embedHeight + '; border: 0;"></iframe>';
        }
    },
    methods: {
        open () {
            this.$refs.modal.show();
        }
    }
};
</script>

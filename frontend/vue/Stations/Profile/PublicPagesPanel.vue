<template>
    <section class="card mb-4" role="region">
        <template v-if="enablePublicPage">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="lang_profile_public_title">Public Pages</translate>
                    <small class="badge badge-pill badge-success" key="lang_profile_public_enabled" v-translate>Enabled</small>
                </h3>
            </div>
            <table class="table table-striped table-responsive-md mb-0">
                <colgroup>
                    <col style="width: 30%;">
                    <col style="width: 70%;">
                </colgroup>
                <tbody>
                <tr>
                    <td key="lang_profile_public_page" v-translate>Public Page</td>
                    <td>
                        <a :href="publicPageUri">{{ publicPageUri }}</a>
                    </td>
                </tr>
                <tr v-if="stationSupportsStreamers && enableStreamers">
                    <td key="lang_profile_web_dj" v-translate>Web DJ</td>
                    <td>
                        <a :href="publicWebDjUri">{{ publicWebDjUri }}</a>
                    </td>
                </tr>
                <tr v-if="enableOnDemand">
                    <td key="lang_profile_on_demand_media" v-translate>On-Demand Media</td>
                    <td>
                        <a :href="publicOnDemandUri">{{ publicOnDemandUri }}</a>
                    </td>
                </tr>
                <tr>
                    <td key="lang_profile_podcasts" v-translate>Podcasts</td>
                    <td>
                        <a :href="publicPodcastsUri">{{ publicPodcastsUri }}</a>
                    </td>
                </tr>
                <tr>
                    <td key="lang_profile_schedule" v-translate>Schedule</td>
                    <td>
                        <a :href="publicScheduleUri">{{ publicScheduleUri }}</a>
                    </td>
                </tr>
                </tbody>
            </table>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-danger" @click.prevent="doOpenEmbed">
                    <icon icon="code"></icon>
                    <translate key="lang_public_pages_disable">Embed Widgets</translate>
                </a>
                <a class="btn btn-outline-danger" :data-confirm-title="langDisablePublicPages" :href="togglePublicPageUri">
                    <icon icon="close"></icon>
                    <translate key="lang_public_pages_disable">Disable</translate>
                </a>
            </div>
            <embed-modal ref="embed_modal" v-bind="$props"></embed-modal>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="lang_profile_public_title">Public Pages</translate>
                    <small class="badge badge-pill badge-danger" key="lang_profile_public_disabled" v-translate>Disabled</small>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-success" :data-confirm-title="langEnablePublicPages" :href="togglePublicPageUri">
                    <icon icon="check"></icon>
                    <translate key="lang_public_pages_enable">Enable</translate>
                </a>
            </div>
        </template>
    </section>
</template>

<script>
import EmbedModal, { profileEmbedModalProps } from './EmbedModal';
import Icon from '../../Common/Icon';

export const profilePublicProps = {
    props: {
        stationSupportsStreamers: Boolean,
        stationSupportsRequests: Boolean,
        enablePublicPage: Boolean,
        enableStreamers: Boolean,
        enableOnDemand: Boolean,
        enableRequests: Boolean,
        userCanManageProfile: Boolean,
        publicPageUri: String,
        publicWebDjUri: String,
        publicOnDemandUri: String,
        publicPodcastsUri: String,
        publicScheduleUri: String,
        togglePublicPageUri: String
    }
};

export default {
    components: { Icon, EmbedModal },
    mixins: [profilePublicProps, profileEmbedModalProps],
    computed: {
        langDisablePublicPages () {
            return this.$gettext('Disable public pages?');
        },
        langEnablePublicPages () {
            return this.$gettext('Enable public pages?');
        },
        publicPageEmbedCode () {
            return '<iframe src="' + this.publicPageEmbedUri + '" frameborder="0" allowtransparency="true" style="width: 100%; min-height: 150px; border: 0;"></iframe>';
        },
        publicOnDemandEmbedCode () {
            return '<iframe src="' + this.publicOnDemandEmbedUri + '" frameborder="0" allowtransparency="true" style="width: 100%; min-height: 400px; border: 0;"></iframe>';
        },
        publicRequestEmbedCode () {
            return '<iframe src="' + this.publicRequestEmbedUri + '" frameborder="0" allowtransparency="true" style="width: 100%; min-height: 850px; border: 0;"></iframe>';
        }
    },
    methods: {
        doOpenEmbed () {
            this.$refs.embed_modal.open();
        }
    }
};
</script>

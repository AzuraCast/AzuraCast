<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <template #label>
                <translate key="lang_section_update_checks">AzuraCast Update Checks</translate>
            </template>

            <b-row>
                <b-form-markup id="form_release_channel">
                    <template #label>
                        <translate key="lang_release_channel">Current Release Channel</translate>
                    </template>
                    <template #description>
                        <a href="https://docs.azuracast.com/en/getting-started/updates/release-channels"
                           target="_blank">
                            <translate key="lang_switch_release_channel">Learn more about release channels in the AzuraCast docs.</translate>
                        </a>
                    </template>

                    <p class="card-text font-weight-bold">
                        {{ langReleaseChannel }}
                    </p>
                </b-form-markup>

                <b-wrapped-form-group class="col-md-12" id="edit_form_check_for_updates"
                                      :field="form.check_for_updates">
                    <template #description>
                        <translate key="lang_edit_form_check_for_updates_desc">Show new releases within your update channel on the AzuraCast homepage.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_edit_form_check_for_updates">Show Update Announcements</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>

        <b-form-group>
            <template #label>
                <translate key="lang_section_email_delivery">E-mail Delivery Service</translate>
            </template>
            <template #description>
                <translate key="lang_section_email_delivery_desc">Used for "Forgot Password" functionality, web hooks and other functions.</translate>
            </template>

            <b-row>

            </b-row>
        </b-form-group>
    </b-tab>

    ----

    'legend' => __(''),
    'description' => __(''),
    'use_grid' => true,

    'elements' => [

    'mail_enabled' => [
    'toggle',
    [
    'label' => __('Enable Mail Delivery'),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-12',
    ],
    ],

    'mail_sender_name' => [
    'text',
    [
    'label' => __('Sender Name'),
    'default' => 'AzuraCast',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'mail_sender_email' => [
    'email',
    [
    'label' => __('Sender E-mail Address'),
    'required' => false,
    'default' => '',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'mail_smtp_host' => [
    'text',
    [
    'label' => __('SMTP Host'),
    'default' => '',
    'form_group_class' => 'col-md-4',
    ],
    ],

    'mail_smtp_port' => [
    'number',
    [
    'label' => __('SMTP Port'),
    'default' => 465,
    'form_group_class' => 'col-md-3',
    ],
    ],

    'mail_smtp_secure' => [
    'toggle',
    [
    'label' => __('Use Secure (TLS) SMTP Connection'),
    'description' => __('Usually enabled for port 465, disabled for ports 587 or 25.'),

    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => true,
    'form_group_class' => 'col-md-5',
    ],
    ],

    'mail_smtp_username' => [
    'text',
    [
    'label' => __('SMTP Username'),
    'default' => '',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'mail_smtp_password' => [
    'password',
    [
    'label' => __('SMTP Password'),
    'default' => '',
    'form_group_class' => 'col-md-6',
    ],
    ],
    ],

    ----

    'legend' => __('Avatar Services'),

    'elements' => [

    'avatar_service' => [
    'radio',
    [
    'label' => __('Avatar Service'),

    'choices' => [
    App\Service\Avatar::SERVICE_LIBRAVATAR => 'Libravatar',
    App\Service\Avatar::SERVICE_GRAVATAR => 'Gravatar',
    App\Service\Avatar::SERVICE_DISABLED => __('Disabled'),
    ],
    'default' => App\Service\Avatar::DEFAULT_SERVICE,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'avatar_default_url' => [
    'text',
    [
    'label' => __('Default Avatar URL'),
    'default' => App\Service\Avatar::DEFAULT_AVATAR,
    'form_group_class' => 'col-md-6',
    ],
    ],

    ----

    'legend' => __('Album Art Services'),

    'elements' => [

    'use_external_album_art_in_apis' => [
    'toggle',
    [
    'label' => __('Check Web Services for Album Art for "Now Playing" Tracks'),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'use_external_album_art_when_processing_media' => [
    'toggle',
    [
    'label' => __('Check Web Services for Album Art When Uploading Media'),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'last_fm_api_key' => [
    'text',
    [
    'label' => __('Last.fm API Key'),
    'description' => __(
    '<a href="%s" target="_blank">Apply for an API key here</a>. This service can provide album art for tracks where
    none is available locally.',
    'https://www.last.fm/api/account/create'
    ),
    'default' => '',
    'form_group_class' => 'col-md-12',
    ],
    ],

</template>

<script>
import BFormMarkup from "~/components/Form/BFormMarkup";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'SettingsServicesTab',
    components: {BWrappedFormGroup, BFormMarkup},
    props: {
        form: Object,
        releaseChannel: String,
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Services');
        },
        langReleaseChannel() {
            return (this.releaseChannel === 'stable')
                ? this.$gettext('Stable')
                : this.$gettext('Rolling Release');
        }
    }
}
</script>

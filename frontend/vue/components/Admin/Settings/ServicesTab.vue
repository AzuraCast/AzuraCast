<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <template #label>
                <translate key="lang_section_update_checks">AzuraCast Update Checks</translate>
            </template>


            <b-row>

            </b-row>
        </b-form-group>
    </b-tab>


    'legend' => __('AzuraCast Update Checks'),

    'elements' => [

    'release_channel' => [
    'markup',
    [
    'label' => __('Current Release Channel'),
    'markup' => '<strong>' . $releaseChannelName . '</strong>',
    'description' => __(
    'For information on how to switch your release channel, visit <a href="%s" target="_blank">this page</a>.',
    'https://docs.azuracast.com/en/getting-started/updates/release-channels'
    ),
    ],
    ],

    'check_for_updates' => [
    'toggle',
    [
    'label' => __('Show Update Announcements'),
    'description' => __('Show new releases within your update channel on the AzuraCast homepage.'),
    'default' => true,
    ],
    ],

    ],

    ----

    'legend' => __('E-mail Delivery Service'),
    'description' => __('Used for "Forgot Password" functionality, web hooks and other functions.'),
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
export default {
    name: 'SettingsServicesTab',
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Services');
        },
    }
}
</script>

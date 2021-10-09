<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row>

                <b-wrapped-form-group class="col-md-6" id="edit_form_base_url" :field="form.base_url" input-type="url">
                    <template #label>
                        <translate key="lang_edit_form_base_url">Site Base URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_base_url_desc">The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if one exists) pointing to this server.</translate>
                    </template>
                </b-wrapped-form-group>

            </b-row>
        </b-form-group>

    </b-tab>

    'base_url' => [
    'url',
    [
    'label' => __('Site Base URL'),
    'description' => __(
    'The base URL where this service is located. Use either the external IP address or fully-qualified domain name (if
    one exists) pointing to this server.'
    ),
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'instance_name' => [
    'text',
    [
    'label' => __('AzuraCast Instance Name'),
    'description' => __(
    'This name will appear as a sub-header next to the AzuraCast logo, to help identify this server.'
    ),
    'form_group_class' => 'col-md-6',
    ],
    ],

    'prefer_browser_url' => [
    'toggle',
    [
    'label' => __('Prefer Browser URL (If Available)'),
    'description' => __(
    'If this setting is set to "Yes", the browser URL will be used instead of the base URL when it\'s available. Set to
    "No" to always use the base URL.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'use_radio_proxy' => [
    'toggle',
    [
    'label' => __('Use Web Proxy for Radio'),
    'description' => __(
    'By default, radio stations broadcast on their own ports (i.e. 8000). If you\'re using a service like CloudFlare or
    accessing your radio station by SSL, you should enable this feature, which routes all radio through the web ports
    (80 and 443).'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'history_keep_days' => [
    'radio',
    [
    'label' => __('Days of Playback History to Keep'),
    'description' => __(
    'Set longer to preserve more playback history and listener metadata for stations. Set shorter to save disk space. '
    ),
    'choices' => [
    14 => __('Last 14 Days'),
    30 => __('Last 30 Days'),
    60 => __('Last 60 Days'),
    365 => __('Last Year'),
    730 => __('Last 2 Years'),
    0 => __('Indefinitely'),
    ],
    'default' => App\Entity\SongHistory::DEFAULT_DAYS_TO_KEEP,
    'form_group_class' => 'col-sm-6',
    ],
    ],

    'enable_websockets' => [
    'toggle',
    [
    'label' => __('Use WebSockets for Now Playing Updates'),
    'description' => __(
    'Enables or disables the use of the newer and faster WebSocket-based system for receiving live updates on public
    players. You may need to disable this if you encounter problems with it.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'enable_advanced_features' => [
    'toggle',
    [
    'label' => __('Enable Advanced Features'),
    'description' => __(
    'Enable certain advanced features in the web interface, including advanced playlist configuration, station port
    assignment, changing base media directories and other functionality that should only be used by users who are
    comfortable with advanced functionality.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-12',
    ],
    ],
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'SettingsGeneralTab',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Settings');
        },
    }
}
</script>

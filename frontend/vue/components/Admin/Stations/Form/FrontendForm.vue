<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row>

            </b-row>
        </b-form-group>
    </b-tab>

    'select_frontend_type' => [
    'tab' => 'frontend',

    'elements' => [
    'frontend_type' => [
    'radio',
    [
    'label' => __('Broadcasting Service'),
    'description' => __('This software delivers your broadcast to the listening audience.'),
    'options' => $frontend_types,
    'default' => Adapters::DEFAULT_FRONTEND,
    ],
    ],
    ],
    ],

    'frontend_local' => [
    'use_grid' => true,
    'class' => 'frontend_fieldset',
    'tab' => 'frontend',

    'elements' => [

    StationFrontendConfiguration::SOURCE_PASSWORD => [
    'text',
    [
    'label' => __('Customize Source Password'),
    'description' => __('Leave blank to automatically generate a new password.'),
    'belongsTo' => 'frontend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationFrontendConfiguration::ADMIN_PASSWORD => [
    'text',
    [
    'label' => __('Customize Administrator Password'),
    'description' => __('Leave blank to automatically generate a new password.'),
    'belongsTo' => 'frontend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationFrontendConfiguration::PORT => [
    'text',
    [
    'label' => __('Customize Broadcasting Port'),
    'label_class' => 'advanced',
    'description' => __(
    'No other program can be using this port. Leave blank to automatically assign a port.'
    ),
    'belongsTo' => 'frontend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationFrontendConfiguration::MAX_LISTENERS => [
    'text',
    [
    'label' => __('Maximum Listeners'),
    'label_class' => 'advanced',
    'description' => __(
    'Maximum number of total listeners across all streams. Leave blank to use the default (250).'
    ),
    'belongsTo' => 'frontend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationFrontendConfiguration::CUSTOM_CONFIGURATION => [
    'textarea',
    [
    'label' => __('Custom Configuration'),
    'label_class' => 'advanced',
    'belongsTo' => 'frontend_config',
    'class' => 'text-preformatted',
    'description' => __(
    'This code will be included in the frontend configuration. You can use either JSON {"new_key": "new_value"} format
    or XML &lt;new_key&gt;new_value&lt;/new_key&gt;.'
    ).__(
    'For SHOUTcast Premium users, you can use custom configuration in this format: <code>{ "licenceid":
    "YOUR_LICENSE_ID", "userid": "YOUR_USER_ID" }</code>'
    ),
    'form_group_class' => 'col-sm-7',
    ],
    ],

    StationFrontendConfiguration::BANNED_IPS => [
    'textarea',
    [
    'label' => __('Banned IP Addresses'),
    'label_class' => 'advanced',
    'belongsTo' => 'frontend_config',
    'class' => 'text-preformatted',
    'description' => __('List one IP address or group (in CIDR format) per line.'),
    'form_group_class' => 'col-sm-5',
    ],
    ],

    StationFrontendConfiguration::BANNED_COUNTRIES => [
    'multiselect',
    [
    'label' => __('Banned Countries'),
    'label_class' => 'advanced',
    'belongsTo' => 'frontend_config',
    'description' => __('Select the countries that are not allowed to connect to the streams.'),
    'form_group_class' => 'col-sm-7',
    'options' => $countries,
    ],
    ],

    StationFrontendConfiguration::ALLOWED_IPS => [
    'textarea',
    [
    'label' => __('Allowed IP Addresses'),
    'label_class' => 'advanced',
    'belongsTo' => 'frontend_config',
    'class' => 'text-preformatted',
    'description' => __('List one IP address or group (in CIDR format) per line to explicitly allow them to connect even
    when their country is banned.'),
    'form_group_class' => 'col-sm-5',
    ],
    ],
    ],
    ],
</template>

<script>
export default {
    name: 'AdminStationsFrontendForm',
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Broadcasting');
        },
    }
}
</script>

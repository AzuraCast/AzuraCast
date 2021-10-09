<template>
    <b-tab :title="langTabTitle">

    </b-tab>

    'always_use_ssl' => [
    'toggle',
    [
    'label' => __('Always Use HTTPS'),
    'description' => __(
    'Set to "Yes" to always use "https://" secure URLs, and to automatically redirect to the secure URL when an insecure
    URL is visited.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'api_access_control' => [
    'text',
    [
    'label' => __('API "Access-Control-Allow-Origin" header'),
    'class' => 'advanced',
    'description' => __(
    '<a href="%s" target="_blank">Learn more about this header</a>. Set to * to allow all sources, or specify a list of
    origins separated by a comma (,).',
    'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin'
    ),
    'default' => '',
    'form_group_class' => 'col-md-12',
    ],
    ],
</template>

<script>
export default {
    name: 'SettingsSecurityTab',
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Security');
        },
    }
}
</script>

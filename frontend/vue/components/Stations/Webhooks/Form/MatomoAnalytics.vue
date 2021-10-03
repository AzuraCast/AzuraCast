<template>
    'matomo_url' => [
    'url',
    [
    'label' => __('Matomo Installation Base URL'),
    'description' => __('The full base URL of your Matomo installation.'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-12',
    ],
    ],

    'site_id' => [
    'text',
    [
    'label' => __('Matomo Site ID'),
    'description' => __('The numeric site ID for this site.'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'token' => [
    'text',
    [
    'label' => __('Matomo API Token'),
    'description' => __('Optionally supply an API token to allow IP address overriding.'),
    'belongsTo' => 'config',
    'form_group_class' => 'col-md-6',
    ],
    ],
</template>

<script>
export default {
    name: 'MatomoAnalytics'
}
</script>

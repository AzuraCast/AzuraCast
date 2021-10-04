<template>
    'api_info' => [
    'use_grid' => true,
    'legend' => __('Web Hook Details'),
    'legend_class' => 'd-none',
    'description' => sprintf(
    __(
    'Web hooks automatically send a HTTP POST request to the URL you specify to
    notify it any time one of the triggers you specify occurs on your station. The body of the POST message
    is the exact same as the <a href="%s" target="_blank">Now Playing API response</a> for your station.
    In order to process quickly, web hooks have a short timeout, so the responding service should be
    optimized to handle the request in under 2 seconds.'
    ),
    $router->named('api:nowplaying:index')
    ),

    'elements' => [

    'webhook_url' => [
    'url',
    [
    'label' => __('Web Hook URL'),
    'description' => __(
    'The URL that will receive the POST messages any time an event is triggered.'
    ),
    'belongsTo' => 'config',
    'required' => true,
    'label_class' => 'mb-2',
    'form_group_class' => 'col-md-6 mt-1',
    ],
    ],

    'basic_auth_username' => [
    'text',
    [
    'label' => __('Optional: HTTP Basic Authentication Username'),
    'description' => __(
    'If your web hook requires HTTP basic authentication, provide the username here.'
    ),
    'belongsTo' => 'config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'basic_auth_password' => [
    'text',
    [
    'label' => __('Optional: HTTP Basic Authentication Password'),
    'description' => __(
    'If your web hook requires HTTP basic authentication, provide the password here.'
    ),
    'belongsTo' => 'config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    ],
    ],
</template>

<script>
export default {
    name: 'Generic',
    props: {
        title: String,
        form: Object
    }
}
</script>

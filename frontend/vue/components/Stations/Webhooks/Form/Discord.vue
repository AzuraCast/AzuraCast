<template>
    'api_info' => [
    'use_grid' => true,
    'elements' => [

    'webhook_url' => [
    'url',
    [
    'label' => __('Discord Web Hook URL'),
    'description' => __('This URL is provided within the Discord application.'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    ],
    ],

    'message' => [
    'use_grid' => true,
    'legend' => __('Customize Message'),
    'legend_class' => 'd-none',
    'description' => __(
    'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now
    Playing API response</a> are avaliable for use. Any empty fields are ignored.',
    $router->named('api:nowplaying:index')
    ),

    'elements' => [

    'content' => [
    'text',
    [
    'label' => __('Main Message Content'),
    'belongsTo' => 'config',
    'default' => sprintf(__('Now playing on %s:'), '{{ station.name }}'),
    'form_group_class' => 'col-md-6',
    ],
    ],

    'title' => [
    'text',
    [
    'label' => __('Title'),
    'belongsTo' => 'config',
    'default' => '{{ now_playing.song.title }}',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'description' => [
    'text',
    [
    'label' => __('Description'),
    'belongsTo' => 'config',
    'default' => '{{ now_playing.song.artist }}',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'url' => [
    'text',
    [
    'label' => __('URL'),
    'belongsTo' => 'config',
    'default' => '{{ station.listen_url }}',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'author' => [
    'text',
    [
    'label' => __('Author Name'),
    'belongsTo' => 'config',
    'default' => '{{ live.streamer_name }}',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'thumbnail' => [
    'text',
    [
    'label' => __('Thumbnail Image URL'),
    'belongsTo' => 'config',
    'default' => '{{ now_playing.song.art }}',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'footer' => [
    'text',
    [
    'label' => __('Footer Text'),
    'belongsTo' => 'config',
    'default' => sprintf(__('Powered by %s'), $environment->getAppName()),
    'form_group_class' => 'col-md-6',
    ],
    ],

    ],
    ],
</template>

<script>
export default {
    name: 'Discord',
    props: {
        title: String,
        form: Object
    }
}
</script>

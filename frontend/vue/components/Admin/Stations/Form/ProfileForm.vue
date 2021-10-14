<template>
    'profile' => [
    'tab' => 'profile',
    'use_grid' => true,

    'elements' => [
    'name' => [
    'text',
    [
    'label' => __('Name'),
    'required' => true,
    'form_group_class' => 'col-sm-12',
    ],
    ],

    'description' => [
    'textarea',
    [
    'label' => __('Description'),
    'form_group_class' => 'col-sm-12',
    ],
    ],

    'genre' => [
    'text',
    [
    'label' => __('Genre'),
    'form_group_class' => 'col-md-6',
    ],
    ],

    'url' => [
    'text',
    [
    'label' => __('Web Site URL'),
    'description' => __(
    'Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in
    broadcast details.'
    ),
    'form_group_class' => 'col-md-6',
    ],
    ],

    'timezone' => [
    'select',
    [
    'label' => __('Time Zone'),
    'description' => __(
    'Scheduled playlists and other timed items will be controlled by this time zone.'
    ),
    'options' => $tzSelect,
    'default' => 'UTC',
    'form_group_class' => 'col-sm-12',
    ],
    ],

    'enable_public_page' => [
    'toggle',
    [
    'label' => __('Enable Public Page'),
    'description' => __('Show the station in public pages and general API results.'),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => true,
    'form_group_class' => 'col-sm-6',
    ],
    ],

    'enable_on_demand' => [
    'toggle',
    [
    'label' => __('Enable On-Demand Streaming'),
    'description' => __(
    'If enabled, music from playlists with on-demand streaming enabled will be available to stream and download via a
    specialized public page.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-sm-6',
    ],
    ],

    'default_album_art_url' => [
    'text',
    [
    'label' => __('Default Album Art URL'),
    'description' => __(
    'If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.'
    ),
    'form_group_class' => 'col-md-6',
    ],
    ],

    'enable_on_demand_download' => [
    'toggle',
    [
    'label' => __('Enable Downloads on On-Demand Page'),
    'description' => __(
    'If enabled, music from playlists with on-demand streaming enabled will be available to stream and download via a
    specialized public page.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => true,
    'form_group_class' => 'col-sm-6',
    ],
    ],

    'short_name' => [
    'text',
    [
    'label' => __('URL Stub'),
    'label_class' => 'advanced',
    'description' => __(
    'Optionally specify a short URL-friendly name, such as <code>my_station_name</code>, that will be used in this
    station\'s URLs. Leave this field blank to automatically create one based on the station name.'
    ),
    'form_group_class' => 'col-md-6',
    ],
    ],

    'api_history_items' => [
    'select',
    [
    'label' => __('Number of Recently Played Songs'),
    'label_class' => 'advanced',
    'description' => __(
    'Customize the number of songs that will appear in the "Song History" section for this station and in all public
    APIs.'
    ),
    'choices' => [
    0 => __('Disabled'),
    1 => '1',
    5 => '5',
    10 => '10',
    15 => '15',
    ],
    'default' => Station::DEFAULT_API_HISTORY_ITEMS,
    'form_group_class' => 'col-md-6',
    ],
    ],
    ],
    ],
</template>

<script>
export default {
    name: 'AdminStationsProfileForm'
}
</script>

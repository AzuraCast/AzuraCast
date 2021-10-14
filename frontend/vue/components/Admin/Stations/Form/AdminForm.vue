<template>
    'admin' => [
    'use_grid' => true,
    'tab' => 'admin',

    'elements' => [

    'media_storage_location_id' => [
    'select',
    [
    'label' => __('Media Storage Location'),
    'choices' => [],
    'form_group_class' => 'col-md-6',
    ],
    ],

    'recordings_storage_location_id' => [
    'select',
    [
    'label' => __('Live Recordings Storage Location'),
    'choices' => [],
    'form_group_class' => 'col-md-6',
    ],
    ],

    'podcasts_storage_location_id' => [
    'select',
    [
    'label' => __('Podcasts Storage Location'),
    'choices' => [],
    'form_group_class' => 'col-md-6',
    ],
    ],

    'is_enabled' => [
    'toggle',
    [
    'label' => __('Enable Broadcasting'),
    'description' => __('If disabled, the station will not broadcast or shuffle its AutoDJ.'),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'radio_base_dir' => [
    'text',
    [
    'label' => __('Base Station Directory'),
    'label_class' => 'advanced',
    'description' => __(
    'The parent directory where station playlist and configuration files are stored. Leave blank to use default
    directory.'
    ),
    'form_group_class' => 'col-md-6',
    ],
    ],

    ],
    ],
</template>

<script>
export default {
    name: 'AdminStationsAdminForm'
}
</script>

<template>
    'tracking_id' => [
    'text',
    [
    'label' => __('GA Property Tracking ID'),
    'description' => __('The property ID used to track live listeners.'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],
</template>

<script>
export default {
    name: 'GoogleAnalytics',
    props: {
        title: String,
        form: Object
    }
}
</script>

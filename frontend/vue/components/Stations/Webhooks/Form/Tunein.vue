<template>
    'station_id' => [
    'text',
    [
    'label' => __('TuneIn Station ID'),
    'description' => __('The station ID will be a numeric string that starts with the letter S.'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'partner_id' => [
    'text',
    [
    'label' => __('TuneIn Partner ID'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],

    'partner_key' => [
    'text',
    [
    'label' => __('TuneIn Partner Key'),
    'belongsTo' => 'config',
    'required' => true,
    'form_group_class' => 'col-md-6',
    ],
    ],
</template>

<script>
export default {
    name: 'Tunein',
}
</script>

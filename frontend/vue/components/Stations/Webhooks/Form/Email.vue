<template>
    'to' => [
    'text',
    [
    'label' => __('Message Recipient(s)'),
    'belongsTo' => 'config',
    'required' => true,
    'description' => __('E-mail addresses can be separated by commas.'),
    'form_group_class' => 'col-sm-6',
    ],
    ],

    'subject' => [
    'text',
    [
    'label' => __('Message Subject'),
    'belongsTo' => 'config',
    'required' => true,
    'description' => __(
    'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now
    Playing API response</a> are avaliable for use. Any empty fields are ignored.',
    $router->named('api:nowplaying:index')
    ),
    'form_group_class' => 'col-sm-6',
    ],
    ],

    'message' => [
    'textarea',
    [
    'label' => __('Message Body'),
    'belongsTo' => 'config',
    'required' => true,
    'description' => __(
    'Variables are in the form of <code>{{ var.name }}</code>. All values in the <a href="%s" target="_blank">Now
    Playing API response</a> are avaliable for use. Any empty fields are ignored.',
    $router->named('api:nowplaying:index')
    ),
    'form_group_class' => 'col-sm-12',
    ],
    ],
</template>

<script>
export default {
    name: 'Email',
}
</script>

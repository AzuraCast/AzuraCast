<template>
    <b-tab :title="langTabTitle">

    </b-tab>

    'analytics' => [
    'radio',
    [
    'label' => __('Listener Analytics Collection'),
    'description' => __(
    'Aggregate listener statistics are used to show station reports across the system. IP-based listener statistics are
    used to view live listener tracking and may be required for royalty reports.'
    ),

    'choices' => [
    Entity\Analytics::LEVEL_ALL => __(
    '<b>Full:</b> Collect aggregate listener statistics and IP-based listener statistics'
    ),
    Entity\Analytics::LEVEL_NO_IP => __(
    '<b>Limited:</b> Only collect aggregate listener statistics'
    ),
    Entity\Analytics::LEVEL_NONE => __('<b>None:</b> Do not collect any listener analytics'),
    ],
    'default' => Entity\Analytics::LEVEL_ALL,
    ],
    ],
</template>

<script>
export default {
    name: 'SettingsPrivacyTab',
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Privacy');
        },
    }
}
</script>

<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row>

            </b-row>
        </b-form-group>
    </b-tab>

    'select_backend_type' => [
    'tab' => 'backend',
    'elements' => [
    'backend_type' => [
    'radio',
    [
    'label' => __('AutoDJ Service'),
    'description' => __(
    'This software shuffles from playlists of music constantly and plays when no other radio source is available.'
    ),
    'options' => $backend_types,
    'default' => Adapters::DEFAULT_BACKEND,
    ],
    ],
    ],
    ],

    'backend_liquidsoap' => [
    'use_grid' => true,
    'class' => 'backend_fieldset',
    'tab' => 'backend',

    'elements' => [

    StationBackendConfiguration::CROSSFADE_TYPE => [
    'radio',
    [
    'label' => __('Crossfade Method'),
    'belongsTo' => 'backend_config',
    'description' => __(
    'Choose a method to use when transitioning from one song to another. Smart Mode considers the volume of the two
    tracks when fading for a smoother effect, but requires more CPU resources.'
    ),
    'choices' => [
    StationBackendConfiguration::CROSSFADE_SMART => __('Smart Mode'),
    StationBackendConfiguration::CROSSFADE_NORMAL => __('Normal Mode'),
    StationBackendConfiguration::CROSSFADE_DISABLED => __('Disable Crossfading'),
    ],
    'default' => StationBackendConfiguration::CROSSFADE_NORMAL,
    'form_group_class' => 'col-md-8',
    ],
    ],

    'crossfade' => [
    'number',
    [
    'label' => __('Crossfade Duration (Seconds)'),
    'belongsTo' => 'backend_config',
    'description' => __('Number of seconds to overlap songs.'),
    'default' => 2,
    'min' => '0.0',
    'max' => '30.0',
    'step' => '0.1',
    'form_group_class' => 'col-md-4',
    ],
    ],

    StationBackendConfiguration::USE_NORMALIZER => [
    'toggle',
    [
    'label' => __('Apply Compression and Normalization'),
    'belongsTo' => 'backend_config',
    'description' => __(
    'Compress and normalize your station\'s audio, producing a more uniform and "full" sound.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-sm-12',
    ],
    ],

    'enable_requests' => [
    'toggle',
    [
    'label' => __('Allow Song Requests'),
    'description' => __(
    'Enable listeners to request a song for play on your station. Only songs that are already in your playlists are
    requestable.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-sm-12',
    ],
    ],

    'request_delay' => [
    'number',
    [
    'label' => __('Request Minimum Delay (Minutes)'),
    'description' => __(
    'If requests are enabled, this specifies the minimum delay (in minutes) between a request being submitted and being
    played. If set to zero, a minor delay of 15 seconds is applied to prevent request floods.<br><b>Important:</b> Some
    stream licensing rules require a minimum
    delay for requests (in the US, this is currently 60 minutes). Check your local regulations for more information.'
    ),
    'default' => Station::DEFAULT_REQUEST_DELAY,
    'min' => '0',
    'max' => '1440',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'request_threshold' => [
    'number',
    [
    'label' => __('Request Last Played Threshold (Minutes)'),
    'description' => __(
    'If requests are enabled, this specifies the minimum time (in minutes) between a song playing on the radio and being
    available to request again. Set to 0 for no threshold.'
    ),
    'default' => Station::DEFAULT_REQUEST_THRESHOLD,
    'min' => '0',
    'max' => '1440',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'enable_streamers' => [
    'toggle',
    [
    'label' => __('Allow Streamers / DJs'),
    'description' => __(
    'If enabled, streamers (or DJs) will be able to connect directly to your stream and broadcast live music that
    interrupts the AutoDJ stream.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-12',
    ],
    ],

    StationBackendConfiguration::RECORD_STREAMS => [
    'toggle',
    [
    'label' => __('Record Live Broadcasts'),
    'description' => __(
    'If enabled, AzuraCast will automatically record any live broadcasts made to this station to per-broadcast
    recordings.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-4',
    ],
    ],

    StationBackendConfiguration::RECORD_STREAMS_FORMAT => [
    'radio',
    [
    'label' => __('Live Broadcast Recording Format'),
    'choices' => [
    StationMountInterface::FORMAT_MP3 => 'MP3',
    StationMountInterface::FORMAT_OGG => 'OGG Vorbis',
    StationMountInterface::FORMAT_OPUS => 'OGG Opus',
    StationMountInterface::FORMAT_AAC => 'AAC+ (MPEG4 HE-AAC v2)',
    ],
    'default' => StationMountInterface::FORMAT_MP3,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-4',
    ],
    ],

    'record_streams_bitrate' => [
    'radio',
    [
    'label' => __('Live Broadcast Recording Bitrate (kbps)'),
    'choices' => [
    32 => '32',
    48 => '48',
    64 => '64',
    96 => '96',
    128 => '128',
    192 => '192',
    256 => '256',
    320 => '320',
    ],
    'default' => 128,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-4',
    ],
    ],

    'disconnect_deactivate_streamer' => [
    'number',
    [
    'label' => __('Deactivate Streamer on Disconnect (Seconds)'),
    'description' => __(
    'Number of seconds to deactivate station streamer on manual disconnect. Set to 0 to disable deactivation
    completely.'
    ),
    'default' => 0,
    'min' => '0',
    'step' => '1',
    'form_group_class' => 'col-md-4',
    ],
    ],

    StationBackendConfiguration::DJ_PORT => [
    'text',
    [
    'label' => __('Customize DJ/Streamer Port'),
    'label_class' => 'advanced',
    'description' => __(
    'No other program can be using this port. Leave blank to automatically assign a port.<br><b>Note:</b> The port after
    this one (n+1) will automatically be used for legacy connections.'
    ),
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::TELNET_PORT => [
    'text',
    [
    'label' => __('Customize Internal Request Processing Port'),
    'label_class' => 'advanced',
    'description' => __(
    'This port is not used by any external process. Only modify this port if the assigned port is in use. Leave blank to
    automatically assign a port.'
    ),
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    'dj_buffer' => [
    'number',
    [
    'label' => __('DJ/Streamer Buffer Time (Seconds)'),
    'description' => __(
    'The number of seconds of signal to store in case of interruption. Set to the lowest value that your DJs can use
    without stream interruptions.'
    ),
    'default' => 5,
    'min' => 0,
    'max' => 60,
    'step' => 1,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::DJ_MOUNT_POINT => [
    'text',
    [
    'label' => __('Customize DJ/Streamer Mount Point'),
    'label_class' => 'advanced',
    'description' => __(
    'If your streaming software requires a specific mount point path, specify it here. Otherwise, use the default.'
    ),
    'belongsTo' => 'backend_config',
    'default' => '/',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::USE_REPLAYGAIN => [
    'toggle',
    [
    'label' => __('Use Replaygain Metadata'),
    'label_class' => 'advanced',
    'belongsTo' => 'backend_config',
    'description' => __(
    'Instruct Liquidsoap to use any replaygain metadata associated with a song to control its volume level.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::AUTODJ_QUEUE_LENGTH => [
    'number',
    [
    'label' => __('AutoDJ Queue Length'),
    'description' => __(
    'If using AzuraCast\'s AutoDJ, this determines how many songs in advance the AutoDJ will automatically fill the
    queue.'
    ),
    'default' => StationBackendConfiguration::DEFAULT_QUEUE_LENGTH,
    'min' => 1,
    'max' => 25,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::USE_MANUAL_AUTODJ => [
    'toggle',
    [
    'label' => __('Manual AutoDJ Mode'),
    'label_class' => 'advanced',
    'description' => __(
    'This mode disables AzuraCast\'s AutoDJ management, using Liquidsoap itself to manage song playback. "Next Song" and
    some other features will not be available.'
    ),
    'selected_text' => __('Yes'),
    'deselected_text' => __('No'),
    'default' => false,
    'belongsTo' => 'backend_config',
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::CHARSET => [
    'radio',
    [
    'label' => __('Character Set Encoding'),
    'label_class' => 'advanced',
    'description' => __(
    'For most cases, use the default UTF-8 encoding. The older ISO-8859-1 encoding can be used if accepting connections
    from SHOUTcast 1 DJs or using other legacy software.'
    ),
    'belongsTo' => 'backend_config',
    'default' => 'UTF-8',
    'choices' => [
    'UTF-8' => 'UTF-8',
    'ISO-8859-1' => 'ISO-8859-1',
    ],
    'form_group_class' => 'col-md-6',
    ],
    ],

    StationBackendConfiguration::DUPLICATE_PREVENTION_TIME_RANGE => [
    'number',
    [
    'label' => __('Duplicate Prevention Time Range (Minutes)'),
    'description' => __(
    'This specifies the time range (in minutes) of the song history that the duplicate song prevention algorithm should
    take into account.'
    ),
    'belongsTo' => 'backend_config',
    'default' => StationBackendConfiguration::DEFAULT_DUPLICATE_PREVENTION_TIME_RANGE,
    'min' => '0',
    'max' => '1440',
    'form_group_class' => 'col-md-6',
    ],
    ],
    ],
    ],
</template>

<script>
export default {
    name: 'AdminStationsBackendForm',
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('AutoDJ');
        },
    }
}
</script>

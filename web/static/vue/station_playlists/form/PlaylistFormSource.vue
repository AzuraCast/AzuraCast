<template>
    <b-tab :title="langTabTitle">
        <b-form-group :label="langTabTitle">
            <b-row>
                <b-col md="12">
                    <b-form-group label-for="edit_form_is_enabled">
                        <template v-slot:label v-translate>
                            Is Enabled
                        </template>
                        <template v-slot:description v-translate>
                            If set to "No", the playlist will not be included in radio playback, but can still be
                            managed.
                        </template>
                        <b-form-checkbox v-model="form.is_enabled" id="edit_form_is_enabled" switch v-translate>
                            Is Enabled
                        </b-form-checkbox>
                    </b-form-group>
                </b-col>
                <b-col md="6">
                    <b-form-group label-for="edit_form_name">
                        <template v-slot:label v-translate>
                            Playlist Name
                        </template>
                        <b-form-input type="text" id="edit_form_name" v-model="form.name.$model"
                                      :state="form.name.$dirty ? !form.name.$error : null"></b-form-input>
                        <b-form-invalid-feedback v-translate>
                            This field is required.
                        </b-form-invalid-feedback>
                    </b-form-group>
                </b-col>
            </b-row>
        </b-form-group>

        'select_source' => [
        'tab' => 'source',
        'elements' => [
        'source' => [
        'radio',
        [
        'label' => __('Source'),
        'choices' => [
        StationPlaylist::SOURCE_SONGS => '<b>' . __('Song-Based Playlist') . ':</b> ' . __('A playlist containing media
        files hosted on this server.'),
        StationPlaylist::SOURCE_REMOTE_URL => '<b>' . __('Remote URL Playlist') . ':</b> ' . __('A playlist that
        instructs the station to play from a remote URL.'),
        ],
        'default' => StationPlaylist::SOURCE_SONGS,
        'required' => true,
        ],
        ],
        ],
        ],

        'source_' . StationPlaylist::SOURCE_SONGS => [
        'use_grid' => true,
        'class' => 'source_fieldset',
        'tab' => 'source',

        'elements' => [

        'order' => [
        'radio',
        [
        'label' => __('Song Playback Order'),
        'required' => true,
        'choices' => [
        StationPlaylist::ORDER_SHUFFLE => __('Shuffled'),
        StationPlaylist::ORDER_RANDOM => __('Random'),
        StationPlaylist::ORDER_SEQUENTIAL => __('Sequential'),
        ],
        'default' => StationPlaylist::ORDER_SHUFFLE,
        'form_group_class' => 'col-md-6',
        ],
        ],

        'import' => [
        'file',
        [
        'label' => __('Import Existing Playlist'),
        'description' => __('Select an existing playlist file to add its contents to this playlist. PLS and M3U are
        supported.'),
        'required' => false,
        'type' => [
        'audio/x-scpls',
        'application/vnd.apple.mpegurl',
        'application/mpegurl',
        'application/x-mpegurl',
        'audio/mpegurl',
        'audio/x-mpegurl',
        'application/octet-stream',
        ],
        'form_group_class' => 'col-md-6',
        'button_text' => __('Select File'),
        'button_icon' => 'cloud_upload',
        ],
        ],

        'include_in_requests' => [
        'toggle',
        [
        'label' => __('Allow Requests from This Playlist'),
        'description' => __('If requests are enabled for your station, users will be able to request media that is on
        this playlist.'),
        'selected_text' => __('Yes'),
        'deselected_text' => __('No'),
        'default' => true,
        'form_group_class' => 'col-md-6',
        ],
        ],

        'is_jingle' => [
        'toggle',
        [
        'label' => __('Hide Metadata from Listeners ("Jingle Mode")'),
        'label_class' => 'advanced',
        'description' => __('Enable this setting to prevent metadata from being sent to the AutoDJ for files in this
        playlist. This is useful if the playlist contains jingles or bumpers.'),
        'selected_text' => __('Yes'),
        'deselected_text' => __('No'),
        'default' => false,
        'form_group_class' => 'col-md-6',
        ],
        ],

        ],
        ],

        'source_' . StationPlaylist::SOURCE_REMOTE_URL => [
        'use_grid' => true,
        'class' => 'source_fieldset',
        'tab' => 'source',

        'elements' => [

        'remote_url' => [
        'text',
        [
        'label' => __('Remote URL'),
        'form_group_class' => 'col-md-6',
        ],
        ],

        'remote_type' => [
        'radio',
        [
        'label' => __('Remote URL Type'),
        'default' => StationPlaylist::REMOTE_TYPE_STREAM,
        'choices' => [
        StationPlaylist::REMOTE_TYPE_STREAM => __('Direct Stream URL'),
        StationPlaylist::REMOTE_TYPE_PLAYLIST => __('Playlist (M3U/PLS) URL'),
        ],
        'form_group_class' => 'col-md-6',
        ],
        ],

        'remote_buffer' => [
        'number',
        [
        'label' => __('Remote Playback Buffer (Seconds)'),
        'label_class' => 'advanced mb-2',
        'description' => __('The length of playback time that Liquidsoap should buffer when playing this remote
        playlist. Shorter times may lead to intermittent playback on unstable connections.'),
        'default' => StationPlaylist::DEFAULT_REMOTE_BUFFER,
        'min' => 0,
        'max' => 120,
        'form_group_class' => 'col-md-6',
        ],
        ],

        ],
        ],

    </b-tab>
</template>

<script>
  export default {
    name: 'PlaylistEditSource',
    props: {
      form: Object
    },
    computed: {
      langTabTitle () {
        return this.$gettext('Source')
      }
    }
  }
</script>
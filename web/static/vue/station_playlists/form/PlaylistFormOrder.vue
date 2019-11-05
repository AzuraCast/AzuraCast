<template>
    <b-tab :title="langTabTitle">
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

        'select_type' => [
        'use_grid' => true,
        'tab' => 'scheduling',

        'elements' => [
        'type' => [
        'radio',
        [
        'label' => __('Scheduling'),
        'choices' => [
        StationPlaylist::TYPE_DEFAULT => '<b>' . __('General Rotation') . ':</b> ' . __('Plays all day, shuffles with
        other standard playlists based on weight.'),
        StationPlaylist::TYPE_ONCE_PER_X_SONGS => '<b>' . __('Once per x Songs') . ':</b> ' . __('Play exactly once
        every <i>x</i> songs.'),
        StationPlaylist::TYPE_ONCE_PER_X_MINUTES => '<b>' . __('Once Per x Minutes') . ':</b> ' . __('Play exactly once
        every <i>x</i> minutes.'),
        StationPlaylist::TYPE_ONCE_PER_HOUR => '<b>' . __('Once per Hour') . ':</b> ' . __('Play once per hour at the
        specified minute.'),
        StationPlaylist::TYPE_ADVANCED => '<b>' . __('Advanced') . '</b>: ' . __('Manually define how this playlist is
        used in Liquidsoap configuration. <a href="%s" target="_blank">Learn about Advanced Playlists</a>',
        'https://www.azuracast.com/help/advanced_playlists.html'),
        ],
        'default' => StationPlaylist::TYPE_DEFAULT,
        'required' => true,
        'form_group_class' => 'col-md-6',
        ],
        ],

        'backend_options' => [
        'checkboxes',
        [
        'label' => __('AutoDJ Scheduling Options'),
        'label_class' => 'advanced',
        'description' => __('Control how this playlist is handled by the AutoDJ software.') . '<br>' .
        __('<b>Warning:</b> These functions are internal to Liquidsoap and will affect how your AutoDJ works.'),
        'choices' => [
        StationPlaylist::OPTION_INTERRUPT_OTHER_SONGS => __('Interrupt other songs to play at scheduled time.'),
        StationPlaylist::OPTION_LOOP_PLAYLIST_ONCE => __('Only loop through playlist once.'),
        StationPlaylist::OPTION_PLAY_SINGLE_TRACK => __('Only play one track at scheduled time.'),
        StationPlaylist::OPTION_MERGE => __('Merge playlist to play as a single track.'),
        ],
        'form_group_class' => 'col-md-6',
        ],
        ],
        ],
        ],

        'type_' . StationPlaylist::TYPE_DEFAULT => [
        'class' => 'type_fieldset',
        'tab' => 'scheduling',

        'elements' => [

        'include_in_automation' => [
        'toggle',
        [
        'label' => __('Include in Automated Assignment'),
        'description' => __('If auto-assignment is enabled, use this playlist as one of the targets for songs to be
        redistributed into. This will overwrite the existing contents of this playlist.'),
        'selected_text' => __('Yes'),
        'deselected_text' => __('No'),
        'default' => false,
        ],
        ],

        ],
        ],

        'type_' . StationPlaylist::TYPE_ONCE_PER_X_SONGS => [
        'class' => 'type_fieldset',
        'tab' => 'scheduling',

        'elements' => [

        'play_per_songs' => [
        'number',
        [
        'label' => __('Number of Songs Between Plays'),
        'description' => __('This playlist will play every $x songs, where $x is specified below.'),
        'default' => 1,
        'min' => 0,
        'max' => 150,
        ],
        ],

        ],
        ],

        'type_' . StationPlaylist::TYPE_ONCE_PER_X_MINUTES => [
        'class' => 'type_fieldset',
        'tab' => 'scheduling',

        'elements' => [

        'play_per_minutes' => [
        'number',
        [
        'label' => __('Number of Minutes Between Plays'),
        'description' => __('This playlist will play every $x minutes, where $x is specified below.'),
        'default' => 1,
        'min' => 0,
        'max' => 360,
        ],
        ],

        ],
        ],

        'type_' . StationPlaylist::TYPE_ONCE_PER_HOUR => [
        'class' => 'type_fieldset',
        'tab' => 'scheduling',

        'elements' => [

        'play_per_hour_minute' => [
        'number',
        [
        'label' => __('Minute of Hour to Play'),
        'description' => __('Specify the minute of every hour that this playlist should play.'),
        'default' => 0,
        'min' => 0,
        'max' => 59,
        ],
        ],

        ],
        ],

    </b-tab>
</template>

<script>
  export default {
    name: 'PlaylistEditOrder',
    props: {
      form: Object
    },
    computed: {
      langTabTitle () {
        return this.$gettext('Order')
      }
    }
  }
</script>
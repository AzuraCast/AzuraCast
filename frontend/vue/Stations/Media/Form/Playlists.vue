<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-12" label-for="edit_form_playlists" :label="langTitle">
                    <b-form-checkbox-group
                        id="edit_form_playlists"
                        v-model="form.playlists.$model"
                        :options="options"
                        stacked
                    ></b-form-checkbox-group>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import _ from 'lodash';

export default {
    name: 'MediaFormPlaylists',
    props: {
        form: Object,
        playlists: Array
    },
    computed: {
        langTitle () {
            return this.$gettext('Playlists');
        },
        options () {
            return _.map(this.playlists, function (row) {
                return {
                    text: row.name,
                    value: row.id
                };
            });
        }
    }
};
</script>

<template>
    <b-modal id="move_file" size="xl" centered ref="modal" :title="langHeader">
        <b-row class="mb-3 align-items-center">
            <b-col md="6">
                <b-button size="sm" variant="primary" @click="pageBack" :disabled="dirHistory.length === 0">
                    <icon icon="chevron_left"></icon>
                    <translate key="lang_btn_back">Back</translate>
                </b-button>
            </b-col>
            <b-col md="6" class="text-right">
                <h6 class="m-0">{{ destinationDirectory }}</h6>
            </b-col>
        </b-row>
        <b-row>
            <b-col md="12">
                <data-table ref="datatable" id="station_media" :show-toolbar="false"
                            :selectable="false" :fields="fields"
                            :api-url="listDirectoriesUrl" :request-config="requestConfig">
                    <template v-slot:cell(directory)="row">
                        <div class="is_dir">
                            <span class="file-icon">
                                <icon icon="folder"></icon>
                            </span>

                            <a href="#" @click.prevent="enterDirectory(row.item.path)">
                                {{ row.item.name }}
                            </a>
                        </div>
                    </template>
                </data-table>
            </b-col>
        </b-row>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" @click="doMove">
                <translate key="lang_btn_move">Move to Directory</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import DataTable from '../../Common/DataTable.vue';
import axios from 'axios';
import _ from 'lodash';
import Icon from '../../Common/Icon';
import handleAxiosError from '../../Function/handleAxiosError';

export default {
    name: 'MoveFilesModal',
    components: { Icon, DataTable },
    props: {
        selectedItems: Object,
        currentDirectory: String,
        batchUrl: String,
        listDirectoriesUrl: String
    },
    data () {
        return {
            destinationDirectory: '',
            dirHistory: [],
            fields: [
                { key: 'directory', label: this.$gettext('Directory'), sortable: false }
            ]
        };
    },
    computed: {
        langHeader () {
            let headerText = this.$gettext('Move %{ num } File(s) to');
            return this.$gettextInterpolate(headerText, { num: this.selectedItems.all.length });
        }
    },
    methods: {
        close () {
            this.dirHistory = [];
            this.destinationDirectory = '';

            this.$refs.modal.hide();
        },
        doMove () {
            (this.selectedItems.all.length) && axios.put(this.batchUrl, {
                'do': 'move',
                'currentDirectory': this.currentDirectory,
                'directory': this.destinationDirectory,
                'files': this.selectedItems.files,
                'dirs': this.selectedItems.directories
            }).then((resp) => {
                let allItemNames = _.map(this.selectedItems.all, 'name');
                let notifyMessage = this.$gettext('Files moved:');
                notify('<b>' + notifyMessage + '</b><br>' + allItemNames.join('<br>'), 'success');

                this.close();
                this.$emit('relist');
            }).catch((err) => {
                let notifyMessage = this.$gettext('An error occurred and your request could not be completed.');
                handleAxiosError(err, notifyMessage);

                this.close();
                this.$emit('relist');
            });
        },
        enterDirectory (path) {
            this.dirHistory.push(path);
            this.destinationDirectory = path;

            this.$refs.datatable.refresh();
        },
        pageBack: function (e) {
            e.preventDefault();

            this.dirHistory.pop();

            let newDirectory = this.dirHistory.slice(-1)[0];
            if (typeof newDirectory === 'undefined' || null === newDirectory) {
                newDirectory = '';
            }
            this.destinationDirectory = newDirectory;

            this.$refs.datatable.refresh();
        },
        requestConfig (config) {
            config.params.currentDirectory = this.destinationDirectory;
            config.params.csrf = this.csrf;

            return config;
        }
    }
};
</script>

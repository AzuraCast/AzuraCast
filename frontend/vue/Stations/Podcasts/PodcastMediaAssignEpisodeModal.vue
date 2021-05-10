<template>
    <b-modal size="lg" id="assign_modal" ref="modal" :title="langTitle">
        <data-table ref="datatable" id="station_podcast_episodes_assign" paginated :fields="fields" :api-url="episodesListUrl">
            <template v-slot:cell(title)="row">
                <h5 class="m-0">{{ row.item.title }}</h5>
            </template>
            <template v-slot:cell(podcast_media)="row">
                <template v-if="row.item.has_media">
                    <span>{{ row.item.podcast_media.original_name }}</span>
                    <br/>
                    <small>{{ row.item.podcast_media.path }}</small>
                </template>
            </template>
            <template v-slot:cell(actions)="row">
                <b-button-group size="sm">
                    <b-button size="sm" variant="primary" @click.prevent="doAssign(row.item.links.assign)">
                        <translate key="lang_btn_edit">Assign</translate>
                    </b-button>
                </b-button-group>
            </template>
        </data-table>

        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
    import DataTable from '../../Common/DataTable';
    import axios from 'axios';

    export default {
        name: 'AssignModal',
        components: {
            DataTable
        },
        props: {
            listUrl: String,
            stationTimeZone: String
        },
        data () {
            return {
                fields: [
                    { key: 'title', label: this.$gettext('Episode'), sortable: false },
                    { key: 'podcast_media', label: this.$gettext('File'), sortable: false },
                    { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                ],
                podcastMedia: null
            };
        },
        computed: {
            langTitle () {
                return this.$gettext('Assign to episode');
            },
            episodesListUrl () {
                if (this.podcastMedia === null) {
                    return '';
                }
                return this.listUrl + '?podcast_media_id=' + this.podcastMedia.id;
            }
        },
        methods: {
            relist () {
                if (this.$refs.datatable) {
                    this.$refs.datatable.refresh();
                }
            },
            doAssign(url) {
                let buttonText = this.$gettext('Assign');
                let buttonConfirmText = this.$gettext('Assign media to episode?');

                Swal.fire({
                    title: buttonConfirmText,
                    confirmButtonText: buttonText,
                    confirmButtonColor: '#2296f3',
                    showCancelButton: true,
                    focusCancel: true
                }).then((value) => {
                    if (value) {
                        axios.put(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success');

                            this.relist();
                            this.close();
                        }).catch((err) => {
                            console.error(err);
                            if (err.response.message) {
                                notify('<b>' + err.response.message + '</b>', 'danger');
                            }
                        });
                    }
                });
            },
            assign (podcastMedia) {
                this.podcastMedia = podcastMedia;
                this.$refs.modal.show();
            },
            close () {
                this.$refs.modal.hide();
            }
        }
    };
</script>

<template>
    <div>
        <b-card no-body>
            <b-card-header header-bg-variant="primary-dark">
                <b-row class="align-items-center">
                    <b-col md="6">
                        <h2 class="card-title" key="lang_podcasts" v-translate>Podcasts</h2>
                    </b-col>
                </b-row>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doCreate">
                    <i class="material-icons" aria-hidden="true">add</i>
                    <translate key="lang_add_podcasts">Add Podcast</translate>
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="station_podcasts" paginated :fields="fields" :responsive="false"
                        :api-url="listUrl">
                <template v-slot:cell(title)="row">
                    <h5 class="m-0">{{ row.item.title }}</h5>
                </template>
                <template v-slot:cell(num_episodes)="row">
                    {{ countEpisodes(row.item.episodes) }}
                </template>
                <template v-slot:cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                            <translate key="lang_btn_edit">Edit</translate>
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                            <translate key="lang_btn_delete">Delete</translate>
                        </b-button>
                        <b-button size="sm" variant="dark" :href="stationUrl+'/podcast/'+row.item.id+'/episodes'">
                            <translate key="lang_btn_episodes">Episodes</translate>
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </b-card>

        <edit-modal ref="editPodcastModal" :create-url="listUrl" :station-time-zone="stationTimeZone"
            :language-options="languageOptions" :categories-options="categoriesOptions" @relist="relist"></edit-modal>
    </div>
</template>

<script>
    import DataTable from '../Common/DataTable';
    import EditModal from './Podcasts/PodcastEditModal';
    import axios from 'axios';

    export default {
        name: 'StationPodcasts',
        components: { EditModal, DataTable },
        props: {
            listUrl: String,
            categoriesUrl: String,
            stationUrl: String,
            locale: String,
            stationTimeZone: String,
            languageOptions: Object,
            categoriesOptions: Object
        },
        data () {
            return {
                fields: [
                    { key: 'actions', label: this.$gettext('Actions'), sortable: false },
                    { key: 'title', label: this.$gettext('Podcast'), sortable: false },
                    { key: 'num_episodes', label: this.$gettext('# Episodes'), sortable: false }
                ]
            };
        },
        computed: {
            langAllPodcastsTab () {
                return this.$gettext('All Podcasts');
            }
        },
        mounted () {
            moment.relativeTimeThreshold('ss', 1);
            moment.relativeTimeRounding(function (value) {
                return Math.round(value * 10) / 10;
            });
        },
        methods: {
            formatTime (time) {
                return moment(time).tz(this.stationTimeZone).format('LT');
            },
            formatLength (length) {
                return moment.duration(length, 'seconds').humanize();
            },
            countEpisodes (episodes) {
                return episodes.length;
            },
            relist () {
                if (this.$refs.datatable) {
                    this.$refs.datatable.refresh();
                }
            },
            doCreate () {
                this.$refs.editPodcastModal.create();
            },
            doEdit (url) {
                this.$refs.editPodcastModal.edit(url);
            },
            doDelete (url) {
                let buttonText = this.$gettext('Delete');
                let buttonConfirmText = this.$gettext('Delete podcast?');

                Swal.fire({
                    title: buttonConfirmText,
                    confirmButtonText: buttonText,
                    confirmButtonColor: '#e64942',
                    showCancelButton: true,
                    focusCancel: true
                }).then((value) => {
                    if (value) {
                        axios.delete(url).then((resp) => {
                            notify('<b>' + resp.data.message + '</b>', 'success');

                            this.relist();
                        }).catch((err) => {
                            console.error(err);
                            if (err.response.message) {
                                notify('<b>' + err.response.message + '</b>', 'danger');
                            }
                        });
                    }
                });
            }
        }
    };
</script>

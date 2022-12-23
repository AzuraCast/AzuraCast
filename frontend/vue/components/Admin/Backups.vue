<template>
    <div>
        <h2 class="outside-card-header mb-1">{{ $gettext('Backups') }}</h2>

        <div class="card-deck">
            <section class="card mb-3" role="region">
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('Automatic Backups') }}
                        <enabled-badge :enabled="settings.backupEnabled"></enabled-badge>
                    </h2>
                </b-card-header>

                <b-overlay variant="card" :show="settingsLoading">
                    <div v-if="settings.backupEnabled" class="card-body">
                        <p v-if="settings.backupLastRun > 0" class="card-text">
                            {{ $gettext('Last run:') }}
                            {{ toRelativeTime(settings.backupLastRun) }}
                        </p>
                        <p v-else class="card-text">
                            {{ $gettext('Never run') }}
                        </p>
                    </div>
                </b-overlay>

                <div class="card-actions">
                    <b-button variant="outline-primary" @click.prevent="doConfigure">
                        <icon icon="settings"></icon>
                        {{ $gettext('Configure') }}
                    </b-button>
                    <b-button v-if="settings.backupEnabled && settings.backupLastOutput !== ''"
                              variant="outline-secondary" @click.prevent="showLastOutput">
                        <icon icon="assignment"></icon>
                        {{ $gettext('Most Recent Backup Log') }}
                    </b-button>
                </div>
            </section>

            <section class="card mb-3" role="region">
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('Restoring Backups') }}
                    </h2>
                </b-card-header>

                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('To restore a backup from your host computer, run:') }}
                    </p>

                    <pre v-if="isDocker"><code>./docker.sh restore path_to_backup.zip</code></pre>
                    <pre v-else><code>/var/azuracast/www/bin/console azuracast:restore path_to_backup.zip</code></pre>

                    <p class="card-text text-warning">
                        {{
                            $gettext('Note that restoring a backup will clear your existing database. Never restore backup files from untrusted users.')
                        }}
                    </p>
                </div>
            </section>
        </div>

        <section class="card mb-3" role="region">
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Backups') }}
                </h2>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button variant="outline-primary" @click.prevent="doRunBackup">
                    <icon icon="send"></icon>
                    {{ $gettext('Run Manual Backup') }}
                </b-button>
            </b-card-body>

            <data-table ref="datatable" id="api_keys" :fields="fields" :api-url="listUrl">
                <template #cell(timestamp)="row">
                    {{ toLocaleTime(row.item.timestamp) }}
                </template>
                <template #cell(size)="row">
                    {{ formatFileSize(row.item.size) }}
                </template>
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button size="sm" variant="primary" :href="row.item.links.download" target="_blank">
                            {{ $gettext('Download') }}
                        </b-button>
                        <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.delete)">
                            {{ $gettext('Delete') }}
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </section>

        <admin-backups-configure-modal ref="configureModal" :settings-url="settingsUrl"
                                       :storage-locations="storageLocations"
                                       @relist="relist"></admin-backups-configure-modal>

        <admin-backups-run-backup-modal ref="runBackupModal" :run-backup-url="runBackupUrl"
                                        :storage-locations="storageLocations"
                                        @relist="relist"></admin-backups-run-backup-modal>

        <admin-backups-last-output-modal ref="lastOutputModal"
                                         :last-output="settings.backupLastOutput"></admin-backups-last-output-modal>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";
import AdminBackupsLastOutputModal from "./Backups/LastOutputModal";
import {DateTime} from 'luxon';
import formatFileSize from "~/functions/formatFileSize";
import AdminBackupsConfigureModal from "~/components/Admin/Backups/ConfigureModal";
import AdminBackupsRunBackupModal from "~/components/Admin/Backups/RunBackupModal";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {useAzuraCast} from "~/vendor/azuracast";

export default {
    name: 'AdminBackups',
    components: {
        EnabledBadge,
        AdminBackupsRunBackupModal,
        AdminBackupsConfigureModal,
        AdminBackupsLastOutputModal,
        DataTable,
        Icon
    },
    props: {
        listUrl: String,
        settingsUrl: String,
        runBackupUrl: String,
        storageLocations: Object,
        isDocker: Boolean,
    },
    data() {
        return {
            fields: [
                {
                    key: 'basename',
                    isRowHeader: true,
                    label: this.$gettext('File Name'),
                    sortable: false
                },
                {
                    key: 'timestamp',
                    label: this.$gettext('Last Modified'),
                    sortable: false
                },
                {
                    key: 'size',
                    label: this.$gettext('Size'),
                    sortable: false
                },
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ],
            settingsLoading: false,
            settings: {
                backupEnabled: false,
                backupLastRun: null,
                backupLastOutput: '',
            }
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.settingsLoading = true;
            this.$wrapWithLoading(
                this.axios.get(this.settingsUrl)
            ).then((resp) => {
                this.settings = {
                    backupEnabled: resp.data.backup_enabled,
                    backupLastRun: resp.data.backup_last_run,
                    backupLastOutput: resp.data.backup_last_output
                };
                this.settingsLoading = false;
            });

            this.$refs.datatable.relist();
        },
        toRelativeTime(timestamp) {
            return DateTime.fromSeconds(timestamp).toRelative();
        },
        toLocaleTime(timestamp) {
            const {timeConfig} = useAzuraCast();

            return DateTime.fromSeconds(timestamp).toLocaleString(
                {...DateTime.DATETIME_SHORT, timeConfig}
            );
        },
        formatFileSize(size) {
            return formatFileSize(size);
        },
        showLastOutput() {
            this.$refs.lastOutputModal.show();
        },
        doConfigure() {
            this.$refs.configureModal.open();
        },
        doRunBackup() {
            this.$refs.runBackupModal.open();
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete Backup?')
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        }
    }
}
</script>

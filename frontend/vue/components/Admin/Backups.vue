<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('Backups') }}
        </h2>

        <div class="card-deck">
            <section
                class="card mb-3"
                role="region"
            >
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        {{ $gettext('Automatic Backups') }}
                        <enabled-badge :enabled="settings.backupEnabled" />
                    </h2>
                </b-card-header>

                <b-overlay
                    variant="card"
                    :show="settingsLoading"
                >
                    <div
                        v-if="settings.backupEnabled"
                        class="card-body"
                    >
                        <p
                            v-if="settings.backupLastRun > 0"
                            class="card-text"
                        >
                            {{ $gettext('Last run:') }}
                            {{ toRelativeTime(settings.backupLastRun) }}
                        </p>
                        <p
                            v-else
                            class="card-text"
                        >
                            {{ $gettext('Never run') }}
                        </p>
                    </div>
                </b-overlay>

                <div class="card-actions">
                    <b-button
                        variant="outline-primary"
                        @click.prevent="doConfigure"
                    >
                        <icon icon="settings" />
                        {{ $gettext('Configure') }}
                    </b-button>
                    <b-button
                        v-if="settings.backupEnabled && settings.backupLastOutput !== ''"
                        variant="outline-secondary"
                        @click.prevent="showLastOutput"
                    >
                        <icon icon="assignment" />
                        {{ $gettext('Most Recent Backup Log') }}
                    </b-button>
                </div>
            </section>

            <section
                class="card mb-3"
                role="region"
            >
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

        <section
            class="card mb-3"
            role="region"
        >
            <b-card-header header-bg-variant="primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Backups') }}
                </h2>
            </b-card-header>

            <b-card-body body-class="card-padding-sm">
                <b-button
                    variant="outline-primary"
                    @click.prevent="doRunBackup"
                >
                    <icon icon="send" />
                    {{ $gettext('Run Manual Backup') }}
                </b-button>
            </b-card-body>

            <data-table
                id="api_keys"
                ref="$dataTable"
                :fields="fields"
                :api-url="listUrl"
            >
                <template #cell(actions)="row">
                    <b-button-group size="sm">
                        <b-button
                            size="sm"
                            variant="primary"
                            :href="row.item.links.download"
                            target="_blank"
                        >
                            {{ $gettext('Download') }}
                        </b-button>
                        <b-button
                            size="sm"
                            variant="danger"
                            @click.prevent="doDelete(row.item.links.delete)"
                        >
                            {{ $gettext('Delete') }}
                        </b-button>
                    </b-button-group>
                </template>
            </data-table>
        </section>

        <admin-backups-configure-modal
            ref="$configureModal"
            :settings-url="settingsUrl"
            :storage-locations="storageLocations"
            @relist="relist"
        />

        <admin-backups-run-backup-modal
            ref="$runBackupModal"
            :run-backup-url="runBackupUrl"
            :storage-locations="storageLocations"
            @relist="relist"
        />

        <admin-backups-last-output-modal
            ref="$lastOutputModal"
            :last-output="settings.backupLastOutput"
        />
    </div>
</template>

<script setup>
import Icon from "~/components/Common/Icon.vue";
import DataTable from "~/components/Common/DataTable.vue";
import AdminBackupsLastOutputModal from "./Backups/LastOutputModal.vue";
import {DateTime} from 'luxon';
import formatFileSize from "~/functions/formatFileSize";
import AdminBackupsConfigureModal from "~/components/Admin/Backups/ConfigureModal.vue";
import AdminBackupsRunBackupModal from "~/components/Admin/Backups/RunBackupModal.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {useAzuraCast} from "~/vendor/azuracast";
import {onMounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    listUrl: {
        type: String,
        required: true
    },
    settingsUrl: {
        type: String,
        required: true
    },
    runBackupUrl: {
        type: String,
        required: true
    },
    storageLocations: {
        type: Object,
        required: true
    },
    isDocker: {
        type: Boolean,
        default: true
    },
});

const settingsLoading = ref(false);

const blankSettings = {
    backupEnabled: false,
    backupLastRun: null,
    backupLastOutput: '',
};

const settings = ref({...blankSettings});

const {$gettext} = useTranslate();
const {timeConfig} = useAzuraCast();

const fields = [
    {
        key: 'basename',
        isRowHeader: true,
        label: $gettext('File Name'),
        sortable: false
    },
    {
        key: 'timestamp',
        label: $gettext('Last Modified'),
        sortable: false,
        formatter: (value) => {
            return DateTime.fromSeconds(value).toLocaleString(
                {...DateTime.DATETIME_SHORT, ...timeConfig}
            );
        }
    },
    {
        key: 'size',
        label: $gettext('Size'),
        sortable: false,
        formatter: (value) => formatFileSize(value)
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $dataTable = ref(); // DataTable

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const relist = () => {
    settingsLoading.value = true;
    wrapWithLoading(
        axios.get(props.settingsUrl)
    ).then((resp) => {
        settings.value = {
            backupEnabled: resp.data.backup_enabled,
            backupLastRun: resp.data.backup_last_run,
            backupLastOutput: resp.data.backup_last_output
        };
        settingsLoading.value = false;
    });

    $dataTable.value.relist();
};

onMounted(relist);

const toRelativeTime = (timestamp) => {
    return DateTime.fromSeconds(timestamp).toRelative();
};

const $lastOutputModal = ref(); // AdminBackupsLastOutputModal
const showLastOutput = () => {
    $lastOutputModal.value.show();
};

const $configureModal = ref(); // AdminBackupsConfigureModal
const doConfigure = () => {
    $configureModal.value.open();
};

const $runBackupModal = ref(); // AdminBackupsRunBackupModal
const doRunBackup = () => {
    $runBackupModal.value.open();
};

const {confirmDelete} = useSweetAlert();

const doDelete = (url) => {
    confirmDelete({
        title: $gettext('Delete Backup?')
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};
</script>

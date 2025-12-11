<template>
    <div class="row row-of-cards">
        <div class="col-md-6">
            <card-page header-id="hdr_automatic_backups">
                <template #header="{id}">
                    <h2
                        :id="id"
                        class="card-title"
                    >
                        {{ $gettext('Automatic Backups') }}
                        <enabled-badge :enabled="settings.backup_enabled"/>
                    </h2>
                </template>

                <div
                    v-if="settings.backup_enabled"
                    class="card-body"
                >
                    <p
                        v-if="settings.backup_last_run > 0"
                        class="card-text"
                    >
                        {{ $gettext('Last run:') }}
                        {{ timestampToRelative(settings.backup_last_run) }}
                    </p>
                    <p
                        v-else
                        class="card-text"
                    >
                        {{ $gettext('Never run') }}
                    </p>
                </div>

                <template #footer_actions>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="doConfigure"
                    >
                        <icon-ic-settings/>

                        <span>
                            {{ $gettext('Configure') }}
                        </span>
                    </button>
                    <button
                        v-if="settings.backup_enabled && settings.backup_last_output !== ''"
                        type="button"
                        class="btn btn-secondary"
                        @click="showLastOutput"
                    >
                        <icon-ic-assignment/>

                        <span>
                            {{ $gettext('Most Recent Backup Log') }}
                        </span>
                    </button>
                </template>
            </card-page>
        </div>
        <div class="col-md-6">
            <card-page
                header-id="hdr_restoring_backups"
                :title="$gettext('Restoring Backups')"
            >
                <div class="card-body">
                    <p class="card-text">
                        {{ $gettext('To restore a backup from your host computer, run:') }}
                    </p>

                    <pre v-if="props.isDocker"><code>./docker.sh restore path_to_backup.zip</code></pre>
                    <pre
                        v-else><code>/var/azuracast/www/bin/console azuracast:restore path_to_backup.zip</code></pre>

                    <p class="card-text text-warning">
                        {{
                            $gettext('Note that restoring a backup will clear your existing database. Never restore backup files from untrusted users.')
                        }}
                    </p>
                </div>
            </card-page>
        </div>
    </div>

    <card-page
        header-id="hdr_backups"
        :title="$gettext('Backups')"
    >
        <template #actions>
            <button
                type="button"
                class="btn btn-primary"
                @click="doRunBackup"
            >
                <icon-ic-send/>

                <span>
                    {{ $gettext('Run Manual Backup') }}
                </span>
            </button>
        </template>

        <data-table
            id="api_keys"
            :fields="fields"
            :provider="itemProvider"
        >
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <a
                        class="btn btn-primary"
                        :href="row.item.links.download"
                        target="_blank"
                    >
                        {{ $gettext('Download') }}
                    </a>
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="doDelete(row.item.links.delete)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>
    </card-page>

    <admin-backups-configure-modal
        ref="$configureModal"
        :settings-url="settingsUrl"
        :storage-locations="props.storageLocations"
        @relist="() => relist()"
    />

    <admin-backups-run-backup-modal
        ref="$runBackupModal"
        :run-backup-url="runBackupUrl"
        :storage-locations="props.storageLocations"
        @relist="() => relist()"
    />

    <admin-backups-last-output-modal
        ref="$lastOutputModal"
        :last-output="settings.backup_last_output ?? ''"
    />
</template>

<script setup lang="ts">
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import AdminBackupsLastOutputModal from "~/components/Admin/Backups/LastOutputModal.vue";
import formatFileSize from "~/functions/formatFileSize";
import AdminBackupsConfigureModal from "~/components/Admin/Backups/ConfigureModal.vue";
import AdminBackupsRunBackupModal from "~/components/Admin/Backups/RunBackupModal.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {useAzuraCast} from "~/vendor/azuracast";
import {useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import CardPage from "~/components/Common/CardPage.vue";
import {useLuxon} from "~/vendor/luxon";
import {ApiAdminBackup, ApiAdminVueBackupProps} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {BackupSettings} from "~/components/Admin/BackupsWrapper.vue";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import IconIcAssignment from "~icons/ic/baseline-assignment";
import IconIcSend from "~icons/ic/baseline-send";
import IconIcSettings from "~icons/ic/baseline-settings";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const props = defineProps<ApiAdminVueBackupProps & {
    settings: BackupSettings
}>();

const emit = defineEmits<HasRelistEmit>();

const {getApiUrl} = useApiRouter();
const listUrl = getApiUrl('/admin/backups');
const runBackupUrl = getApiUrl('/admin/backups/run');
const settingsUrl = getApiUrl('/admin/settings/backup');

const {$gettext} = useTranslate();
const {timeConfig} = useAzuraCast();
const {DateTime, timestampToRelative} = useLuxon();

type Row = Required<ApiAdminBackup>

const fields: DataTableField<Row>[] = [
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

const itemProvider = useApiItemProvider<Row>(
    listUrl,
    [QueryKeys.AdminBackups]
);

const relist = async () => {
    await itemProvider.refresh();
    emit('relist');
};

const $lastOutputModal = useTemplateRef('$lastOutputModal');
const showLastOutput = () => {
    $lastOutputModal.value?.show();
};

const $configureModal = useTemplateRef('$configureModal');
const doConfigure = () => {
    $configureModal.value?.open();
};

const $runBackupModal = useTemplateRef('$runBackupModal');
const doRunBackup = () => {
    $runBackupModal.value?.open();
};

const {doDelete} = useConfirmAndDelete(
    $gettext('Delete Backup?'),
    () => {
        void relist();
    }
);
</script>

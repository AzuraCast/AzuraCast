<template>
    <full-height-card>
        <template #header>
            <div class="d-flex align-items-center">
                <div class="flex-shrink">
                    <h2 class="card-title py-2">
                        <template v-if="stationName">
                            {{ stationName }}
                        </template>
                        <template v-else>
                            {{ $gettext('On-Demand Media') }}
                        </template>
                    </h2>
                </div>
                <div class="flex-fill text-end">
                    <inline-player ref="player" />
                </div>
            </div>
        </template>

        <template #default>
            <data-table
                id="public_on_demand"
                paginated
                select-fields
                :fields="fields"
                :provider="listItemProvider"
            >
                <template #cell(download_url)="row">
                    <play-button
                        class="btn-lg"
                        :stream="{
                            title: row.item.media.text,
                            url: row.item.download_url,
                        }"
                    />
                    <template v-if="showDownloadButton">
                        <a
                            class="name btn btn-lg p-0 ms-2"
                            :href="row.item.download_url"
                            target="_blank"
                            :title="$gettext('Download')"
                        >
                            <icon-ic-cloud-download/>
                        </a>
                    </template>
                </template>
                <template #cell(art)="row">
                    <album-art :src="row.item.media.art" />
                </template>
            </data-table>
        </template>
    </full-height-card>
</template>

<script setup lang="ts">
import InlinePlayer from "~/components/InlinePlayer.vue";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import {useTranslate} from "~/vendor/gettext";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import FullHeightCard from "~/components/Public/FullHeightCard.vue";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import IconIcCloudDownload from "~icons/ic/baseline-cloud-download";

interface OnDemandCustomField {
    display_key: string,
    key: string,
    label: string,
}

const props = withDefaults(
    defineProps<{
        listUrl: string,
        stationName: string,
        customFields?: OnDemandCustomField[],
        showDownloadButton?: boolean
    }>(),
    {
        customFields: () => ([]),
        showDownloadButton: false,
    }
);

const {$gettext} = useTranslate();

const fields: DataTableField[] = [
    {key: 'download_url', label: ' ', class: 'shrink'},
    {key: 'art', label: $gettext('Art'), class: 'shrink'},
    {
        key: 'title',
        label: $gettext('Title'),
        sortable: true,
        selectable: true,
        formatter: (_value, _key, item) => item.media.title,
    },
    {
        key: 'artist',
        label: $gettext('Artist'),
        sortable: true,
        selectable: true,
        formatter: (_value, _key, item) => item.media.artist,
    },
    {
        key: 'album',
        label: $gettext('Album'),
        sortable: true,
        selectable: true,
        visible: false,
        formatter: (_value, _key, item) => item.media.album
    }
];

for (const field of props.customFields.slice()) {
    fields.push({
        key: field.display_key,
        label: field.label,
        sortable: true,
        selectable: true,
        visible: false,
        formatter: (_value, _key, item) => item.media.custom_fields[field.key]
    });
}

const listItemProvider = useApiItemProvider(
    props.listUrl,
    [
        QueryKeys.PublicOnDemand
    ]
);
</script>

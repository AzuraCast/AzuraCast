<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_streams"
    >
        <div class="card-header text-bg-primary">
            <h3
                id="hdr_streams"
                class="card-title"
            >
                {{ $gettext('Streams') }}
            </h3>
        </div>
        <table class="table table-striped table-responsive mb-0">
            <colgroup>
                <col style="width: 2%;">
                <col style="width: 78%;">
                <col style="width: 20%;">
            </colgroup>
            <template v-if="station.mounts.length > 0">
                <thead>
                    <tr>
                        <th colspan="2">
                            {{ $gettext('Local Streams') }}
                        </th>
                        <th class="text-end">
                            <icon
                                class="align-middle"
                                icon="headset"
                            />
                            {{ $gettext('Listeners') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="mount in station.mounts"
                        :key="mount.id"
                        class="align-middle"
                    >
                        <td class="pe-1">
                            <play-button
                                class="btn-xl"
                                :url="mount.url"
                                is-stream
                            />
                        </td>
                        <td class="ps-1">
                            <h6 class="mb-1">
                                {{ mount.name }}
                            </h6>
                            <a
                                :href="mount.url"
                                target="_blank"
                            >{{ mount.url }}</a>
                        </td>
                        <td class="ps-1 text-end">
                            <span class="listeners-total ps-1">{{ mount.listeners.total }}</span> {{ $gettext('Total') }}<br>
                            <small>
                                <span class="listeners-unique pe-1">{{ mount.listeners.unique }}</span>
                                {{ $gettext('Unique') }}
                            </small>
                        </td>
                    </tr>
                </tbody>
            </template>

            <template v-if="station.remotes.length > 0">
                <thead>
                    <tr>
                        <th colspan="2">
                            {{ $gettext('Remote Relays') }}
                        </th>
                        <th class="text-end">
                            {{ $gettext('Listeners') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="remote in station.remotes"
                        :key="remote.id"
                        class="align-middle"
                    >
                        <td class="pe-1">
                            <play-button
                                class="btn-xl"
                                :url="remote.url"
                                is-stream
                            />
                        </td>
                        <td class="ps-1">
                            <h6 class="mb-1">
                                {{ remote.name }}
                            </h6>
                            <a
                                :href="remote.url"
                                target="_blank"
                            >{{ remote.url }}</a>
                        </td>
                        <td class="ps-1 text-end">
                            <icon
                                class="sm align-middle"
                                icon="headset"
                            />
                            <span class="listeners-total ps-1">{{ remote.listeners.total }}</span><br>
                            <small>
                                <span class="listeners-unique pe-1">{{ remote.listeners.unique }}</span>
                                {{ $gettext('Unique') }}
                            </small>
                        </td>
                    </tr>
                </tbody>
            </template>

            <template v-if="station.hls_enabled">
                <thead>
                    <tr>
                        <th colspan="2">
                            {{ $gettext('HTTP Live Streaming (HLS)') }}
                        </th>
                        <th class="text-end">
                            {{ $gettext('Listeners') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="align-middle">
                        <td class="pe-1">
                            <play-button
                                class="btn-xl"
                                :url="station.hls_url"
                                is-stream
                                is-hls
                            />
                        </td>
                        <td class="ps-1">
                            <a
                                :href="station.hls_url"
                                target="_blank"
                            >{{ station.hls_url }}</a>
                        </td>
                        <td class="ps-1 text-end">
                            <icon
                                class="sm align-middle"
                                icon="headset"
                            />
                            <span class="listeners-total ps-1">{{ station.hls_listeners }}</span>
                        </td>
                    </tr>
                </tbody>
            </template>
        </table>
        <div class="card-body buttons">
            <a
                class="btn btn-primary"
                :href="station.playlist_pls_url"
            >
                <icon icon="file_download" />
                <span>
                    {{ $gettext('Download PLS') }}
                </span>
            </a>
            <a
                class="btn btn-primary"
                :href="station.playlist_m3u_url"
            >
                <icon icon="file_download" />
                <span>
                    {{ $gettext('Download M3U') }}
                </span>
            </a>
        </div>
    </section>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";

const props = defineProps({
    station: {
        type: Object,
        required: true
    }
});
</script>

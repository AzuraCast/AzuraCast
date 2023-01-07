<template>
    <section
        class="card mb-4"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                {{ $gettext('Streams') }}
            </h3>
        </div>
        <table class="table table-sm table-striped table-responsive mb-0">
            <colgroup>
                <col style="width: 2%;">
                <col style="width: 78%;">
                <col style="width: 20%;">
            </colgroup>
            <template v-if="np.station.mounts.length > 0">
                <thead>
                    <tr>
                        <th colspan="2">
                            {{ $gettext('Local Streams') }}
                        </th>
                        <th class="text-right">
                            {{ $gettext('Listeners') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="mount in np.station.mounts"
                        :key="mount.id"
                        class="align-middle"
                    >
                        <td class="pr-1">
                            <play-button
                                icon-class="outlined"
                                :url="mount.url"
                                is-stream
                            />
                        </td>
                        <td class="pl-1">
                            <h6 class="mb-0">
                                {{ mount.name }}
                            </h6>
                            <a
                                :href="mount.url"
                                target="_blank"
                            >{{ mount.url }}</a>
                        </td>
                        <td class="pl-1 text-right">
                            <icon
                                class="sm align-middle"
                                icon="headset"
                            />
                            <span class="listeners-total pl-1">{{ mount.listeners.total }}</span><br>
                            <small>
                                <span class="listeners-unique pr-1">{{ mount.listeners.unique }}</span>
                                {{ $gettext('Unique') }}
                            </small>
                        </td>
                    </tr>
                </tbody>
            </template>

            <template v-if="np.station.remotes.length > 0">
                <thead>
                    <tr>
                        <th colspan="2">
                            {{ $gettext('Remote Relays') }}
                        </th>
                        <th class="text-right">
                            {{ $gettext('Listeners') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="remote in np.station.remotes"
                        :key="remote.id"
                        class="align-middle"
                    >
                        <td class="pr-1">
                            <play-button
                                icon-class="outlined"
                                :url="remote.url"
                                is-stream
                            />
                        </td>
                        <td class="pl-1">
                            <h6 class="mb-0">
                                {{ remote.name }}
                            </h6>
                            <a
                                :href="remote.url"
                                target="_blank"
                            >{{ remote.url }}</a>
                        </td>
                        <td class="pl-1 text-right">
                            <icon
                                class="sm align-middle"
                                icon="headset"
                            />
                            <span class="listeners-total pl-1">{{ remote.listeners.total }}</span><br>
                            <small>
                                <span class="listeners-unique pr-1">{{ remote.listeners.unique }}</span>
                                {{ $gettext('Unique') }}
                            </small>
                        </td>
                    </tr>
                </tbody>
            </template>

            <template v-if="np.station.hls_enabled">
                <thead>
                    <tr>
                        <th colspan="2">
                            {{ $gettext('HTTP Live Streaming (HLS)') }}
                        </th>
                        <th class="text-right">
                            {{ $gettext('Listeners') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="align-middle">
                        <td class="pr-1">
                            <play-button
                                icon-class="outlined"
                                :url="np.station.hls_url"
                                is-stream
                                is-hls
                            />
                        </td>
                        <td class="pl-1">
                            <a
                                :href="np.station.hls_url"
                                target="_blank"
                            >{{ np.station.hls_url }}</a>
                        </td>
                        <td class="pl-1 text-right">
                            <icon
                                class="sm align-middle"
                                icon="headset"
                            />
                            <span class="listeners-total pl-1">{{ np.station.hls_listeners }}</span>
                        </td>
                    </tr>
                </tbody>
            </template>
        </table>
        <div class="card-actions">
            <a
                class="btn btn-outline-primary"
                :href="np.station.playlist_pls_url"
            >
                <icon icon="file_download" />
                {{ $gettext('Download PLS') }}
            </a>
            <a
                class="btn btn-outline-primary"
                :href="np.station.playlist_m3u_url"
            >
                <icon icon="file_download" />
                {{ $gettext('Download M3U') }}
            </a>
        </div>
    </section>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";

const props = defineProps({
    np: {
        type: Object,
        required: true
    }
});
</script>

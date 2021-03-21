<template>
    <section class="card mb-4" role="region">
        <div class="card-header bg-primary-dark">
            <h3 class="card-title" key="lang_profile_streams" v-translate>Streams</h3>
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
                    <th colspan="2" key="lang_streams_local" v-translate>Local Streams</th>
                    <th key="lang_streams_listeners" v-translate>Listeners</th>
                </tr>
                </thead>
                <tbody>
                <tr class="align-middle" v-for="mount in np.station.mounts">
                    <td class="pr-1">
                        <a class="btn-audio has-listener" href="#" v-bind:data-url="mount.url" @click.prevent="toggle(mount.url)">
                            <icon class="outlined" icon="play_circle"></icon>
                        </a>
                    </td>
                    <td class="pl-1">
                        <h6 class="mb-0">{{ mount.name }}</h6>
                        <a v-bind:href="mount.url" target="_blank">{{ mount.url }}</a>
                    </td>
                    <td class="pl-1 text-right">
                        <icon class="sm align-middle" icon="headset"></icon>
                        <span class="listeners-total">{{ mount.listeners.total }}</span><br>
                        <small>
                            <span class="listeners-unique">{{ mount.listeners.unique }}</span>
                            <translate key="lang_streams_unique">Unique</translate>
                        </small>
                    </td>
                </tr>
                </tbody>
            </template>

            <template v-if="np.station.remotes.length > 0">
                <thead>
                <tr>
                    <th colspan="2" key="lang_streams_remote" v-translate>Remote Relays</th>
                    <th key="lang_streams_listeners" v-translate>Listeners</th>
                </tr>
                </thead>
                <tbody>
                <tr class="align-middle" v-for="remote in np.station.remotes">
                    <td class="pr-1">
                        <a class="btn-audio has-listener" href="#" v-bind:data-url="remote.url" @click.prevent="toggle(remote.url)">
                            <icon class="outlined" icon="play_circle"></icon>
                        </a>
                    </td>
                    <td class="pl-1">
                        <h6 class="mb-0">{{ remote.name }}</h6>
                        <a v-bind:href="remote.url" target="_blank">{{ remote.url }}</a>
                    </td>
                    <td class="pl-1 text-right">
                        <icon class="sm align-middle" icon="headset"></icon>
                        <span class="listeners-total">{{ remote.listeners.total }}</span><br>
                        <small>
                            <span class="listeners-unique">{{ remote.listeners.unique }}</span>
                            <translate key="lang_streams_unique">Unique</translate>
                        </small>
                    </td>
                </tr>
                </tbody>
            </template>
        </table>
        <div class="card-actions">
            <a class="btn btn-outline-primary" :href="np.station.playlist_pls_url">
                <icon icon="file_download"></icon>
                <translate key="lang_streams_download_pls">Download PLS</translate>
            </a>
            <a class="btn btn-outline-primary" :href="np.station.playlist_m3u_url">
                <icon icon="file_download"></icon>
                <translate key="lang_streams_download_m3u">Download M3U</translate>
            </a>
        </div>
    </section>
</template>

<script>
import Icon from '../../Common/Icon';

export default {
    components: { Icon },
    props: {
        np: Object
    },
    methods: {
        toggle (url) {
            this.$eventHub.$emit('player_toggle', url);
        }
    }
};
</script>

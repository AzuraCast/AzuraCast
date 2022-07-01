<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                <translate key="lang_connection_info_hdr">Connection Information</translate>
            </h2>
        </div>
        <div class="card-body">
            <h3 class="card-subtitle mt-0">
                <translate key="lang_connection_icecast_hdr">Icecast Clients</translate>
            </h3>
            <dl>
                <dt class="mb-1">
                    <translate key="lang_connection_info_server">Server:</translate>
                </dt>
                <dd>
                    <code>{{ connectionInfo.serverUrl }}</code>
                </dd>
                <dd v-if="connectionInfo.ip">
                    <translate
                        key="lang_connection_ip">You may need to connect directly via your IP address:</translate>
                    <code>{{ connectionInfo.ip }}</code>
                </dd>

                <dt class="mb-1">
                    <translate key="lang_connection_info_port">Port:</translate>
                </dt>
                <dd><code>{{ connectionInfo.streamPort }}</code></dd>

                <dt class="mb-1">
                    <translate key="lang_connection_info_mount">Mount Name:</translate>
                </dt>
                <dd><code>{{ connectionInfo.djMountPoint }}</code></dd>
            </dl>
        </div>
        <div class="card-body">
            <h3 class="card-subtitle mt-0">
                <translate key="lang_connection_shoutcast_hdr">Shoutcast Clients</translate>
            </h3>
            <dl>
                <dt class="mb-1">
                    <translate key="lang_connection_info_server">Server:</translate>
                </dt>
                <dd>
                    <code>{{ connectionInfo.serverUrl }}</code>
                </dd>
                <dd v-if="connectionInfo.ip">
                    <translate
                        key="lang_connection_ip">You may need to connect directly via your IP address:</translate>
                    <code>{{ connectionInfo.ip }}</code>
                </dd>

                <dt class="mb-1">
                    <translate key="lang_connection_info_port">Port:</translate>
                </dt>
                <dd><code>{{ connectionInfo.streamPort }}</code></dd>
                <dd>
                    <translate key="lang_connection_info_legacy">For some clients, use port:</translate>
                    <code>{{ connectionInfo.streamPort + 1 }}</code>
                </dd>

                <dt class="mb-1">
                    <translate key="lang_connection_info_password">Password:</translate>
                </dt>
                <dd>
                    <code>dj_username:dj_password</code>
                    <translate key="lang_or">or</translate>
                    <code>dj_username,dj_password</code>
                </dd>
            </dl>
        </div>
        <div class="card-body">
            <p class="card-text">
                <translate key="lang_connection_wiki">Setup instructions for broadcasting software are available on the AzuraCast wiki.</translate>
                <br>
                <a href="https://docs.azuracast.com/en/user-guide/streaming-software" target="_blank">
                    <translate key="lang_connection_wiki_link">AzuraCast Wiki</translate>
                </a>
            </p>
        </div>
    </div>
</template>
<script>
export default {
    name: 'ConnectionInfo',
    props: {
        connectionInfo: Object
    }
}
</script>

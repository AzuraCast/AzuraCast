<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_connection_info"
    >
        <div class="card-header text-bg-primary">
            <h2
                id="hdr_connection_info"
                class="card-title"
            >
                {{ $gettext('Connection Information') }}
            </h2>
        </div>
        <div class="card-body">
            <h3 class="card-subtitle mt-0">
                {{ $gettext('Icecast Clients') }}
            </h3>
            <dl>
                <dt class="mb-1">
                    {{ $gettext('Server:') }}
                </dt>
                <dd>
                    <code>{{ connectionInfo.serverUrl }}</code>
                </dd>
                <dd v-if="connectionInfo.ip">
                    {{ $gettext('You may need to connect directly via your IP address:') }}
                    <code>{{ connectionInfo.ip }}</code>
                </dd>

                <dt class="mb-1">
                    {{ $gettext('Port:') }}
                </dt>
                <dd><code>{{ connectionInfo.streamPort }}</code></dd>

                <dt class="mb-1">
                    {{ $gettext('Mount Name:') }}
                </dt>
                <dd><code>{{ connectionInfo.djMountPoint }}</code></dd>
            </dl>
        </div>
        <div class="card-body">
            <h3 class="card-subtitle mt-0">
                {{ $gettext('Shoutcast Clients') }}
            </h3>
            <dl>
                <dt class="mb-1">
                    {{ $gettext('Server:') }}
                </dt>
                <dd>
                    <code>{{ connectionInfo.serverUrl }}</code>
                </dd>
                <dd v-if="connectionInfo.ip">
                    {{ $gettext('You may need to connect directly via your IP address:') }}
                    <code>{{ connectionInfo.ip }}</code>
                </dd>

                <dt class="mb-1">
                    {{ $gettext('Port:') }}
                </dt>
                <dd><code>{{ connectionInfo.streamPort }}</code></dd>
                <dd>
                    {{ $gettext('For some clients, use port:') }}
                    <code>{{ connectionInfo.streamPort + 1 }}</code>
                </dd>

                <dt class="mb-1">
                    {{ $gettext('Password:') }}
                </dt>
                <dd>
                    <code>dj_username:dj_password</code>
                    {{ $gettext('or') }}
                    <code>dj_username,dj_password</code>
                </dd>
            </dl>
        </div>
        <div class="card-body">
            <p class="card-text">
                {{ $gettext('Setup instructions for broadcasting software are available on the AzuraCast wiki.') }}
                <br>
                <a
                    href="https://docs.azuracast.com/en/user-guide/streaming-software"
                    target="_blank"
                >
                    {{ $gettext('AzuraCast Wiki') }}
                </a>
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
const props = defineProps({
    connectionInfo: {
        type: Object,
        required: true
    }
});
</script>

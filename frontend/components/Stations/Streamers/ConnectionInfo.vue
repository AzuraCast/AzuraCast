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
                    <code>{{ connectionServerUrl }}</code>
                </dd>
                <dd v-if="connectionIp">
                    {{ $gettext('You may need to connect directly via your IP address:') }}
                    <code>{{ connectionIp }}</code>
                </dd>

                <dt class="mb-1">
                    {{ $gettext('Port:') }}
                </dt>
                <dd><code>{{ connectionStreamPort }}</code></dd>

                <dt class="mb-1">
                    {{ $gettext('Mount Name:') }}
                </dt>
                <dd><code>{{ connectionDjMountPoint }}</code></dd>
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
                    <code>{{ connectionServerUrl }}</code>
                </dd>
                <dd v-if="connectionIp">
                    {{ $gettext('You may need to connect directly via your IP address:') }}
                    <code>{{ connectionIp }}</code>
                </dd>

                <template v-if="connectionStreamPort !== null">
                    <dt class="mb-1">
                        {{ $gettext('Port:') }}
                    </dt>
                    <dd><code>{{ connectionStreamPort }}</code></dd>
                    <dd>
                        {{ $gettext('For some clients, use port:') }}
                        <code>{{ connectionStreamPort + 1 }}</code>
                    </dd>
                </template>

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
                    href="/docs/user-guide/streaming-software/"
                    target="_blank"
                >
                    {{ $gettext('AzuraCast Wiki') }}
                </a>
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import {ApiStationsVueStreamersProps} from "~/entities/ApiInterfaces.ts";

defineProps<ApiStationsVueStreamersProps>();
</script>

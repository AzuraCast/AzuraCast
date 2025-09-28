<template>
    <footer id="footer">
        {{ $gettext('Powered by') }}
        <a
            href="https://www.azuracast.com/"
            target="_blank"
        >AzuraCast</a>
        &bull;
        <span v-html="version"/>
        &bull;
        <span v-html="platform"/><br>
        {{ $gettext('Like our software?') }}
        <a
            href="https://donate.azuracast.com/"
            target="_blank"
        >
            {{ $gettext('Donate to support AzuraCast!') }}
        </a>
    </footer>
</template>

<script setup lang="ts">
import {useAzuraCastDashboardGlobals} from "~/vendor/azuracast.ts";

const {version, platform} = useAzuraCastDashboardGlobals();
</script>

<template>
    <div class="list-group list-group-flush">
        <a
            v-for="log in logs"
            :key="log.key"
            class="list-group-item list-group-item-action log-item"
            href="#"
            @click.prevent="viewLog(log.links.self, log.tail)"
        >
            <span class="log-name">{{ log.name }}</span><br>
            <small class="text-secondary">{{ log.path }}</small>
        </a>
    </div>
</template>

<script setup lang="ts">
import {useAsyncState} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    url: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['view']);

const {axios} = useAxios();

const {state: logs} = useAsyncState(
    () => axios.get(props.url).then((r) => r.data.logs),
    []
);

const viewLog = (url, isStreaming) => {
    emit('view', url, isStreaming);
};
</script>

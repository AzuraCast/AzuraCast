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
import {useAxios} from "~/vendor/axios";
import {ApiLogType} from "~/entities/ApiInterfaces.ts";
import {useQuery} from "@tanstack/vue-query";
import {toRef} from "vue";

const props = defineProps<{
    queryKey: unknown[],
    url: string
}>();

const emit = defineEmits<{
    (e: 'view', url: string, isStreaming: boolean): void
}>();

const {axios} = useAxios();

const {data: logs} = useQuery<ApiLogType[]>({
    queryKey: toRef(props, 'queryKey'),
    queryFn: async () => {
        const {data} = await axios.get<ApiLogType[]>(props.url);
        return data;
    }
});

const viewLog = (url: string, isStreaming: boolean) => {
    emit('view', url, isStreaming);
};
</script>

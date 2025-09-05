<template>
    <div
        class="row g-2"
        v-bind="$attrs"
    >
        <div class="col-6">
            <input
                :id="id+'_date'"
                v-model="publishDate"
                class="form-control"
                type="date"
            >
        </div>
        <div class="col-6">
            <input
                :id="id+'_time'"
                v-model="publishTime"
                class="form-control"
                type="time"
            >
        </div>
    </div>
</template>

<script setup lang="ts">
import {ref, watch} from "vue";
import {useLuxon} from "~/vendor/luxon.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";

defineProps<{
    id: string
}>();

const model = defineModel<string | number | null>();

const publishDate = ref<string>('');
const publishTime = ref<string>('');

const {DateTime} = useLuxon();

const stationData = useStationData();
const {timezone} = toRefs(stationData);

watch(model, (publishAt) => {
    if (publishAt !== null) {
        const publishDateTime = DateTime.fromSeconds(Number(publishAt), {zone: timezone.value});
        if (publishDateTime.isValid) {
            publishDate.value = publishDateTime.toISODate();
            publishTime.value = publishDateTime.toISOTime({
                suppressMilliseconds: true,
                includeOffset: false
            });
        }
    } else {
        publishDate.value = '';
        publishTime.value = '';
    }
}, {
    immediate: true
});

const updatePublishAt = () => {
    if (publishDate.value.length > 0 && publishTime.value.length > 0) {
        const publishDateTimeString = publishDate.value + 'T' + publishTime.value;
        const publishDateTime = DateTime.fromISO(publishDateTimeString, {zone: timezone.value});

        model.value = publishDateTime.toSeconds();
    } else {
        model.value = null;
    }
}

watch(publishDate, () => {
    updatePublishAt();
});

watch(publishTime, () => {
    updatePublishAt();
});

watch(timezone, () => {
    updatePublishAt();
});
</script>

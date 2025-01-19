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
import {ref, toRef, watch} from "vue";
import {useLuxon} from "~/vendor/luxon.ts";
import {useAzuraCastStation} from "~/vendor/azuracast.ts";

const props = withDefaults(
    defineProps<{
        id: string,
        modelValue?: string | number | null
    }>(),
    {
        modelValue: null
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null)
}>();

const publishDate = ref<string>('');
const publishTime = ref<string>('');

const {DateTime} = useLuxon();
const {timezone} = useAzuraCastStation();

watch(toRef(props, 'modelValue'), (publishAt) => {
    if (publishAt !== null) {
        const publishDateTime = DateTime.fromSeconds(Number(publishAt), {zone: timezone});
        publishDate.value = publishDateTime.toISODate();
        publishTime.value = publishDateTime.toISOTime({
            suppressMilliseconds: true,
            includeOffset: false
        });
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
        const publishDateTime = DateTime.fromISO(publishDateTimeString, {zone: timezone});

        emit('update:modelValue', publishDateTime.toSeconds());
    } else {
        emit('update:modelValue', null);
    }
}

watch(publishDate, () => {
    updatePublishAt();
});

watch(publishTime, () => {
    updatePublishAt();
});
</script>

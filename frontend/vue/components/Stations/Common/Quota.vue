<template>
    <div
        v-if="!loading"
        style="line-height: 1;"
    >
        <template v-if="quota.available">
            <div
                class="progress h-20 mb-3"
                role="progressbar"
                :aria-label="quota.used_percent+'%'"
                :aria-valuenow="quota.used_percent"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="progress-bar"
                    :class="progressVariant"
                    :style="{ width: quota.used_percent+'%' }"
                >
                    {{ quota.used_percent }} %
                </div>
            </div>

            {{ langSpaceUsed }}
        </template>
        <template v-else>
            {{ langSpaceUsed }}
        </template>
    </div>
</template>

<script setup>
import mergeExisting from "~/functions/mergeExisting";
import {computed, onMounted, ref, shallowRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    quotaUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['updated']);

const loading = ref(true);
const quota = shallowRef({
    used: null,
    used_bytes: null,
    used_percent: null,
    available: null,
    available_bytes: null,
    quota: null,
    quota_bytes: null,
    is_full: null,
    num_files: null
});

const progressVariant = computed(() => {
    if (quota.value.used_percent > 85) {
        return 'text-bg-danger';
    } else if (quota.value.used_percent > 65) {
        return 'text-bg-warning';
    } else {
        return 'text-bg-default';
    }
});

const {$gettext, $ngettext} = useTranslate();

const langSpaceUsed = computed(() => {
    let langSpaceUsed;

    if (quota.value.available) {
        langSpaceUsed = $gettext(
            '%{spaceUsed} of %{spaceTotal} Used',
            {
                spaceUsed: quota.value.used,
                spaceTotal: quota.value.available
            }
        );
    } else {
        langSpaceUsed = $gettext(
            '%{spaceUsed} Used',
            {
                spaceUsed: quota.value.used,
            }
        )
    }

    if (null !== quota.value.num_files) {
        const langNumFiles = $ngettext(
            '%{filesCount} File',
            '%{filesCount} Files',
            quota.value.num_files,
            {filesCount: quota.value.num_files}
        );

        return langSpaceUsed + ' (' + langNumFiles + ')';
    }

    return langSpaceUsed;
});

const {axios} = useAxios();

const update = () => {
    axios.get(props.quotaUrl).then((resp) => {
        quota.value = mergeExisting(quota.value, resp.data);
        loading.value = false;

        emit('updated', quota.value);
    });
}

onMounted(update);

defineExpose({
    update
});
</script>

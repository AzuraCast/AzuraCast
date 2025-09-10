<template>
    <div
        v-if="!loading"
        style="line-height: 1;"
    >
        <template v-if="quota.available">
            <div
                class="progress h-20 mb-2"
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

<script setup lang="ts">
import {computed, onMounted, ref, shallowRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import {ApiStationQuota} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    quotaUrl: string,
}>();

type Quota = Required<ApiStationQuota>;

const emit = defineEmits<{
    (e: 'updated', quota: Quota): void
}>();

const loading = ref(true);
const quota = shallowRef<Quota>({
    used: '',
    used_bytes: '',
    used_percent: 0,
    available: '',
    available_bytes: '',
    quota: null,
    quota_bytes: null,
    is_full: false,
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
    let langSpaceUsed: string;

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
            {filesCount: String(quota.value.num_files)}
        );

        return langSpaceUsed + ' (' + langNumFiles + ')';
    }

    return langSpaceUsed;
});

const {axios} = useAxios();

const update = async () => {
    const {data} = await axios.get<Quota>(props.quotaUrl);

    quota.value = data;
    loading.value = false;

    emit('updated', quota.value);
}

onMounted(update);

defineExpose({
    update
});
</script>

<template>
    <div
        v-if="!loading"
        style="line-height: 1;"
    >
        <template v-if="quota.available">
            <b-progress
                :value="quota.used_percent"
                :variant="progressVariant"
                show-progress
                height="15px"
                class="mb-1"
            />

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
        return 'danger';
    } else if (quota.value.used_percent > 65) {
        return 'warning';
    } else {
        return 'default';
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
</script>

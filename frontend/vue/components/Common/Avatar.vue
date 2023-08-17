<template>
    <a
        v-if="'' !== serviceUrl"
        :href="serviceUrl"
        class="avatar"
        target="_blank"
        :title="langAvatar"
        :aria-label="$gettext('Manage Avatar')"
    >
        <img
            :src="url"
            :style="{ width: width+'px', height: 'auto' }"
            alt=""
        >
    </a>
</template>

<script setup>
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";

const props = defineProps({
    url: {
        type: String,
        required: true
    },
    service: {
        type: String,
        default: null
    },
    serviceUrl: {
        type: String,
        default: null
    },
    width: {
        type: Number,
        default: 64
    }
});

const {$gettext} = useTranslate();

const langAvatar = computed(() => {
    return $gettext(
        'Avatars are retrieved based on your e-mail address from the %{ service } service. Click to manage your %{ service } settings.',
        {
            service: props.service ?? ''
        }
    );
});
</script>

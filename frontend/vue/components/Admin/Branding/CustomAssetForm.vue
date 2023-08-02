<template>
    <div class="d-flex">
        <div class="flex-shrink-0">
            <a
                v-lightbox
                :href="url"
                target="_blank"
            >
                <img
                    :src="url"
                    width="125"
                    :alt="caption"
                >
            </a>
        </div>
        <div class="flex-grow-1 ms-3">
            <loading :loading="isLoading">
                <form-group :id="id">
                    <template #label>
                        {{ caption }}
                    </template>

                    <form-file
                        :id="id"
                        @uploaded="uploaded"
                    />
                </form-group>

                <button
                    v-if="isUploaded"
                    type="button"
                    class="btn btn-danger mt-3"
                    @click="clear()"
                >
                    {{ $gettext('Clear Image') }}
                </button>
            </loading>
        </div>
    </div>
</template>

<script setup>
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import Loading from "~/components/Common/Loading.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormFile from "~/components/Form/FormFile.vue";
import {useLightbox} from "~/vendor/lightbox";

const props = defineProps({
    id: {
        type: String,
        required: true
    },
    apiUrl: {
        type: String,
        required: true
    },
    caption: {
        type: String,
        required: true
    }
});

const isLoading = ref(true);
const isUploaded = ref(false);
const url = ref(null);

const {axios} = useAxios();

const relist = () => {
    isLoading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        isUploaded.value = resp.data.is_uploaded;
        url.value = resp.data.url;

        isLoading.value = false;
    });
};

onMounted(relist);

const {wrapWithLoading} = useNotify();

const uploaded = (newFile) => {
    if (null === newFile) {
        return;
    }

    const formData = new FormData();
    formData.append('file', newFile);

    wrapWithLoading(
        axios.post(props.apiUrl, formData)
    ).finally(() => {
        relist();
    });
};

const clear = () => {
    wrapWithLoading(
        axios.delete(props.apiUrl)
    ).finally(() => {
        relist();
    });
};

const {vLightbox} = useLightbox();
</script>

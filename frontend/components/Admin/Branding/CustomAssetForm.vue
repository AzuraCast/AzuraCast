<template>
    <div class="d-flex">
        <div class="flex-shrink-0">
            <a
                v-lightbox
                v-if="url"
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

<script setup lang="ts">
import {onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormFile from "~/components/Form/FormFile.vue";
import {useLightbox} from "~/vendor/lightbox";
import {ApiUploadedRecordStatus} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    id: string,
    apiUrl: string,
    caption: string,
}>();

const isLoading = ref<boolean>(true);
const isUploaded = ref<boolean>(false);
const url = ref<string | null>(null);

const {axios} = useAxios();

const relist = () => {
    isLoading.value = true;

    void axios.get<ApiUploadedRecordStatus>(props.apiUrl).then(({data}) => {
        isUploaded.value = data.hasRecord;
        url.value = data.url;

        isLoading.value = false;
    });
};

onMounted(relist);

const uploaded = (newFile: File | null) => {
    if (null === newFile) {
        return;
    }

    const formData = new FormData();
    formData.append('file', newFile);

    void axios.post(props.apiUrl, formData).finally(() => {
        relist();
    });
};

const clear = () => {
    void axios.delete(props.apiUrl).finally(() => {
        relist();
    });
};

const {vLightbox} = useLightbox();
</script>

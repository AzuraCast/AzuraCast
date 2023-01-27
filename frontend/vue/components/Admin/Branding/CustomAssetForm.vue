<template>
    <b-media tag="li">
        <template #aside>
            <a
                :href="url"
                data-fancybox
                target="_blank"
            >
                <b-img
                    :src="url"
                    width="125"
                    :alt="caption"
                />
            </a>
        </template>
        <b-overlay
            variant="card"
            :show="loading"
        >
            <b-form-group :label-for="id">
                <template #label>
                    {{ caption }}
                </template>
                <b-form-file
                    :id="id"
                    v-model="file"
                    accept="image/*"
                />
            </b-form-group>
            <b-button
                v-if="isUploaded"
                variant="outline-danger"
                @click.prevent="clear()"
            >
                {{ $gettext('Clear Image') }}
            </b-button>
        </b-overlay>
    </b-media>
</template>

<script setup>
import {onMounted, ref, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/vendor/bootstrapVue";

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

const loading = ref(true);
const isUploaded = ref(false);
const url = ref(null);
const file = ref(null);

const {axios} = useAxios();

const relist = () => {
    file.value = null;
    loading.value = true;

    axios.get(props.apiUrl).then((resp) => {
        isUploaded.value = resp.data.is_uploaded;
        url.value = resp.data.url;

        loading.value = false;
    });
};

onMounted(relist);

const {wrapWithLoading} = useNotify();

watch(file, (newFile) => {
    if (null === newFile) {
        return;
    }

    let formData = new FormData();
    formData.append('file', newFile);

    wrapWithLoading(
        axios.post(props.apiUrl, formData)
    ).finally(() => {
        relist();
    });
});

const clear = () => {
    wrapWithLoading(
        axios.delete(props.apiUrl)
    ).finally(() => {
        relist();
    });
};
</script>

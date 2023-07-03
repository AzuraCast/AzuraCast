<template>
    <o-tab-item :label="$gettext('Artwork')">
        <div class="row g-3">
            <div class="col-md-8">
                <form-group :id="edit_form_art">
                    <template #label>
                        {{ $gettext('Select PNG/JPG artwork file') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Artwork must be a minimum size of 1400 x 1400 pixels and a maximum size of 3000 x 3000 pixels for Apple Podcasts.')
                        }}
                    </template>

                    <!-- TODO -->
                    <b-form-file
                        id="edit_form_art"
                        v-model="uploadedFile"
                        accept="image/jpeg, image/png"
                    />
                </form-group>
            </div>
            <div
                v-if="src && src !== ''"
                class="col-md-4"
            >
                <!-- TODO -->
                <b-img
                    :src="src"
                    :alt="$gettext('Artwork')"
                    rounded
                    fluid
                />

                <div class="block-buttons pt-3">
                    <button
                        class="btn btn-block btn-danger"
                        @click="deleteArt"
                    >
                        {{ $gettext('Clear Artwork') }}
                    </button>
                </div>
            </div>
        </div>
    </o-tab-item>
</template>

<script setup>
import {computed, ref, toRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import FormGroup from "~/components/Form/FormGroup.vue";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    artworkSrc: {
        type: String,
        required: true
    },
    editArtUrl: {
        type: String,
        required: true
    },
    newArtUrl: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['update:modelValue']);

const artworkSrc = toRef(props, 'artworkSrc');
const localSrc = ref(null);

const src = computed(() => {
    return localSrc.value ?? artworkSrc.value;
});

const {axios} = useAxios();

const uploadedFile = ref(null);

watch(uploadedFile, (file) => {
    if (null === file) {
        return;
    }

    let fileReader = new FileReader();
    fileReader.addEventListener('load', () => {
        localSrc.value = fileReader.result;
    }, false);
    fileReader.readAsDataURL(file);

    let url = (props.editArtUrl) ? props.editArtUrl : props.newArtUrl;
    let formData = new FormData();
    formData.append('art', file);

    axios.post(url, formData).then((resp) => {
        emit('update:modelValue', resp.data);
    });
});

const deleteArt = () => {
    if (props.editArtUrl) {
        axios.delete(props.editArtUrl).then(() => {
            localSrc.value = null;
        });
    } else {
        localSrc.value = null;
    }
}
</script>

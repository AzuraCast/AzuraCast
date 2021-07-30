<template>
    <b-media tag="li">
        <template #aside>
            <a :href="url" data-fancybox target="_blank">
                <b-img :src="url" width="125" :alt="caption"></b-img>
            </a>
        </template>
        <b-overlay variant="card" :show="loading">
            <b-form-group :label-for="id">
                <template #label>{{ caption }}</template>
                <b-form-file :id="id" v-model="file" accept="image/*" @input="upload"></b-form-file>
            </b-form-group>
            <b-button v-if="isUploaded" variant="outline-danger" @click.prevent="clear()">
                <translate key="lang_btn_reset">Clear Image</translate>
            </b-button>
        </b-overlay>
    </b-media>
</template>

<script>
import axios from 'axios';
import handleAxiosError from "../../Function/handleAxiosError";

export default {
    name: 'CustomAssetForm',
    props: {
        id: String,
        apiUrl: String,
        caption: String
    },
    data() {
        return {
            loading: true,
            isUploaded: false,
            url: null,
            file: null,
        };
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.file = null;
            this.loading = true;

            axios.get(this.apiUrl).then((resp) => {
                this.isUploaded = resp.data.is_uploaded;
                this.url = resp.data.url;

                this.loading = false;
            }).catch((error) => {
                handleAxiosError(error);
            });

        },
        clear() {
            axios.delete(this.apiUrl).then((resp) => {
                this.relist();
            }).catch((error) => {
                handleAxiosError(error);
                this.relist();
            });
        },
        upload() {
            if (null === this.file) {
                return;
            }

            let formData = new FormData();
            formData.append('file', this.file);

            axios.post(this.apiUrl, formData).then((resp) => {
                this.relist();
            }).catch((error) => {
                handleAxiosError(error);
                this.relist();
            });
        },
    }
}
</script>

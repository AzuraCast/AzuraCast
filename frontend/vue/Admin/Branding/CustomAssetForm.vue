<template>
    <b-overlay variant="card" :show="loading">
        <div class="row">
            <div class="col-6">
                <b-form-group label-for="edit_form_art">
                    <b-form-file id="edit_form_art" v-model="file" accept="image/*" @input="upload"></b-form-file>
                </b-form-group>
                <b-button v-if="isUploaded" block variant="danger" @click.prevent="delete()">
                    Reset to Default
                </b-button>
            </div>
            <div class="col-6">
                <b-img :src="url" fluid :alt="caption"></b-img>
            </div>
        </div>
    </b-overlay>
</template>

<script>
import axios from 'axios';
import handleAxiosError from "../../Function/handleAxiosError";

export default {
    name: 'CustomAssetForm',
    props: {
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
        delete() {
            axios.delete(this.apiUrl).then((resp) => {
                this.relist();
            }).catch((error) => {
                handleAxiosError(error);
                this.relist();
            });
        },
        upload() {
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

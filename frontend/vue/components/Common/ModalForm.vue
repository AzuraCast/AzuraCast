<template>
    <b-modal :size="size" :id="id" ref="modal" :title="title" :busy="loading">
        <template #default="slotProps">
            <b-overlay variant="card" :show="loading">
                <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

                <b-form class="form" @submit.prevent="doSubmit">
                    <slot name="default" v-bind="slotProps">
                    </slot>

                    <invisible-submit-button/>
                </b-form>
            </b-overlay>
        </template>

        <template #modal-footer="slotProps">
            <slot name="modal-footer" v-bind="slotProps">
                <b-button variant="default" type="button" @click="close">
                    <translate key="lang_btn_close">Close</translate>
                </b-button>
                <b-button variant="primary" type="submit" @click="doSubmit" :disabled="disableSaveButton">
                    <translate key="lang_btn_save_changes">Save Changes</translate>
                </b-button>
            </slot>
        </template>

        <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
        <template v-for="(_, name) in filteredScopedSlots" :slot="name" slot-scope="slotData">
            <slot :name="name" v-bind="slotData"/>
        </template>
    </b-modal>
</template>

<script>
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";

export default {
    components: {InvisibleSubmitButton},
    emits: ['submit'],
    props: {
        title: {
            type: String,
            required: true
        },
        size: {
            type: String,
            default: 'lg'
        },
        id: {
            type: String,
            default: 'edit-modal'
        },
        loading: {
            type: Boolean,
            required: true
        },
        disableSaveButton: {
            type: Boolean,
            default: false
        },
        error: {
            type: String
        }
    },
    computed: {
        filteredScopedSlots() {
            return _.filter(this.$scopedSlots, (slot, name) => {
                return !_.includes([
                    'default', 'modal-footer'
                ], name);
            });
        },
    },
    methods: {
        doSubmit() {
            this.$emit('submit');
        },
        close() {
            this.hide();
        },
        hide() {
            this.$refs.modal.hide();
        },
        show() {
            this.$refs.modal.show();
        }
    }
}
</script>

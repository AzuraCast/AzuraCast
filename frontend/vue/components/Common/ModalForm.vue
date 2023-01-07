<template>
    <b-modal
        :id="id"
        ref="modal"
        :size="size"
        :centered="centered"
        :title="title"
        :busy="loading"
        :no-enforce-focus="noEnforceFocus"
        @shown="onShown"
        @hidden="onHidden"
    >
        <template #default="slotProps">
            <b-overlay
                variant="card"
                :show="loading"
            >
                <b-alert
                    variant="danger"
                    :show="error != null"
                >
                    {{ error }}
                </b-alert>

                <b-form
                    class="form vue-form"
                    @submit.prevent="doSubmit"
                >
                    <slot
                        name="default"
                        v-bind="slotProps"
                    />

                    <invisible-submit-button />
                </b-form>
            </b-overlay>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <b-button
                    variant="default"
                    type="button"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </b-button>
                <b-button
                    :variant="(disableSaveButton) ? 'danger' : 'primary'"
                    type="submit"
                    @click="doSubmit"
                >
                    <slot name="save-button-name">
                        {{ $gettext('Save Changes') }}
                    </slot>
                </b-button>
            </slot>
        </template>

        <template
            v-for="(_, slot) of filteredScopedSlots"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </b-modal>
</template>

<script>
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {defineComponent} from "vue";
import {filter, includes} from "lodash";

/* TODO Options API */

export default defineComponent({
    components: {InvisibleSubmitButton},
    props: {
        title: {
            type: String,
            required: true
        },
        size: {
            type: String,
            default: 'lg'
        },
        centered: {
            type: Boolean,
            default: false
        },
        id: {
            type: String,
            default: 'edit-modal'
        },
        loading: {
            type: Boolean,
            default: false
        },
        disableSaveButton: {
            type: Boolean,
            default: false
        },
        noEnforceFocus: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String,
            default: null
        }
    },
    emits: ['submit', 'shown', 'hidden'],
    computed: {
        filteredScopedSlots() {
            return filter(this.$slots, (slot, name) => {
                return !includes([
                    'default', 'modal-footer'
                ], name);
            });
        },
    },
    methods: {
        doSubmit() {
            this.$emit('submit');
        },
        onShown() {
            this.$emit('shown');
        },
        onHidden() {
            this.$emit('hidden');
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
});
</script>

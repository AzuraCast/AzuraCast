<template>
    <b-form-group v-bind="$attrs" :label-for="id" :state="fieldState">
        <template #default>
            <slot name="default" v-bind="{ id, field, state: fieldState }">
                <b-form-textarea v-if="inputType === 'textarea'" ref="input" :id="id" :name="name"
                                 v-model="modelValue"
                                 :required="isRequired" :number="isNumeric" :trim="inputTrim" v-bind="inputAttrs"
                                 :autofocus="autofocus" :state="fieldState"></b-form-textarea>
                <b-form-input v-else ref="input" :type="inputType" :id="id" :name="name" v-model="modelValue"
                              :required="isRequired" :number="isNumeric" :trim="inputTrim"
                              :autofocus="autofocus" v-bind="inputAttrs" :state="fieldState"></b-form-input>
            </slot>

            <b-form-invalid-feedback :state="fieldState">
                <vuelidate-error :field="field"></vuelidate-error>
            </b-form-invalid-feedback>
        </template>

        <template #label="slotProps">
            <slot name="label" v-bind="slotProps" :lang="'lang_'+id"></slot>
            <span v-if="isRequired" class="text-danger">
                <span aria-hidden="true">*</span>
                <span class="sr-only">Required</span>
            </span>
            <span v-if="advanced" class="badge small badge-primary">
                <translate key="badge_advanced">Advanced</translate>
            </span>
        </template>
        <template #description="slotProps">
            <slot name="description" v-bind="slotProps" :lang="'lang_'+id+'_desc'"></slot>
        </template>

        <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
        <template v-for="(_, name) in filteredScopedSlots" :slot="name" slot-scope="slotData">
            <slot :name="name" v-bind="slotData"/>
        </template>
    </b-form-group>
</template>

<script>
import _ from "lodash";
import VuelidateError from "./VuelidateError";

export default {
    name: 'BWrappedFormGroup',
    components: {VuelidateError},
    props: {
        id: {
            type: String,
            required: true
        },
        name: {
            type: String,
        },
        field: {
            type: Object,
            required: true
        },
        inputType: {
            type: String,
            default: 'text'
        },
        inputNumber: {
            type: Boolean,
            default: false
        },
        inputTrim: {
            type: Boolean,
            default: false
        },
        inputEmptyIsNull: {
            type: Boolean,
            default: false
        },
        inputAttrs: {
            type: Object,
            default() {
                return {};
            }
        },
        autofocus: {
            type: Boolean,
            default: false
        },
        advanced: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        modelValue: {
            get() {
                return this.field.$model;
            },
            set(value) {
                if ((this.isNumeric || this.inputEmptyIsNull) && '' === value) {
                    value = null;
                }

                this.field.$model = value;
            }
        },
        filteredScopedSlots() {
            return _.filter(this.$scopedSlots, (slot, name) => {
                return !_.includes([
                    'default', 'label', 'description'
                ], name);
            });
        },
        fieldState() {
            return this.field.$dirty ? !this.field.$error : null;
        },
        isRequired() {
            return _.has(this.field, 'required');
        },
        isNumeric() {
            return this.inputNumber || this.inputType === "number";
        }
    },
    methods: {
        focus() {
            if (typeof this.$refs.input !== "undefined") {
                this.$refs.input.focus();
            }
        }
    }
}
</script>

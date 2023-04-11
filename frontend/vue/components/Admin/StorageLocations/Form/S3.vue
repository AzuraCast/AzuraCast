<template>
    <section class="card mt-3">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote: S3 Compatible') }}
            </h2>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        id="form_edit_s3CredentialKey"
                        class="col-md-6"
                        :field="form.s3CredentialKey"
                    >
                        <template #label>
                            {{ $gettext('Access Key ID') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3CredentialSecret"
                        class="col-md-6"
                        :field="form.s3CredentialSecret"
                    >
                        <template #label>
                            {{ $gettext('Secret Key') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Endpoint"
                        class="col-md-6"
                        :field="form.s3Endpoint"
                    >
                        <template #label>
                            {{ $gettext('Endpoint') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Bucket"
                        class="col-md-6"
                        :field="form.s3Bucket"
                    >
                        <template #label>
                            {{ $gettext('Bucket Name') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Region"
                        class="col-md-6"
                        :field="form.s3Region"
                    >
                        <template #label>
                            {{ $gettext('Region') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_s3Version"
                        class="col-md-6"
                        :field="form.s3Version"
                    >
                        <template #label>
                            {{ $gettext('API Version') }}
                        </template>
                    </b-wrapped-form-group>
                </div>
            </b-form-group>
        </b-card-body>
    </section>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

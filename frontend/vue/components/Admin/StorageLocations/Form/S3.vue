<template>
    <section class="card mt-3">
        <div class="card-header text-bg-primary">
            <h2 class="card-title">
                {{ $gettext('Remote: S3 Compatible') }}
            </h2>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <form-group-field
                    id="form_edit_s3CredentialKey"
                    class="col-md-6"
                    :field="form.s3CredentialKey"
                    :label="$gettext('Access Key ID')"
                />

                <form-group-field
                    id="form_edit_s3CredentialSecret"
                    class="col-md-6"
                    :field="form.s3CredentialSecret"
                    :label="$gettext('Secret Key')"
                />

                <form-group-field
                    id="form_edit_s3Endpoint"
                    class="col-md-6"
                    :field="form.s3Endpoint"
                    :label="$gettext('Endpoint')"
                />

                <form-group-field
                    id="form_edit_s3Bucket"
                    class="col-md-6"
                    :field="form.s3Bucket"
                    :label="$gettext('Bucket Name')"
                />

                <form-group-field
                    id="form_edit_s3Region"
                    class="col-md-6"
                    :field="form.s3Region"
                    :label="$gettext('Region')"
                />

                <form-group-field
                    id="form_edit_s3Version"
                    class="col-md-6"
                    :field="form.s3Version"
                    :label="$gettext('API Version')"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

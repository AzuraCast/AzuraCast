<template>
    <section class="card mt-3">
        <div class="card-header text-bg-primary">
            <h2 class="card-title">
                {{ $gettext('Remote: SFTP') }}
            </h2>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <form-group-field
                    id="form_edit_sftpHost"
                    class="col-md-12 col-lg-6"
                    :field="form.sftpHost"
                    :label="$gettext('SFTP Host')"
                />

                <form-group-field
                    id="form_edit_sftpPort"
                    class="col-md-12 col-lg-6"
                    input-type="number"
                    min="1"
                    step="1"
                    :field="form.sftpPort"
                    :label="$gettext('SFTP Port')"
                />

                <form-group-field
                    id="form_edit_sftpUsername"
                    class="col-md-12 col-lg-6"
                    :field="form.sftpUsername"
                    :label="$gettext('SFTP Username')"
                />

                <form-group-field
                    id="form_edit_sftpPassword"
                    class="col-md-12 col-lg-6"
                    :field="form.sftpPassword"
                    :label="$gettext('SFTP Password')"
                />

                <form-group-field
                    id="form_edit_sftpPrivateKeyPassPhrase"
                    class="col-md-12"
                    :field="form.sftpPrivateKeyPassPhrase"
                    :label="$gettext('SFTP Private Key Pass Phrase')"
                />

                <form-group-field
                    id="form_edit_sftpPrivateKey"
                    class="col-md-12"
                    input-type="textarea"
                    :field="form.sftpPrivateKey"
                    :label="$gettext('SFTP Private Key')"
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

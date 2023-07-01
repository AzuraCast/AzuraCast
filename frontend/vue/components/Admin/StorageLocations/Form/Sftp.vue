<template>
    <section class="card mt-3">
        <div class="card-header text-bg-primary">
            <h2 class="card-title">
                {{ $gettext('Remote: SFTP') }}
            </h2>
        </div>
        <div class="card-body">
            <b-form-group>
                <div class="row g-3">
                    <form-group-field
                        id="form_edit_sftpHost"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpHost"
                    >
                        <template #label>
                            {{ $gettext('SFTP Host') }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="form_edit_sftpPort"
                        class="col-md-12 col-lg-6"
                        input-type="number"
                        min="1"
                        step="1"
                        :field="form.sftpPort"
                    >
                        <template #label>
                            {{ $gettext('SFTP Port') }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="form_edit_sftpUsername"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpUsername"
                    >
                        <template #label>
                            {{ $gettext('SFTP Username') }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="form_edit_sftpPassword"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpPassword"
                    >
                        <template #label>
                            {{ $gettext('SFTP Password') }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="form_edit_sftpPrivateKeyPassPhrase"
                        class="col-md-12"
                        :field="form.sftpPrivateKeyPassPhrase"
                    >
                        <template #label>
                            {{ $gettext('SFTP Private Key Pass Phrase') }}
                        </template>
                    </form-group-field>

                    <form-group-field
                        id="form_edit_sftpPrivateKey"
                        class="col-md-12"
                        input-type="textarea"
                        :field="form.sftpPrivateKey"
                    >
                        <template #label>
                            {{ $gettext('SFTP Private Key') }}
                        </template>
                    </form-group-field>
                </div>
            </b-form-group>
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

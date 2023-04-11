<template>
    <section class="card mt-3">
        <div class="card-header bg-primary-dark">
            <h2 class="card-title">
                {{ $gettext('Remote: SFTP') }}
            </h2>
        </div>
        <b-card-body>
            <b-form-group>
                <div class="form-row">
                    <b-wrapped-form-group
                        id="form_edit_sftpHost"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpHost"
                    >
                        <template #label>
                            {{ $gettext('SFTP Host') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
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
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpUsername"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpUsername"
                    >
                        <template #label>
                            {{ $gettext('SFTP Username') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPassword"
                        class="col-md-12 col-lg-6"
                        :field="form.sftpPassword"
                    >
                        <template #label>
                            {{ $gettext('SFTP Password') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPrivateKeyPassPhrase"
                        class="col-md-12"
                        :field="form.sftpPrivateKeyPassPhrase"
                    >
                        <template #label>
                            {{ $gettext('SFTP Private Key Pass Phrase') }}
                        </template>
                    </b-wrapped-form-group>

                    <b-wrapped-form-group
                        id="form_edit_sftpPrivateKey"
                        class="col-md-12"
                        input-type="textarea"
                        :field="form.sftpPrivateKey"
                    >
                        <template #label>
                            {{ $gettext('SFTP Private Key') }}
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

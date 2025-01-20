<template>
    <tab
        :label="$gettext('Remote: SFTP')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_sftpHost"
                class="col-md-12 col-lg-6"
                :field="v$.sftpHost"
                :label="$gettext('SFTP Host')"
            />

            <form-group-field
                id="form_edit_sftpPort"
                class="col-md-12 col-lg-6"
                input-type="number"
                min="1"
                step="1"
                :field="v$.sftpPort"
                :label="$gettext('SFTP Port')"
            />

            <form-group-field
                id="form_edit_sftpUsername"
                class="col-md-12 col-lg-6"
                :field="v$.sftpUsername"
                :label="$gettext('SFTP Username')"
            />

            <form-group-field
                id="form_edit_sftpPassword"
                class="col-md-12 col-lg-6"
                :field="v$.sftpPassword"
                :label="$gettext('SFTP Password')"
            />

            <form-group-field
                id="form_edit_sftpPrivateKeyPassPhrase"
                class="col-md-12"
                :field="v$.sftpPrivateKeyPassPhrase"
                :label="$gettext('SFTP Private Key Pass Phrase')"
            />

            <form-group-field
                id="form_edit_sftpPrivateKey"
                class="col-md-12"
                input-type="textarea"
                :field="v$.sftpPrivateKey"
                :label="$gettext('SFTP Private Key')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps<FormTabProps>();
const emit = defineEmits<FormTabEmits>();

const {v$, tabClass} = useVuelidateOnFormTab(
    props,
    emit,
    {
        sftpHost: {required},
        sftpPort: {required},
        sftpUsername: {required},
        sftpPassword: {},
        sftpPrivateKey: {},
        sftpPrivateKeyPassPhrase: {}
    },
    {
        sftpHost: null,
        sftpPort: '22',
        sftpUsername: null,
        sftpPassword: null,
        sftpPrivateKey: null,
        sftpPrivateKeyPassPhrase: null,
    }
);
</script>

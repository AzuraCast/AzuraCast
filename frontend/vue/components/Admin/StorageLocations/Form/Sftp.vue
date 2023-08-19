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

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        sftpHost: {required},
        sftpPort: {required},
        sftpUsername: {required},
        sftpPassword: {},
        sftpPrivateKey: {},
        sftpPrivateKeyPassPhrase: {}
    },
    form
);
</script>

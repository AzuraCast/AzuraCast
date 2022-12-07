<template>
    <b-form-fieldset>
        <template #label>
            <translate key="lang_backend_disabled">No AutoDJ Enabled</translate>
        </template>

        <p class="card-text">
            <translate
                key="lang_backend_disabled_desc">This feature requires the AutoDJ feature to be enabled.</translate>
        </p>
    </b-form-fieldset>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset.vue";

export default {
    name: 'StationsFormCommonBackendDisabled',
    components: {BFormFieldset}
}
</script>

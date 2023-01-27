<template>
    <b-form-fieldset>
        <template #label>
            {{ $gettext('No AutoDJ Enabled') }}
        </template>

        <p class="card-text">
            {{ $gettext('This feature requires the AutoDJ feature to be enabled.') }}
        </p>
    </b-form-fieldset>
</template>

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset.vue";</script>

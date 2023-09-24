<template>
    <h3 class="card-subtitle">
        {{ $gettext('New Key Generated') }}
    </h3>

    <p class="card-text">
        <b>{{ $gettext('Important: copy the key below before continuing!') }}</b>
        {{ $gettext('You will not be able to retrieve it again.') }}
    </p>

    <p class="card-text">
        {{ $gettext('Your full API key is below:') }}
    </p>

    <div class="px-2">
        <code id="api_key">{{ newKey }}</code>
        <div class="buttons pt-2">
            <copy-to-clipboard-button :text="newKey" />
        </div>
    </div>

    <p class="card-text pt-3">
        {{
            $gettext('When making API calls, you can pass this value in the "X-API-Key" header to authenticate as yourself.')
        }}
    </p>
    <p class="card-text">
        {{ $gettext('You can only perform the actions your user account is allowed to perform.') }}
    </p>
</template>

<script setup lang="ts">
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton.vue";

defineProps({
    newKey: {
        type: String,
        required: true
    }
});
</script>

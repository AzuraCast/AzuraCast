<template>
    <h3 class="card-subtitle">
        {{ $gettext('New Login Link Created') }}
    </h3>

    <p class="card-text">
        <b>{{ $gettext('Important: copy the address before continuing!') }}</b>
        {{ $gettext('You will not be able to retrieve it again.') }}
    </p>

    <div class="px-2">
        <code id="login_link">{{ loginLink }}</code>
        <div class="buttons pt-2">
            <copy-to-clipboard-button :text="loginLink"/>
        </div>
    </div>

    <p class="card-text pt-3">
        {{
            $gettext('Each login link is only usable once.')
        }}
    </p>
</template>

<script setup lang="ts">
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton.vue";

defineProps<{
    loginLink: string,
}>();
</script>

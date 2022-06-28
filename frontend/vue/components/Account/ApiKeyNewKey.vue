<template>
    <div>
        <h3 class="card-subtitle">
            <translate key="lang_hdr_new_key">New Key Generated</translate>
        </h3>

        <p class="card-text">
            <b>
                <translate key="new_api_key_1">Important: copy the key below before continuing!</translate>
            </b>
            <translate key="new_api_key_2">You will not be able to retrieve it again.</translate>
        </p>

        <p class="card-text">
            <translate key="new_api_key_3">Your full API key is below:</translate>
        </p>

        <div class="px-2">
            <code id="api_key">{{ newKey }}</code>
            <div class="buttons pt-2">
                <copy-to-clipboard-button :text="newKey"></copy-to-clipboard-button>
            </div>
        </div>

        <p class="card-text pt-3">
            <translate key="new_api_key_4">When making API calls, you can pass this value in the "X-API-Key" header to authenticate as yourself.</translate>
        </p>
        <p class="card-text">
            <translate
                key="new_api_key_5">You can only perform the actions your user account is allowed to perform.</translate>
        </p>
    </div>
</template>

<script>
import CopyToClipboardButton from "~/components/Common/CopyToClipboardButton";

export default {
    name: 'AccountApiKeyNewKey',
    components: {CopyToClipboardButton},
    props: {
        newKey: String
    }
}
</script>

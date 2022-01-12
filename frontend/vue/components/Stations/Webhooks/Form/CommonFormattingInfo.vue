<template>
    <b-form-group>
        <template #label>
            <translate key="lang_customize_message_hdr">Message Customization Tips</translate>
        </template>

        <p class="card-text">
            <translate key="lang_customize_message_desc_1">Variables are in the form of: </translate>
            <code v-pre>{{ var.name }}</code>
        </p>

        <p class="card-text">
            <translate key="lang_customize_message_desc_2">All values in the NowPlaying API response are available for use. Any empty fields are ignored.</translate>
            <br>
            <a href="https://azuracast.com/api" target="_blank">
                <translate key="lang_customize_response_link">NowPlaying API Response</translate>
            </a>
        </p>
    </b-form-group>
</template>

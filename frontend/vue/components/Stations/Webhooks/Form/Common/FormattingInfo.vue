<template>
    <b-form-group>
        <template #label>
            {{ $gettext('Message Customization Tips') }}
        </template>

        <p class="card-text">
            {{ $gettext('Variables are in the form of: ') }}
            <code v-pre>{{ var.name }}</code>
        </p>

        <p class="card-text">
            {{ $gettext('All values in the NowPlaying API response are available for use. Any empty fields are ignored.') }}
            <br>
            <a
                :href="nowPlayingUrl"
                target="_blank"
            >
                {{ $gettext('NowPlaying API Response') }}
            </a>
        </p>
    </b-form-group>
</template>

<script setup>
const props = defineProps({
    nowPlayingUrl: String
});
</script>

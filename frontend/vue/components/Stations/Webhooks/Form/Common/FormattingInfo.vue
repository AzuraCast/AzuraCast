<template>
    <form-markup id="customization_tips">
        <template #label>
            {{ $gettext('Message Customization Tips') }}
        </template>

        <p class="card-text">
            {{ $gettext('Variables are in the form of: ') }}
            <code v-pre>{{ var.name }}</code>
        </p>

        <p class="card-text">
            {{
                $gettext('All values in the NowPlaying API response are available for use. Any empty fields are ignored.')
            }}
            <br>
            <a
                :href="nowPlayingUrl"
                target="_blank"
            >
                {{ $gettext('NowPlaying API Response') }}
            </a>
        </p>
    </form-markup>
</template>

<script setup>
import FormMarkup from "~/components/Form/FormMarkup.vue";
import {inject} from "vue";

const nowPlayingUrl = inject('nowPlayingUrl');
</script>

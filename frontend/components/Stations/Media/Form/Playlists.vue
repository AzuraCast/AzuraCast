<template>
    <tab :label="$gettext('Playlists')">
        <div class="row">
            <form-group-multi-check
                id="edit_form_playlists"
                class="col-md-12"
                :field="r$.playlists.$self"
                :options="options"
                stacked
                :label="$gettext('Playlists')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import { map } from "es-toolkit/compat";
import { storeToRefs } from "pinia";
import { computed } from "vue";
import Tab from "~/components/Common/Tab.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import { useStationsMediaForm } from "~/components/Stations/Media/Form/form.ts";
import { MediaInitialPlaylist } from "~/components/Stations/Media.vue";

const props = defineProps<{
    playlists: MediaInitialPlaylist[];
}>();

const { r$ } = storeToRefs(useStationsMediaForm());

const options = computed(() => {
    return map(props.playlists, (row) => ({
        text: row.name,
        value: row.id,
    }));
});
</script>

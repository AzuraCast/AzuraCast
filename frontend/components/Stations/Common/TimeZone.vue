<template>
    {{
        $gettext(
            'This station\'s time zone is currently %{tz}.',
            {tz: timezone}
        )
    }}
</template>

<script setup lang="ts">
import {useStationData} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";

const stationData = useStationData();
const {timezone} = toRefs(stationData);
</script>

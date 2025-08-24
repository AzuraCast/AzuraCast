<template>
    {{
        $gettext(
            'This station\'s time zone is currently %{tz}.',
            {tz: timezone}
        )
    }}
</template>

<script setup lang="ts">
import {useStationQuery} from "~/functions/useStationQuery.ts";
import {toRefs} from "@vueuse/core";

const {data: stationData} = useStationQuery();
const {timezone} = toRefs(stationData);
</script>

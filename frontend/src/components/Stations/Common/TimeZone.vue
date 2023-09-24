<template>
    {{
        $gettext(
            'This station\'s time zone is currently %{tz}.',
            {tz: timezone}
        )
    }}
</template>

<script setup lang="ts">
import {useAzuraCastStation} from "~/vendor/azuracast";

const {timezone} = useAzuraCastStation();
</script>

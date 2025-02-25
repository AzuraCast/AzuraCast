<template>
    <section class="card">
        <div class="card-header text-bg-primary">
            <h2 class="card-title">
                {{ $gettext('Disk Space') }}
            </h2>
        </div>

        <div class="card-body">
            <h6 class="mb-1 text-center">
                {{ $gettext('Total Disk Space') }}
                :
                {{ diskStats.total_readable }}
            </h6>

            <div
                class="progress h-20 mb-3 mt-2"
                role="progressbar"
                :aria-label="diskStats.used_readable"
                aria-valuemin="0"
                :aria-valuemax="diskStats.total_bytes"
            >
                <div
                    class="progress-bar text-bg-primary"
                    :style="{ width: getPercent(diskStats.used_bytes, diskStats.total_bytes) }"
                />
            </div>

            <div class="row">
                <div class="col">
                    <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>

                    {{ $gettext('Used') }}
                    :
                    {{ diskStats.used_readable }}
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import {ApiAdminServerStatsStorageStats} from "~/entities/ApiInterfaces.ts";

defineProps<{
    diskStats: ApiAdminServerStatsStorageStats
}>();

const getPercent = (amount: string | number, total: string | number) => {
    return ((Number(amount) / Number(total)) * 100) + '%';
}
</script>

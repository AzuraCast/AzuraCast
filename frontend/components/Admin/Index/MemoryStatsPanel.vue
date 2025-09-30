<template>
    <section class="card">
        <div class="card-header text-bg-primary d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ $gettext('Memory') }}
                </h2>
            </div>

            <div class="flex-shrink-0">
                <button
                    type="button"
                    class="btn btn-dark btn-sm py-2"
                    @click="showMemoryStatsHelpModal"
                >
                    <icon-ic-info/>
                </button>
            </div>
        </div>

        <div class="card-body">
            <h6 class="mb-1 text-center">
                {{ $gettext('Total RAM') }}
                :
                {{ memoryStats.total_readable }}
            </h6>

            <div
                class="progress h-20 mb-3 mt-2"
                role="progressbar"
                :aria-label="memoryStats.used_readable"
                aria-valuemin="0"
                :aria-valuemax="memoryStats.total_bytes"
            >
                <div
                    class="progress-bar text-bg-primary"
                    :style="{ width: getPercent(memoryStats.used_bytes, memoryStats.total_bytes) }"
                />
                <div
                    class="progress-bar text-bg-warning"
                    :style="{ width: getPercent(memoryStats.cached_bytes, memoryStats.total_bytes) }"
                />
            </div>

            <div class="row">
                <div class="col">
                    <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>
                    {{ $gettext('Used') }}
                    : {{ memoryStats.used_readable }}
                </div>
                <div class="col">
                    <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>&nbsp;

                    {{ $gettext('Cached') }}
                    : {{ memoryStats.cached_readable }}
                </div>
            </div>
        </div>
    </section>

    <memory-stats-help-modal ref="$memoryStatsHelpModal" />
</template>

<script setup lang="ts">
import {useTemplateRef} from "vue";
import MemoryStatsHelpModal from "~/components/Admin/Index/MemoryStatsHelpModal.vue";
import {ApiAdminServerStatsMemoryStats} from "~/entities/ApiInterfaces.ts";
import IconIcInfo from "~icons/ic/baseline-info";

defineProps<{
    memoryStats: ApiAdminServerStatsMemoryStats
}>();

const $memoryStatsHelpModal = useTemplateRef('$memoryStatsHelpModal');

const showMemoryStatsHelpModal = () => {
    $memoryStatsHelpModal.value?.create();
};

const getPercent = (amount: string | number, total: string | number) => {
    return ((Number(amount) / Number(total)) * 100) + '%';
}
</script>

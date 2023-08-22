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
                    <icon icon="help_outline" />
                </button>
            </div>
        </div>

        <div class="card-body">
            <h6 class="mb-1 text-center">
                {{ $gettext('Total RAM') }}
                :
                {{ stats.memory.readable.total }}
            </h6>

            <div
                class="progress h-20 mb-3 mt-2"
                role="progressbar"
                :aria-label="stats.memory.readable.used"
                aria-valuemin="0"
                :aria-valuemax="stats.memory.bytes.total"
            >
                <div
                    class="progress-bar text-bg-primary"
                    :style="{ width: getPercent(stats.memory.bytes.used, stats.memory.bytes.total) }"
                />
                <div
                    class="progress-bar text-bg-warning"
                    :style="{ width: getPercent(stats.memory.bytes.cached, stats.memory.bytes.total) }"
                />
            </div>

            <div class="row">
                <div class="col">
                    <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>
                    {{ $gettext('Used') }}
                    : {{ stats.memory.readable.used }}
                </div>
                <div class="col">
                    <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>&nbsp;

                    {{ $gettext('Cached') }}
                    : {{ stats.memory.readable.cached }}
                </div>
            </div>
        </div>
    </section>

    <memory-stats-help-modal ref="$memoryStatsHelpModal" />
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {ref} from "vue";
import MemoryStatsHelpModal from "~/components/Admin/Index/MemoryStatsHelpModal.vue";

const props = defineProps({
    stats: {
        type: Object,
        required: true
    }
});

const $memoryStatsHelpModal = ref(); // Template Ref

const showMemoryStatsHelpModal = () => {
    $memoryStatsHelpModal.value.create();
};

const getPercent = (amount, total) => {
    return ((amount / total) * 100) + '%';
}
</script>

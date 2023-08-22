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
                {{ stats.disk.readable.total }}
            </h6>

            <div
                class="progress h-20 mb-3 mt-2"
                role="progressbar"
                :aria-label="stats.disk.readable.used"
                aria-valuemin="0"
                :aria-valuemax="stats.disk.bytes.total"
            >
                <div
                    class="progress-bar text-bg-primary"
                    :style="{ width: getPercent(stats.disk.bytes.used, stats.disk.bytes.total) }"
                />
            </div>

            <div class="row">
                <div class="col">
                    <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>

                    {{ $gettext('Used') }}
                    :
                    {{ stats.disk.readable.used }}
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
const props = defineProps({
    stats: {
        type: Object,
        required: true
    }
});

const getPercent = (amount, total) => {
    return ((amount / total) * 100) + '%';
}
</script>

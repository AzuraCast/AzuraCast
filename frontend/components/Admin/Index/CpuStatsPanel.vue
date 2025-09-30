<template>
    <section class="card">
        <div class="card-header text-bg-primary d-flex align-items-center">
            <div class="flex-fill">
                <h2 class="card-title">
                    {{ $gettext('CPU Load') }}
                </h2>
            </div>

            <div class="flex-shrink-0">
                <button
                    type="button"
                    class="btn btn-dark btn-sm py-2"
                    @click="showCpuStatsHelpModal"
                >
                    <icon-ic-info/>
                </button>
            </div>
        </div>

        <div class="card-body">
            <h5 class="mb-1 text-center">
                {{ formatCpuName(cpuStats.total.name) }}
            </h5>

            <div
                class="progress h-20 mb-3 mt-2"
                role="progressbar"
                :aria-label="formatPercentageString(cpuStats.total.usage)"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="progress-bar text-bg-danger"
                    :style="{ width: cpuStats.total.steal+'%' }"
                />
                <div
                    class="progress-bar text-bg-warning"
                    :style="{ width: cpuStats.total.io_wait+'%' }"
                />
                <div
                    class="progress-bar text-bg-primary"
                    :style="{ width: cpuStats.total.usage+'%' }"
                />
            </div>

            <div class="row">
                <div class="col">
                    <span class="badge text-bg-danger me-1">&nbsp;&nbsp;</span>
                    {{ $gettext('Steal') }}
                    : {{ cpuStats.total.steal }}%
                </div>
                <div class="col">
                    <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>
                    {{ $gettext('Wait') }}
                    : {{ cpuStats.total.io_wait }}%
                </div>
                <div class="col">
                    <span class="badge text-bg-primary me-1">&nbsp;&nbsp;</span>
                    {{ $gettext('Use') }}
                    : {{ cpuStats.total.usage }}%
                </div>
            </div>

            <hr>

            <div class="row">
                <div
                    v-for="core in cpuStats.cores"
                    :key="core.name"
                    class="col-lg-6"
                >
                    <h6 class="mb-1 text-center">
                        {{ formatCpuName(core.name) }}
                    </h6>

                    <div
                        class="progress h-20 mb-3 mt-2"
                        role="progressbar"
                        :aria-label="formatPercentageString(core.usage)"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="progress-bar text-bg-danger"
                            :style="{ width: core.steal+'%' }"
                        />
                        <div
                            class="progress-bar text-bg-warning"
                            :style="{ width: core.io_wait+'%' }"
                        />
                        <div
                            class="progress-bar text-bg-primary"
                            :style="{ width: core.usage+'%' }"
                        />
                    </div>

                    <div class="row mb-2 mt-1">
                        <div class="col">
                            St: {{ core.steal }}%
                        </div>
                        <div class="col">
                            Wa: {{ core.io_wait }}%
                        </div>
                        <div class="col">
                            Us: {{ core.usage }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <h6 class="mb-1 text-center">
                {{ $gettext('Load Average') }}
            </h6>
            <div class="row text-center">
                <div class="col">
                    <h6>1-Min</h6>
                    {{ cpuStats.load[0].toFixed(2) }}
                </div>
                <div class="col">
                    <h6>5-Min</h6>
                    {{ cpuStats.load[1].toFixed(2) }}
                </div>
                <div class="col">
                    <h6>15-Min</h6>
                    {{ cpuStats.load[2].toFixed(2) }}
                </div>
            </div>
        </div>
    </section>

    <cpu-stats-help-modal ref="$cpuStatsHelpModal" />
</template>
<script setup lang="ts">
import {useTemplateRef} from "vue";
import CpuStatsHelpModal from "~/components/Admin/Index/CpuStatsHelpModal.vue";
import {upperFirst} from "es-toolkit";
import {ApiAdminServerStatsCpuStats} from "~/entities/ApiInterfaces.ts";
import IconIcInfo from "~icons/ic/baseline-info";

defineProps<{
    cpuStats: ApiAdminServerStatsCpuStats
}>();

const $cpuStatsHelpModal = useTemplateRef('$cpuStatsHelpModal');
const showCpuStatsHelpModal = () => {
    $cpuStatsHelpModal.value?.create();
};

const formatCpuName = (cpuName: string) => upperFirst(cpuName);

const formatPercentageString = (value: string | number) => value + '%';

</script>

<template>
    <b-modal size="lg" centered id="cpu_stats_help_modal" ref="modal" :title="langTitle">
        <div class="mb-2">
            <h6>
                <b-badge pill variant="danger">&nbsp;&nbsp;</b-badge>&nbsp;
                <translate key="lang_steal">Steal (St)</translate>
                :
                <translate
                    key="lang_steal_1">Time stolen by other virtual machines on the same physical server.</translate>
            </h6>
            <div class="ml-4">
                <p>
                    <translate key="lang_steal_2">Most hosting providers will put more Virtual Machines (VPSes) on a server than the hardware can handle when each VM is running at full CPU load. This is called over-provisioning, which can lead to other VMs on the server "stealing" CPU time from your VM and vice-versa.</translate>
                </p>
                <p>
                    <translate key="lang_steal_3">To alleviate this potential problem with shared CPU resources, hosts assign "credits" to a VPS which are used up according to an algorithm based on the CPU load as well as the time over which the CPU load is generated. If your VM's assigned credit is used up, they will take CPU time from your VM and assign it to other VMs on the machine. This is seen as the "Steal" or "St" value.</translate>
                </p>
                <p>
                    <translate key="lang_steal_4">Audio transcoding applications like Liquidsoap use a consistent amount of CPU over time, which gradually drains this available credit. If you regularly see stolen CPU time, you should consider migrating to a VM that has CPU resources dedicated to your instance.</translate>
                </p>
            </div>
        </div>
        <div class="mb-2">
            <h6>
                <b-badge pill variant="warning">&nbsp;&nbsp;</b-badge>&nbsp;
                <translate key="lang_wait">Wait (Wa)</translate>
                :
                <translate key="lang_wait_1">Time spent waiting for disk I/O to be completed.</translate>
            </h6>
            <div class="ml-4">
                <p>
                    <translate key="lang_wait_2">The I/O Wait is the percentage of time that the CPU is waiting for disk access before it can continue the work that depends on the result of this.</translate>
                </p>
                <p>
                    <translate key="lang_wait_3">High I/O Wait can indicate a bottleneck with the server's hard disk, a potentially failing hard disk, or heavy load on the hard disk.</translate>
                </p>
                <p>
                    <translate key="lang_wait_4">One important note on I/O Wait is that it can indicate a bottleneck or problem but also may be completely meaningless, depending on the workload and general available resources. A constantly high I/O Wait should prompt further investigation with more sophisticated tools.</translate>
                </p>
            </div>
        </div>
        <div class="mb-1">
            <h6>
                <b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;
                <translate key="lang_use">Use (Us)</translate>
                :
                <translate key="lang_use_1">The current CPU usage including I/O Wait and Steal.</translate>
            </h6>
        </div>

        <template #modal-footer>
            <slot name="modal-footer">
                <b-button variant="default" type="button" @click="close">
                    <translate key="lang_btn_close">Close</translate>
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>

export default {
    name: 'CpuStatsHelpModal',
    computed: {
        langTitle() {
            return this.$gettext('CPU Stats Help');
        }
    },
    methods: {
        create() {
            this.$refs.modal.show();
        },
        close() {
            this.$refs.modal.hide();
        }
    }
};
</script>

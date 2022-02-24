<template>
    <b-modal size="lg" centered id="cpu_stats_help_modal" ref="modal" :title="langTitle">
        <b-container fluid>
            <b-row class="mb-1">
                <b-col col>
                    <h6><b-badge pill variant="danger">&nbsp;&nbsp;</b-badge>&nbsp;Steal (St)</h6>
                    <p class="lead">Time stolen by other virtual machines on the same physical server.<p/>
                    <p>Most VM hosters are putting more VMs (also called VPSs) on a Server than the hardware can handle when each VM is running at full CPU load. This is called over-provisioning which can lead to other VMs on the server "stealing" CPU time from your VM and vice-versa.</p>
                    <p>To alleviate this potential problem with shared CPU resources hosters are assigning "credits" to a VPS which are used up according to an algorithm based on the CPU load as well as the time over which the CPU load is generated. If your VMs assigned credit is used up they will take CPU time from your VM and assign it to other VMs on the machine. This is seen as the "Steal" or "St" value.</p>
                    <p>Audio transcoding applications like Liquidsoap are constantly using some amount of CPU which constantly drains this credit. If you constantly see stolen CPU time you should switch to a dedicated resource VM where a specific amount of CPU cores is only assigned to your VM.</p>
                </b-col>
            </b-row>
            <b-row class="mb-1">
                <b-col>
                    <h6><b-badge pill variant="warning">&nbsp;&nbsp;</b-badge>&nbsp;Wait (Wa)</h6>
                    <p class="lead">Time spent waiting for disk I/O to be completed.<p/>
                    <p>The I/O Wait is the percentage of time that the CPU is waiting for disk accesses before it can continue the work that depends on the result of this.</p>
                    <p>High I/O Wait can indicate a bottleneck with the servers hard disk, a potentially failing hard disk or just generally a lot of load on the hard disk.</p>
                    <p>One important note on I/O Wait is that it can indicate a bottleneck or problem but also may be completely meaningless depending on the workload and general available resources. A constantly high I/O Wait should prompt further investigation with more sophisticated tools.</p>
                </b-col>
            </b-row>
            <b-row class="mb-1">
                <b-col>
                    <h6><b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;Use (Us)</h6>
                    <p class="lead">The current CPU usage including I/O Wait and Steal.<p/>
                </b-col>
            </b-row>
        </b-container>

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

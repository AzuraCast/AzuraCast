<template>
    <modal
        id="cpu_stats_help_modal"
        ref="$modal"
        size="lg"
        centered
        :title="$gettext('CPU Stats Help')"
    >
        <div class="mb-2">
            <h6>
                <span class="badge text-bg-danger me-1">&nbsp;&nbsp;</span>

                {{ $gettext('Steal (St)') }}:
                {{ $gettext('Time stolen by other virtual machines on the same physical server.') }}
            </h6>
            <div class="ms-4">
                <p>
                    {{
                        $gettext('Most hosting providers will put more Virtual Machines (VPSes) on a server than the hardware can handle when each VM is running at full CPU load. This is called over-provisioning, which can lead to other VMs on the server "stealing" CPU time from your VM and vice-versa.')
                    }}
                </p>
                <p>
                    {{
                        $gettext('To alleviate this potential problem with shared CPU resources, hosts assign "credits" to a VPS which are used up according to an algorithm based on the CPU load as well as the time over which the CPU load is generated. If your VM\'s assigned credit is used up, they will take CPU time from your VM and assign it to other VMs on the machine. This is seen as the "Steal" or "St" value.')
                    }}
                </p>
                <p>
                    {{
                        $gettext('Audio transcoding applications like Liquidsoap use a consistent amount of CPU over time, which gradually drains this available credit. If you regularly see stolen CPU time, you should consider migrating to a VM that has CPU resources dedicated to your instance.')
                    }}
                </p>
            </div>
        </div>
        <div class="mb-2">
            <h6>
                <span class="badge text-bg-warning me-1">&nbsp;&nbsp;</span>

                {{ $gettext('Wait (Wa)') }}:
                {{ $gettext('Time spent waiting for disk I/O to be completed.') }}
            </h6>
            <div class="ms-4">
                <p>
                    {{
                        $gettext('The I/O Wait is the percentage of time that the CPU is waiting for disk access before it can continue the work that depends on the result of this.')
                    }}
                </p>
                <p>
                    {{
                        $gettext('High I/O Wait can indicate a bottleneck with the server\'s hard disk, a potentially failing hard disk, or heavy load on the hard disk.')
                    }}
                </p>
                <p>
                    {{
                        $gettext('One important note on I/O Wait is that it can indicate a bottleneck or problem but also may be completely meaningless, depending on the workload and general available resources. A constantly high I/O Wait should prompt further investigation with more sophisticated tools.')
                    }}
                </p>
            </div>
        </div>
        <div class="mb-1">
            <h6>
                <span class="badge text-bg-primary me-1">&nbsp;</span>
                {{ $gettext('Use (Us)') }}:
                {{ $gettext('The current CPU usage including I/O Wait and Steal.') }}
            </h6>
        </div>

        <template #modal-footer>
            <slot name="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </button>
            </slot>
        </template>
    </modal>
</template>

<script setup>
import {ref} from "vue";
import Modal from "~/components/Common/Modal.vue";

const $modal = ref(); // BModal

const create = () => {
    $modal.value.show();
}
const close = () => {
    $modal.value.hide();
}

defineExpose({
    create,
    close
});
</script>

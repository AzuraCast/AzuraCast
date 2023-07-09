<template>
    <modal
        id="audit_log_details"
        ref="$modal"
        :title="$gettext('Changes')"
        size="xl"
    >
        <table class="table table-bordered table-responsive">
            <colgroup>
                <col width="30%">
                <col width="35%">
                <col width="35%">
            </colgroup>
            <thead>
                <tr>
                    <th>{{ $gettext('Field Name') }}</th>
                    <th>{{ $gettext('Previous') }}</th>
                    <th>{{ $gettext('Updated') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="change in changes"
                    :key="change.field"
                >
                    <td>{{ change.field }}</td>
                    <td>
                        <pre class="changes">{{ change.from }}</pre>
                    </td>
                    <td>
                        <pre class="changes">{{ change.to }}</pre>
                    </td>
                </tr>
            </tbody>
        </table>
    </modal>
</template>

<script setup>
import Modal from "~/components/Common/Modal.vue";
import {ref} from "vue";

const $modal = ref(); // Modal

const changes = ref(null);

const open = (newChanges) => {
    changes.value = newChanges;
    $modal.value.show();
};

defineExpose({
    open
});
</script>

<style lang="scss">
pre.changes {
    max-width: 250px;
    margin-bottom: 0;
}
</style>

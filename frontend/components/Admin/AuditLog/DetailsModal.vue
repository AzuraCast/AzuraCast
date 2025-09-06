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

<script setup lang="ts">
import Modal from "~/components/Common/Modal.vue";
import {ref, useTemplateRef} from "vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {ApiAdminAuditLogChangeset} from "~/entities/ApiInterfaces.ts";

const $modal = useTemplateRef('$modal');
const {show} = useHasModal($modal);

const changes = ref<ApiAdminAuditLogChangeset[] | null>(null);

const open = (newChanges: ApiAdminAuditLogChangeset[]) => {
    changes.value = newChanges;
    show();
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

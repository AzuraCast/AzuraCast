<template>
    <div class="row row-cols-md-auto g-3 align-items-center">
        <div class="col-12">
            <label for="minLength">{{ $gettext('Min. Connected Time') }}</label>
            <div class="input-group input-group-sm">
                <input
                    id="minLength"
                    v-model="filters.minLength"
                    type="number"
                    class="form-control"
                    min="0"
                    step="1"
                >
            </div>
        </div>
        <div class="col-12">
            <label for="maxLength">{{ $gettext('Max. Connected Time') }}</label>
            <div class="input-group input-group-sm">
                <input
                    id="maxLength"
                    v-model="filters.maxLength"
                    type="number"
                    class="form-control"
                    min="0"
                    step="1"
                >
            </div>
        </div>
        <div class="col-12">
            <label for="type">{{ $gettext('Listener Type') }}</label>
            <div class="input-group input-group-sm">
                <select
                    v-model="filters.type"
                    class="form-select form-select-sm"
                >
                    <option :value="ListenerTypeFilter.All">
                        {{ $gettext('All Types') }}
                    </option>
                    <option :value="ListenerTypeFilter.Mobile">
                        {{ $gettext('Mobile') }}
                    </option>
                    <option :value="ListenerTypeFilter.Desktop">
                        {{ $gettext('Desktop') }}
                    </option>
                    <option :value="ListenerTypeFilter.Bot">
                        {{ $gettext('Bot/Crawler') }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-12">
            <button
                type="button"
                class="btn btn-sm btn-secondary"
                @click="clearFilters"
            >
                <icon :icon="IconClearAll" />
                <span>
                    {{ $gettext('Clear Filters') }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import {ListenerFilters, ListenerTypeFilter} from "./listenerFilters.ts";
import {useVModel} from "@vueuse/core";
import {WritableComputedRef} from "vue";
import {IconClearAll} from "~/components/Common/icons.ts";
import Icon from "~/components/Common/Icon.vue";

const props = defineProps<{
    filters: ListenerFilters
}>();

const emit = defineEmits(['update:filters']);
const filters: WritableComputedRef<ListenerFilters> = useVModel(props, 'filters', emit);

const clearFilters = () => {
    filters.value.minLength = null;
    filters.value.maxLength = null;
    filters.value.type = ListenerTypeFilter.All;
}
</script>

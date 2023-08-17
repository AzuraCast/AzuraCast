<template>
    <div
        v-for="(row, id) in logs"
        id="log-view"
        :key="id"
        class="card mb-3"
    >
        <div
            :id="'log-row-'+id"
            class="card-header"
        >
            <h4 class="mb-0">
                <span
                    class="badge"
                    :class="getBadgeClass(row.level)"
                >{{ getBadgeLabel(row.level) }}</span>
                {{ row.message }}
            </h4>

            <div
                v-if="row.context || row.extra"
                class="buttons mt-3"
            >
                <button
                    class="btn btn-sm btn-bg"
                    type="button"
                    data-bs-toggle="collapse"
                    :data-bs-target="'#detail-row-'+id"
                    :aria-controls="'detail-row-'+id"
                >
                    {{ $gettext('Details') }}
                </button>
            </div>
        </div>

        <div
            v-if="row.context || row.extra"
            :id="'detail-row-'+id"
            class="collapse"
            :aria-labelledby="'log-row-'+id"
            data-parent="#log-view"
        >
            <div class="card-body pb-0">
                <dl>
                    <template
                        v-for="(context_value, context_header) in row.context"
                        :key="context_header"
                    >
                        <dt>{{ context_header }}</dt>
                        <dd>{{ dump(context_value) }}</dd>
                    </template>
                    <template
                        v-for="(context_value, context_header) in row.extra"
                        :key="context_header"
                    >
                        <dt>{{ context_header }}</dt>
                        <dd>{{ dump(context_value) }}</dd>
                    </template>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {useTranslate} from "~/vendor/gettext.ts";
import {get} from 'lodash';

const props = defineProps({
    logs: {
        type: Array,
        required: true
    }
});

const badgeClasses = {
    100: 'text-bg-info',
    200: 'text-bg-info',
    250: 'text-bg-info',
    300: 'text-bg-warning',
    400: 'text-bg-danger',
    500: 'text-bg-danger',
    550: 'text-bg-danger',
    600: 'text-bg-danger'
};
const getBadgeClass = (logLevel) => {
    return get(badgeClasses, logLevel, badgeClasses[100]);
};

const {$gettext} = useTranslate();

const badgeLabels = {
    100: $gettext('Debug'),
    200: $gettext('Info'),
    250: $gettext('Notice'),
    300: $gettext('Warning'),
    400: $gettext('Error'),
    500: $gettext('Critical'),
    550: $gettext('Alert'),
    600: $gettext('Emergency')
};
const getBadgeLabel = (logLevel) => {
    return get(badgeLabels, logLevel, badgeLabels[100]);
};

const dump = (value) => {
    return JSON.stringify(value);
}
</script>

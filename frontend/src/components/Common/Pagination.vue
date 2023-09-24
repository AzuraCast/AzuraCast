<template>
    <nav class="d-flex">
        <ul class="pagination mb-0">
            <pagination-item
                v-if="hasFirst"
                :page="1"
                :active="page === 1"
                @click="setPage"
            />

            <pagination-item
                v-if="hasFirstEllipsis"
                label="..."
                disabled
            />

            <pagination-item
                v-for="pageNum in pagesInRange"
                :key="pageNum"
                :page="pageNum"
                :active="page === pageNum"
                @click="setPage"
            />

            <pagination-item
                v-if="hasLastEllipsis"
                label="..."
                disabled
            />

            <pagination-item
                v-if="hasLast"
                :page="pageCount"
                :active="page === pageCount"
                @click="setPage"
            />
        </ul>

        <div
            v-if="showInput"
            class="input-group input-group-sm ms-2"
        >
            <input
                v-model="inputPage"
                type="number"
                :min="1"
                :max="pageCount"
                step="1"
                class="form-control rounded-start-2"
                :aria-label="$gettext('Page')"
                style="max-width: 60px;"
                @keydown.enter.prevent="goToPage"
            >
            <button
                class="btn btn-outline-secondary rounded-end-2"
                type="button"
                @click="goToPage"
            >
                {{ $gettext('Go') }}
            </button>
        </div>
    </nav>
</template>

<script setup lang="ts">
import {computed, ref, toRef, watch} from "vue";
import PaginationItem from "~/components/Common/PaginationItem.vue";
import {clamp} from "lodash";

const props = defineProps({
    total: {
        type: Number,
        required: true
    },
    perPage: {
        type: Number,
        required: true,
    },
    currentPage: {
        type: Number,
        default: 1
    },
    pageSpace: {
        type: Number,
        default: 1
    }
});

const emit = defineEmits(['update:currentPage', 'change']);

const pageCount = computed(() => Math.max(
    1,
    Math.ceil(props.total / props.perPage),
));

const inputPage = ref(1);

const setPage = (newPage: number) => {
    newPage = clamp(newPage, 1, pageCount.value);

    page.value = newPage;
    inputPage.value = newPage;
};

watch(toRef(props, 'currentPage'), (newPage: number) => {
    setPage(newPage);
});

const page = computed({
    get() {
        return props.currentPage;
    },
    set(newValue) {
        emit('update:currentPage', newValue);
        emit('change', newValue);
    }
});

const hasFirst = computed(
    () => page.value >= (props.pageSpace + 2)
);
const hasFirstEllipsis = computed(
    () => page.value >= (props.pageSpace + 4)
);

const hasLast = computed(
    () => page.value <= (pageCount.value - (props.pageSpace + 1))
);
const hasLastEllipsis = computed(
    () => page.value < (pageCount.value - (props.pageSpace + 2))
);

const pagesInRange = computed(() => {
    let left = Math.max(1, page.value - props.pageSpace)
    if (left - 1 === 2) {
        left-- // Do not show the ellipsis if there is only one to hide
    }
    let right = Math.min(page.value + props.pageSpace, pageCount.value)
    if (pageCount.value - right === 2) {
        right++ // Do not show the ellipsis if there is only one to hide
    }

    const pages = []
    for (let i = left; i <= right; i++) {
        pages.push(i)
    }
    return pages
});

const showInput = computed(() => {
    return pageCount.value > 10;
});

const goToPage = () => {
    setPage(inputPage.value);
};
</script>

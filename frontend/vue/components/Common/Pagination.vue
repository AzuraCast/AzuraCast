<template>
    <nav>
        <ul class="pagination mb-0">
            <li
                v-if="hasFirst"
                class="page-item"
                :class="[
                    (page === 1) ? 'active' : ''
                ]"
            >
                <a
                    class="page-link"
                    href="#"
                    @click.prevent="page = 1"
                >1</a>
            </li>

            <li
                v-if="hasFirstEllipsis"
                class="page-item disabled"
            >
                <a
                    class="page-link"
                    href="#"
                    tabindex="-1"
                    @click.prevent=""
                >
                    ...
                </a>
            </li>

            <li
                v-for="pageNum in pagesInRange"
                :key="pageNum"
                class="page-item"
                :class="[
                    (page === pageNum) ? 'active' : ''
                ]"
            >
                <a
                    class="page-link"
                    href="#"
                    @click.prevent="page = pageNum"
                >{{ pageNum }}</a>
            </li>

            <li
                v-if="hasLastEllipsis"
                class="page-item disabled"
            >
                <a
                    class="page-link"
                    href="#"
                    tabindex="-1"
                    @click.prevent=""
                >
                    ...
                </a>
            </li>

            <li
                v-if="hasLast"
                class="page-item"
                :class="[
                    (page === pageCount) ? 'active' : ''
                ]"
            >
                <a
                    class="page-link"
                    href="#"
                    @click.prevent="page = pageCount"
                >{{ pageCount }}</a>
            </li>
        </ul>
    </nav>
</template>

<script setup lang="ts">
import {computed} from "vue";

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
</script>

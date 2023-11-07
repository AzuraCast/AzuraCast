<template>
    <div class="d-flex flex-row align-items-center">
        <div class="flex-shrink-0">
            <icon :icon="IconVolumeOff" />
        </div>
        <div class="flex-fill px-2">
            <input
                v-model.number="volume"
                type="range"
                min="0"
                max="100"
                class="form-range slider"
                style="height: 10px; width: 100px;"
                @click.right.prevent="reset"
            >
        </div>
        <div class="flex-shrink-0">
            <icon :icon="IconVolumeUp" />
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {onMounted, ref} from "vue";
import {useVModel} from "@vueuse/core";
import {IconVolumeOff, IconVolumeUp} from "~/components/Common/icons";

const props = defineProps({
    modelValue: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const initial = ref(75);
onMounted(() => {
    initial.value = props.modelValue;
});

const volume = useVModel(props, 'modelValue', emit);

const reset = () => {
    volume.value = initial.value;
}
</script>

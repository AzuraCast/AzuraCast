<template>
    <div class="d-flex flex-row align-items-center">
        <div class="flex-shrink-0">
            <icon icon="volume_mute" />
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
            <icon icon="volume_up" />
        </div>
    </div>
</template>

<script setup>
import Icon from "~/components/Common/Icon";
import {onMounted, ref} from "vue";
import {useVModel} from "@vueuse/core";

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

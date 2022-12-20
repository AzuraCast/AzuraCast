<template>
    <div class="d-flex flex-row align-items-center">
        <div class="flex-shrink-0">
            <icon icon="volume_mute"></icon>
        </div>
        <div class="flex-fill px-2">
            <input type="range" min="0" max="100" class="custom-range slider"
                   v-model.number="volume" @click.right.prevent="reset" style="height: 10px; width: 100px;">
        </div>
        <div class="flex-shrink-0">
            <icon icon="volume_up"></icon>
        </div>
    </div>
</template>

<script setup>
import Icon from "~/components/Common/Icon";
import {onMounted, ref} from "vue";
import {useVModel} from "@vueuse/core";

const props = defineProps({
    modelValue: String
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

<script>
export default {
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    }
}
</script>

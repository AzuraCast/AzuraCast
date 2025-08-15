<template>
    <radio-with-custom-number
        :id="id"
        :name="name"
        :options="bitrateOptions"
        v-model="model"
    >
        <template
            v-for="(_, slot) of slots"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </radio-with-custom-number>
</template>

<script setup lang="ts" generic="T = string | number | null">
import {map} from "lodash";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import RadioWithCustomNumber from "~/components/Common/RadioWithCustomNumber.vue";
import {useSlots} from "vue";

interface BitrateOptionsProps {
    id: string,
    name?: string,
    inputAttrs?: object,
    maxBitrate: number
}

const props = defineProps<BitrateOptionsProps>();

const slots = useSlots();

const model = defineModel<T>();

const radioBitrates = [
    32, 48, 64, 96, 128, 192, 256, 320
].filter((bitrate) => props.maxBitrate === 0 || bitrate <= props.maxBitrate);

const bitrateOptions: SimpleFormOptionInput = map(
    radioBitrates,
    (val: number) => {
        return {
            value: val,
            text: String(val)
        };
    }
);
</script>

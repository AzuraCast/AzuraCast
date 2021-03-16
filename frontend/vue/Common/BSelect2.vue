<template>
    <b-select :value="value" @input="this.$emit('input', $event.target.value)" v-bind="$attrs"
              :class="{ 'select2-hidden-accessible': true }" plain>
        <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
        <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData">
            <slot :name="name" v-bind="slotData"/>
        </template>
    </b-select>
</template>
<script>
import _ from 'lodash';

export default {
    name: 'BSelect2',
    inheritAttrs: false,
    props: {
        value: {},
        selectOptions: {
            type: Object,
            default () {
                return {};
            }
        }
    },
    mounted () {
        $(this.$el)
            .select2(this.allSelectOptions)
            .val(this.value);
    },
    computed: {
        allSelectOptions () {
            let options = _.clone(this.selectOptions);
            _.defaults(options, {
                width: '100%',
                theme: 'bootstrap4',
                language: App.lang.locale_short
            });
            return options;
        }
    },
    destroyed: function () {
        $(this.$el)
            .off()
            .select2('destroy');
    }
};
</script>

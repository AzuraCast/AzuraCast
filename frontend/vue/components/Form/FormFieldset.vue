<script>
import {h} from 'vue'

export default {
    name: 'FormFieldset',
    methods: {
        getSlot(name, scope = {}) {
            let slot = this.$slots[name] ?? null
            return typeof slot === 'function' ? slot(scope) : slot
        }
    },
    render() {
        const legendSlot = this.getSlot('label');
        const descriptionSlot = this.getSlot('description');

        const slotChildren = [];

        if (legendSlot) {
            const headerChildren = [];
            if (descriptionSlot) {
                headerChildren.push(h('p', {}, [descriptionSlot]));
            }

            headerChildren.push(h('legend', {}, [legendSlot]));

            slotChildren.push(h(
                'div',
                {
                    class: 'fieldset-legend'
                },
                headerChildren
            ));
        }

        slotChildren.push(this.getSlot('default'));

        return h(
            'fieldset',
            {
                class: 'form-group'
            },
            slotChildren
        );
    }
}
</script>

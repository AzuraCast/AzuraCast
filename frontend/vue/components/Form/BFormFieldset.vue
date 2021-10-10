<script>
export default {
    name: 'BFormFieldset',
    methods: {
        getSlot(name, scope = {}) {
            let slot = this.$scopedSlots[name] || this.$slots[name]
            return typeof slot === 'function' ? slot(scope) : slot
        }
    },
    render(h) {
        const legendSlot = this.getSlot('label');
        const descriptionSlot = this.getSlot('description');

        let header = h();

        if (legendSlot) {
            const description = descriptionSlot
                ? h('p', {}, [descriptionSlot])
                : h();

            const legend = h('legend', {}, [legendSlot]);

            header = h(
                'div',
                {
                    staticClass: 'fieldset-legend'
                },
                [
                    legend,
                    description
                ]
            );
        }

        return h(
            'fieldset',
            {
                staticClass: 'form-group'
            },
            [
                header,
                this.getSlot('default') || h()
            ]
        )
    }
}
</script>

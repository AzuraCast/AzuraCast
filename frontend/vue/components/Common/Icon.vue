<template>
    <i :class="iconClass" aria-hidden="true">{{ iconContent }}</i>
</template>

<script>
export default {
    name: 'Icon',
    props: {
        type: {
            type: String,
            default: 'md'
        },
        icon: {
            type: String,
            required: true
        },
        class: {
            type: String,
            default: '',
        }
    },
    computed: {
        iconClass () {
            let classes = [this.class];

            switch (this.type) {
                case 'md':
                default:
                    classes.push('material-icons');
            }

            return classes;
        },
        iconContent () {
            switch (this.type) {
                case 'md':
                default:
                    return this.icon;
            }
        }
    }
};
</script>

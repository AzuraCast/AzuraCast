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
        }
    },
    computed: {
        iconClass () {
            switch (this.type) {
                case 'md':
                default:
                    return ['material-icons'];
            }
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

<template>
    <textarea ref="textarea" spellcheck="false" v-model="modelValue"></textarea>
</template>

<script>
export default {
    name: 'CodemirrorTextarea',
    props: {
        value: String,
        mode: String
    },
    computed: {
        modelValue: {
            get () {
                return this.value;
            },
            set (newValue) {
                this.$emit('input', newValue);
            }
        }
    },
    data() {
        return {
            codemirror: null
        };
    },
    mounted() {
        this.$nextTick(() => {
            this.codemirror = CodeMirror.fromTextArea(this.$refs.textarea, {
                lineNumbers: true,
                theme: 'default',
                mode: this.mode
            });

            this.refresh();
        });
    },
    beforeDestroy() {
        const element = this.codemirror.doc.cm.getWrapperElement()
        element && element.remove && element.remove()
    },
    methods: {
        refresh() {
            this.$nextTick(() => {
                this.codemirror.refresh()
            })
        },
    }
}
</script>

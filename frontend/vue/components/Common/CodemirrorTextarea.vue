<template>
    <textarea ref="textarea" spellcheck="false" :value="value" @input="this.$emit('input', $event.target.value)"/>
</template>

<script>
import Codemirror from 'codemirror';
import 'codemirror/lib/codemirror.css';
import 'codemirror/mode/css/css.js';
import 'codemirror/mode/javascript/javascript.js';

export default {
    name: 'CodemirrorTextarea',
    props: {
        value: String,
        mode: String
    },
    data() {
        return {
            content: null,
            codemirror: null
        };
    },
    watch: {
        value(newVal) {
            newVal = newVal || '';
            const cm_value = this.codemirror.getValue();
            if (newVal !== cm_value) {
                this.content = newVal;
                this.codemirror.setValue(this.content);
            }
        }
    },
    mounted() {
        this.codemirror = Codemirror.fromTextArea(this.$refs.textarea, {
            lineNumbers: true,
            theme: 'default',
            mode: this.mode
        });

        this.content = this.value || '';
        this.codemirror.setValue(this.content);

        this.codemirror.on('change', cm => {
            this.$emit('input', cm.getValue());
        });

        this.refresh();
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

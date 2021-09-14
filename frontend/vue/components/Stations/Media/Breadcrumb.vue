<template>
    <h3 class="card-subtitle mt-0 mb-2" id="breadcrumb">
        <a href="#" key="lang_home" @click.prevent="changeDirectory('')" v-translate>Home</a>
        <template v-for="part in directoryParts">
            &blacktriangleright;
            <a href="#" @click.prevent="changeDirectory(part.dir)">{{ part.display }}</a>
        </template>
    </h3>
</template>

<script>
export default {
    name: 'Breadcrumb',
    props: {
        currentDirectory: String
    },
    computed: {
        directoryParts () {
            let dirParts = [];

            if (this.currentDirectory === '') {
                return dirParts;
            }

            let builtDir = '';
            let dirSegments = this.currentDirectory.split('/');

            dirSegments.forEach((part) => {
                if (builtDir === '') {
                    builtDir += part;
                } else {
                    builtDir += '/' + part;
                }

                dirParts.push({ dir: builtDir, display: part });
            });

            return dirParts;
        }
    },
    methods: {
        changeDirectory (newDir) {
            this.$emit('change-directory', newDir);
        }
    }
};
</script>

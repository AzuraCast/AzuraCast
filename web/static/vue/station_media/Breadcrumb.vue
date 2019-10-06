<template>
    <h3 class="card-subtitle mt-0 mb-2" id="breadcrumb">
        <a href="#" @click.prevent="changeDirectory('')" v-translate>Home</a>
        <template v-for="(_, part) in directoryParts">
            &nbsp;&blacktriangleright;&nbsp;
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
        let dirParts = []

        let builtDir = ''
        let dirSegments = this.currentDirectory.split('/')

        dirSegments.forEach((part) => {
          builtDir += part + '/'
          dirParts.push({ dir: builtDir, display: part })
        })

        return dirParts
      }
    },
    methods: {
      changeDirectory (newDir) {
        this.$emit('change-directory', newDir)
      }
    }
  }
</script>
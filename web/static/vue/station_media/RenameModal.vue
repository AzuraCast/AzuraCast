<template>

</template>
<script>
  export default {
    name: 'RenameModal',
    props: {
      file: String
    },
    data () {
      return {
        form: {
          file: this.file,
          newPath: this.file
        }
      }
    }
  }
</script>
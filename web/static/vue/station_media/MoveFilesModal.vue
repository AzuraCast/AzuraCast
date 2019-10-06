<template>

</template>
<script>
  export default {
    name: 'MoveFilesModal',
    data () {
      return {
        selected_files: 0,
        move_dir: '',
        dir_history: [],
        directory_grid: null
      }
    },
    methods: {
      pageBack: function (e) {
        e.preventDefault()

        this.dir_history.pop()
        this.move_dir = this.dir_history.slice(-1)[0]
        this.directory_grid.bootgrid('reload')
      }
    }
  }
</script>
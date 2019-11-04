<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-spinner v-if="loading">
        </b-spinner>

    </b-modal>
</template>

<script>
  export default {
    name: 'EditModal',
    props: {
      createUrl: String
    },
    data () {
      return {
        loading: true
      }
    },
    computed: {
      langTitle () {
        return this.$gettext('Reorder Playlist')
      }
    },
    methods: {
      create () {

      },
      edit (recordUrl) {

      }
    }
  }
</script>
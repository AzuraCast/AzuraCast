<template>
    <div>
        <b-row class="align-items-center" v-if="showToolbar">
            <b-col md="6">
                <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                              class="mb-0" v-if="paginated">
                </b-pagination>
            </b-col>
            <b-col md="3">
                <div class="input-group">
                    <span class="icon glyphicon input-group-addon search"></span>
                    <input type="text" v-model="filter" class="search-field form-control" placeholder="Search">
                </div>
            </b-col>
            <b-col md="3">
                <div class="actions btn-group">
                    <button class="btn btn-default" type="button" title="Refresh"
                            @click.stop.prevent="onClickRefresh">
                        <i class="material-icons">refresh</i>
                    </button>
                    <div class="dropdown btn-group" v-if="paginated">
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                            <span class="dropdown-text">{{ perPageLabel }}</span>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li v-for="pageOption in pageOptions" :class="{ active: (pageOption === perPage) }">
                                <a href="#" @click.prevent="setPerPage(pageOption)"
                                   class="dropdown-item dropdown-item-button">
                                    {{ getPerPageLabel(pageOption) }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </b-col>
        </b-row>

        <b-table ref="table" show-empty striped hover :selectable="selectable" :api-url="apiUrl" :per-page="perPage"
                 :current-page="currentPage" @row-selected="onRowSelected" :items="loadItems" :fields="fields"
                 tbody-tr-class="align-middle" selected-variant=""
                 :filter="filter" :filter-debounce="200" @filtered="onFiltered">
            <template v-slot:cell(selected)="{ rowSelected }">
                <div class="custom-control custom-checkbox pl-0">
                    <input type="checkbox" class="custom-control-input position-static" :checked="rowSelected">
                    <label class="custom-control-label"></label>
                </div>
            </template>
            <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
            <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData">
                <slot :name="name" v-bind="slotData"/>
            </template>
        </b-table>

        <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                      class="mb-0" v-if="paginated">
        </b-pagination>
    </div>
</template>

<style lang="scss">
    table.b-table-selectable {
        tr > td:nth-child(1) {
            padding-right: 0.75rem;
        }

        tr > td:nth-child(2) {
            padding-left: 0.5rem;
        }
    }
</style>

<script>
  import axios from 'axios'
  import store from 'store'

  // import Vue from 'vue'
  // import { LayoutPlugin, PaginationPlugin, TablePlugin } from 'bootstrap-vue'

  /*
  Vue.use(LayoutPlugin)
  Vue.use(TablePlugin)
  Vue.use(PaginationPlugin)
  */

  export default {
    name: 'DataTable',
    props: {
      id: String,
      apiUrl: String,
      paginated: {
        type: Boolean,
        default: false
      },
      showToolbar: {
        type: Boolean,
        default: true
      },
      defaultPerPage: {
        type: Number,
        default: 10
      },
      fields: Array,
      selectable: {
        type: Boolean,
        default: false
      },
      requestConfig: Function,
      requestProcess: Function
    },
    data () {
      return {
        selected: [],
        storeKey: 'datatable_' + this.id + '_perpage',
        filter: null,
        perPage: (this.paginated) ? this.defaultPerPage : 0,
        perPageText: this.defaultPerPage,
        pageOptions: [10, 25, 50, -1],
        currentPage: 1,
        totalRows: 0,
        flushCache: false
      }
    },
    mounted () {
      if (store.enabled && store.get(this.storeKey) !== undefined) {
        this.perPage = store.get(this.storeKey)
      }
    },
    watch: {
      perPage (newPerPage, oldPerPage) {
        store.set(this.storeKey, newPerPage)
      }
    },
    computed: {
      perPageLabel () {
        return this.getPerPageLabel(this.perPage)
      }
    },
    methods: {
      getPerPageLabel (num) {
        return (num === -1) ? 'All' : num
      },
      setPerPage (num) {
        this.perPage = num
      },
      onClickRefresh (e) {
        if (e.shiftKey) {
          this.list()
        } else {
          this.refresh()
        }
      },
      refresh () {
        this.$refs.table.refresh()
      },
      list () {
        this.filter = null
        this.currentPage = 1
        this.flushCache = true
        this.refresh()
      },
      setFilter (newTerm) {
        this.filter = newTerm
      },
      loadItems (ctx, callback) {
        let queryParams = {}

        if (this.paginated) {
          queryParams.rowCount = ctx.perPage
          queryParams.current = ctx.currentPage
        }

        if (this.flushCache) {
          queryParams.flushCache = true
        }

        if (typeof ctx.filter === 'string') {
          queryParams.searchPhrase = ctx.filter
        }

        if ('' !== ctx.sortBy) {
          queryParams.sort = {}
          queryParams.sort[ctx.sortBy] = (ctx.sortDesc) ? 'DESC' : 'ASC'
        }

        let requestConfig = { params: queryParams }
        if (typeof this.requestConfig === 'function') {
          requestConfig = this.requestConfig(requestConfig)
        }

        axios.get(ctx.apiUrl, requestConfig).then((resp) => {
          this.flushCache = false
          this.totalRows = resp.data.total

          let rows = resp.data.rows
          if (typeof this.requestProcess === 'function') {
            rows = this.requestProcess(rows)
          }

          callback(rows)
        }).catch((err) => {
          this.flushCache = false
          this.totalRows = 0

          console.error(err.data.message)
          callback([])
        })
      },
      onRowSelected (items) {
        this.selected = items
        this.$emit('row-selected', items)
      },
      onFiltered (filter) {
        this.$emit('filtered', filter)
      }
    }
  }
</script>
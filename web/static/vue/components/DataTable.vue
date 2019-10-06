<template>
    <div>
        <b-row class="align-items-center">
            <b-col md="6">
                <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                              class="mb-0">
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
                    <button class="btn btn-default" type="button" title="Refresh" @click="refresh()">
                        <i class="material-icons">refresh</i>
                    </button>
                    <div class="dropdown btn-group">
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
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" :checked="rowSelected">
                </div>
            </template>
            <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
            <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData">
                <slot :name="name" v-bind="slotData"/>
            </template>
        </b-table>

        <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                      class="mb-0">
        </b-pagination>
    </div>
</template>

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
        filter: null,
        perPage: this.defaultPerPage,
        perPageText: this.defaultPerPage,
        pageOptions: [10, 25, 50, -1],
        currentPage: 1,
        totalRows: 0,
        flushCache: false
      }
    },
    created () {
      let storeKey = 'datatable_' + this.id + '_perpage'

      if (store.enabled && store.get(storeKey) !== undefined) {
        this.perPage = store.get(storeKey)
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
        let queryParams = {
          rowCount: ctx.perPage,
          current: ctx.currentPage,
          searchPhrase: ctx.filter,
          sort: []
        }

        if (this.flushCache) {
          queryParams.flush_cache = true
        }

        if ('' !== ctx.sortBy) {
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
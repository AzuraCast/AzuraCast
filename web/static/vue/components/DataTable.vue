<template>
    <div>
        <div class="d-flex align-items-center mb-2" v-if="showToolbar">
            <div class="flex-fill">
                <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                              class="mb-0" v-if="showPagination">
                </b-pagination>
            </div>
            <div class="flex-shrink-1 pl-3">
                <div class="input-group">
                    <span class="icon glyphicon input-group-addon search"></span>
                    <input type="text" v-model="filter" class="search-field form-control" placeholder="Search">
                </div>
            </div>
            <div class="flex-shrink-1 pl-3 pr-3">
                <b-btn-group class="actions">
                    <b-button variant="default" title="Refresh" @click="onClickRefresh" v-b-tooltip.hover
                              :title="langRefreshTooltip">
                        <i class="material-icons">refresh</i>
                    </b-button>
                    <b-dropdown variant="default" :text="perPageLabel" v-b-tooltip.hover :title="langPerPageTooltip">
                        <b-dropdown-item v-for="pageOption in pageOptions" :key="pageOption"
                                         :active="(pageOption === perPage)" @click="setPerPage(pageOption)">
                            {{ getPerPageLabel(pageOption) }}
                        </b-dropdown-item>
                    </b-dropdown>
                    <b-dropdown variant="default" v-if="selectFields" v-b-tooltip.hover
                                :title="langSelectFieldsTooltip">
                        <template v-slot:button-content>
                            <i class="material-icons" aria-hidden="true">filter_list</i>
                            <span class="caret"></span>
                        </template>
                        <b-dropdown-form class="pt-3">
                            <div v-for="field in selectableFields" class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                           v-bind:id="'chk_field_' + field.key" name="is_field_visible"
                                           v-model="field.visible" @change="storeSettings">
                                    <label class="custom-control-label" v-bind:for="'chk_field_'+field.key">
                                        {{ field.label }}
                                    </label>
                                </div>
                            </div>
                        </b-dropdown-form>
                    </b-dropdown>
                </b-btn-group>
            </div>
        </div>

        <b-table ref="table" show-empty striped hover :selectable="selectable" :api-url="apiUrl" :per-page="perPage"
                 :current-page="currentPage" @row-selected="onRowSelected" :items="loadItems" :fields="visibleFields"
                 tbody-tr-class="align-middle" thead-tr-class="align-middle" selected-variant=""
                 :filter="filter" :filter-debounce="200" @filtered="onFiltered" @refreshed="onRefreshed">
            <template v-slot:head(selected)="data">
                <div class="custom-control custom-checkbox pl-0" @click="toggleSelected">
                    <input type="checkbox" class="custom-control-input" :checked="allSelected">
                    <label class="custom-control-label">&nbsp;</label>
                </div>
            </template>
            <template v-slot:cell(selected)="{ rowSelected }">
                <div class="custom-control custom-checkbox pl-0">
                    <input type="checkbox" class="custom-control-input" :checked="rowSelected">
                    <label class="custom-control-label">&nbsp;</label>
                </div>
            </template>
            <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
            <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData">
                <slot :name="name" v-bind="slotData"/>
            </template>
        </b-table>

        <b-pagination v-model="currentPage" :total-rows="totalRows" :per-page="perPage"
                      class="mb-0 mt-2" v-if="showPagination">
        </b-pagination>
    </div>
</template>

<style lang="scss">
    table.b-table-selectable {
        thead tr th:nth-child(1),
        tbody tr td:nth-child(1) {
            padding-right: 0.75rem;
            width: 3rem;
        }

        thead tr th:nth-child(2),
        tbody tr td:nth-child(2) {
            padding-left: 0.5rem;
        }
    }
</style>

<script>
  import axios from 'axios'
  import store from 'store'
  import _ from 'lodash'

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
      selectFields: {
        type: Boolean,
        default: false
      },
      requestConfig: Function,
      requestProcess: Function
    },
    data () {
      return {
        selected: [],
        allSelected: false,
        storeKey: 'datatable_' + this.id + '_settings',
        filter: null,
        perPage: (this.paginated) ? this.defaultPerPage : 0,
        pageOptions: [10, 25, 50, 0],
        currentPage: 1,
        totalRows: 0,
        flushCache: false
      }
    },
    mounted () {
      this.loadStoredSettings()
    },
    computed: {
      langRefreshTooltip () {
        return this.$gettext('Refresh rows')
      },
      langPerPageTooltip () {
        return this.$gettext('Rows per page')
      },
      langSelectFieldsTooltip () {
        return this.$gettext('Select displayed fields')
      },
      visibleFields () {
        let fields = this.fields.slice()

        if (this.selectable) {
          fields.unshift({ key: 'selected', label: '', sortable: false })
        }

        if (!this.selectFields) {
          return fields
        }

        return _.filter(fields, (field) => {
          let isSelectable = _.defaultTo(field.selectable, false)
          if (!isSelectable) {
            return true
          }

          return _.defaultTo(field.visible, true)
        })
      },
      selectableFields () {
        return _.filter(this.fields.slice(), (field) => {
          return _.defaultTo(field.selectable, false)
        })
      },
      showPagination () {
        return this.paginated && this.perPage !== 0
      },
      perPageLabel () {
        return this.getPerPageLabel(this.perPage)
      }
    },
    methods: {
      loadStoredSettings () {
        if (store.enabled && store.get(this.storeKey) !== undefined) {
          let settings = store.get(this.storeKey)

          this.perPage = _.defaultTo(settings.perPage, this.defaultPerPage)

          _.forEach(this.selectableFields, (field) => {
            field.visible = _.includes(settings.visibleFields, field.key)
          })
        }
      },
      storeSettings () {
        if (!store.enabled) {
          return
        }

        let settings = {
          'perPage': this.perPage,
          'visibleFields': _.map(this.visibleFields, 'key')
        }
        store.set(this.storeKey, settings)
      },
      getPerPageLabel (num) {
        return (num === 0) ? 'All' : num.toString()
      },
      setPerPage (num) {
        this.perPage = num
        this.storeSettings()
      },
      onClickRefresh (e) {
        if (e.shiftKey) {
          this.relist()
        } else {
          this.refresh()
        }
      },
      onRefreshed () {
        this.$emit('refreshed')
      },
      refresh () {
        this.$refs.table.refresh()
      },
      navigate () {
        this.filter = null
        this.currentPage = 1
        this.flushCache = true
        this.refresh()
      },
      relist () {
        this.filter = null
        this.flushCache = true
        this.refresh()
      },
      setFilter (newTerm) {
        this.currentPage = 1
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
          queryParams.sort = ctx.sortBy
          queryParams.sortOrder = (ctx.sortDesc) ? 'DESC' : 'ASC'
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
        if (this.perPage === 0) {
          this.allSelected = items.length === this.totalRows
        } else {
          this.allSelected = items.length === this.perPage
        }

        this.selected = items
        this.$emit('row-selected', items)
      },
      toggleSelected () {
        if (this.allSelected) {
          this.$refs.table.clearSelected()
          this.allSelected = false
        } else {
          this.$refs.table.selectAllRows()
          this.allSelected = true
        }
      },
      onFiltered (filter) {
        this.$emit('filtered', filter)
      }
    }
  }
</script>
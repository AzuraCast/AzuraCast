<template>
    <section class="card" role="region">
        <b-card-header header-bg-variant="primary-dark">
            <h2 class="card-title">
                <translate key="lang_hdr">API Keys</translate>
            </h2>
        </b-card-header>

        <data-table ref="datatable" id="api_keys" :fields="fields" :api-url="apiUrl">
            <template #cell(owner)="row">
                {{ row.item.user.email }}
            </template>
            <template #cell(actions)="row">
                <b-button-group size="sm">
                    <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                        <translate key="lang_btn_delete">Delete</translate>
                    </b-button>
                </b-button-group>
            </template>
        </data-table>
    </section>
</template>

<script>
import DataTable from "~/components/Common/DataTable";

export default {
    name: 'AdminApiKeys',
    components: {DataTable},
    props: {
        apiUrl: String
    },
    data() {
        return {
            fields: [
                {
                    key: 'comment',
                    isRowHeader: true,
                    label: this.$gettext('API Key Description/Comments'),
                    sortable: false
                },
                {
                    key: 'owner',
                    label: this.$gettext('Owner'),
                    sortable: false
                },
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        };
    },
    methods: {
        relist() {
            this.$refs.datatable.relist();
        },
        doDelete(url) {
            this.$confirmDelete({
                title: this.$gettext('Delete API Key?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(url)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        }
    }
}
</script>

<template>
    <div>
        <h2 class="outside-card-header mb-1">
            <translate key="hdr">My Account</translate>
        </h2>

        <b-row class="mb-3">
            <b-card no-body>
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        <translate key="lang_hdr_profile">My Profile</translate>
                    </h2>
                </b-card-header>

                <b-card-body body-class="card-padding-sm">
                </b-card-body>

                <div class="card-actions">
                    <a class="btn btn-outline-primary">
                        <icon icon="edit"></icon>
                        <translate key="lang_btn_edit_profile">Edit Profile</translate>
                    </a>
                </div>
            </b-card>

            <b-card no-body>
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        <translate key="lang_hdr_security">Security</translate>
                    </h2>
                </b-card-header>

                <b-card-body body-class="card-padding-sm">
                </b-card-body>

                <div class="card-actions">
                    <a class="btn btn-outline-primary">
                        <icon icon="vpn_key"></icon>
                        <translate key="lang_btn_change_password">Change Password</translate>
                    </a>
                    <a class="btn btn-outline-primary">
                        <icon icon="vpn_key"></icon>
                        <translate key="lang_btn_change_password">Enable Two-Factor</translate>
                    </a>
                </div>
            </b-card>
        </b-row>

        <b-row>
            <b-card no-body>
                <b-card-header header-bg-variant="primary-dark">
                    <h2 class="card-title">
                        <translate key="lang_hdr_api_keys">API Keys</translate>
                    </h2>
                </b-card-header>

                <b-card-body body-class="card-padding-sm">
                    <b-button variant="outline-primary" @click.prevent="doCreate">
                        <icon icon="add"></icon>
                        <translate key="lang_add_btn">Add API Key</translate>
                    </b-button>
                </b-card-body>

                <data-table ref="datatable" id="account_api_keys" :show-toolbar="false" :fields="fields"
                            :api-url="apiKeysApiUrl">
                    <template #cell(actions)="row">
                        <b-button-group size="sm">
                            <b-button size="sm" variant="primary" @click.prevent="doEdit(row.item.links.self)">
                                <translate key="lang_btn_edit">Edit</translate>
                            </b-button>
                            <b-button size="sm" variant="danger" @click.prevent="doDelete(row.item.links.self)">
                                <translate key="lang_btn_delete">Delete</translate>
                            </b-button>
                        </b-button-group>
                    </template>
                </data-table>
            </b-card>
        </b-row>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";

export default {
    name: 'Account',
    components: {Icon, DataTable},
    props: {
        meUrl: String,
        passwordUrl: String,
        twoFactorUrl: String,
        apiKeysApiUrl: String
    }
}
</script>

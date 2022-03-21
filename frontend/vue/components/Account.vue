<template>
    <div>
        <h2 class="outside-card-header mb-1">
            <translate key="hdr">My Account</translate>
        </h2>

        <b-row>
            <b-col sm="12" md="6" lg="5">
                <section class="card mb-3" role="region">
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">
                            <translate key="lang_hdr_profile">Profile</translate>
                        </h2>
                    </b-card-header>

                    <b-overlay variant="card" :show="userLoading">
                        <b-card-body body-class="card-padding-sm">
                            <b-media right-align vertical-align="center">
                                <template v-if="user.avatar.url" #aside>
                                    <avatar :url="user.avatar.url" :service="user.avatar.service"
                                            :service-url="user.avatar.serviceUrl"></avatar>
                                </template>

                                <h2 v-if="user.name" class="card-title">{{ user.name }}</h2>
                                <h2 v-else class="card-title">
                                    <translate key="lang_no_username">AzuraCast User</translate>
                                </h2>
                                <h3 class="card-subtitle">{{ user.email }}</h3>

                                <div v-if="user.roles.length > 0" class="mt-2">
                                <span v-for="role in user.roles" :key="role.id"
                                      class="badge badge-secondary mr-2">{{ role.name }}</span>
                                </div>
                            </b-media>
                        </b-card-body>
                    </b-overlay>

                    <div class="card-actions">
                        <b-button variant="outline-primary" @click.prevent="doEditProfile">
                            <icon icon="edit"></icon>
                            <translate key="lang_btn_edit_profile">Edit Profile</translate>
                        </b-button>
                    </div>
                </section>

                <section class="card" role="region">
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">
                            <translate key="lang_hdr_security">Security</translate>
                        </h2>
                    </b-card-header>

                    <b-overlay variant="card" :show="securityLoading">
                        <b-card-body>
                            <h5>
                                <translate key="lang_two_factor">Two-Factor Authentication</translate>
                                <span v-if="security.twoFactorEnabled" class="badge badge-success">
                                <translate key="lang_enabled">Enabled</translate>
                            </span>
                                <span v-else class="badge badge-danger">
                                <translate key="lang_disabled">Disabled</translate>
                            </span>
                            </h5>

                            <p class="card-text mt-2">
                                <translate key="lang_two_factor_info">Two-factor authentication improves the security of your account by requiring a second one-time access code in addition to your password when you log in.</translate>
                            </p>
                        </b-card-body>
                    </b-overlay>

                    <div class="card-actions">
                        <b-button variant="outline-primary" @click.prevent="doChangePassword">
                            <icon icon="vpn_key"></icon>
                            <translate key="lang_btn_change_password">Change Password</translate>
                        </b-button>
                        <b-button v-if="security.twoFactorEnabled" variant="outline-danger"
                                  @click.prevent="disableTwoFactor">
                            <icon icon="lock_open"></icon>
                            <translate key="lang_btn_disable_two_factor">Disable Two-Factor</translate>
                        </b-button>
                        <b-button v-else variant="outline-success" @click.prevent="enableTwoFactor">
                            <icon icon="lock"></icon>
                            <translate key="lang_btn_enable_two_factor">Enable Two-Factor</translate>
                        </b-button>
                    </div>
                </section>
            </b-col>
            <b-col sm="12" md="6" lg="7">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">
                            <translate key="lang_hdr_api_keys">API Keys</translate>
                        </h2>
                    </b-card-header>

                    <info-card>
                        <translate key="lang_api_keys_info">Use API keys to authenticate with the AzuraCast API using the same permissions as your user account.</translate>
                        <a href="/api" target="_blank">
                            <translate key="lang_api_keys_docs_link">API Documentation</translate>
                        </a>
                    </info-card>

                    <b-card-body body-class="card-padding-sm">
                        <b-button variant="outline-primary" @click.prevent="createApiKey">
                            <icon icon="add"></icon>
                            <translate key="lang_add_btn">Add API Key</translate>
                        </b-button>
                    </b-card-body>

                    <data-table ref="datatable" id="account_api_keys" :show-toolbar="false" :fields="apiKeyFields"
                                :api-url="apiKeysApiUrl">
                        <template #cell(actions)="row">
                            <b-button-group size="sm">
                                <b-button size="sm" variant="danger" @click.prevent="deleteApiKey(row.item.links.self)">
                                    <translate key="lang_btn_delete">Delete</translate>
                                </b-button>
                            </b-button-group>
                        </template>
                    </data-table>
                </b-card>
            </b-col>
        </b-row>

        <account-edit-modal ref="editModal" :user-url="userUrl" :supported-locales="supportedLocales"
                            @relist="relist"></account-edit-modal>

        <account-change-password-modal ref="changePasswordModal" :change-password-url="changePasswordUrl"
                                       @relist="relist"></account-change-password-modal>

        <account-two-factor-modal ref="twoFactorModal" :two-factor-url="twoFactorUrl"
                                  @relist="relist"></account-two-factor-modal>

        <account-api-key-modal ref="apiKeyModal" :create-url="apiKeysApiUrl" @relist="relist"></account-api-key-modal>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";
import AccountChangePasswordModal from "./Account/ChangePasswordModal";
import AccountApiKeyModal from "./Account/ApiKeyModal";
import AccountTwoFactorModal from "./Account/TwoFactorModal";
import AccountEditModal from "./Account/EditModal";
import Avatar from "~/components/Common/Avatar";
import InfoCard from "~/components/Common/InfoCard";

export default {
    name: 'Account',
    components: {
        AccountEditModal,
        AccountTwoFactorModal,
        AccountApiKeyModal,
        AccountChangePasswordModal,
        Icon,
        InfoCard,
        DataTable,
        Avatar
    },
    props: {
        userUrl: String,
        changePasswordUrl: String,
        twoFactorUrl: String,
        apiKeysApiUrl: String,
        supportedLocales: Object
    },
    data() {
        return {
            userLoading: true,
            user: {
                name: null,
                email: null,
                avatar: {
                    url: null,
                    service: null,
                    serviceUrl: null
                },
                roles: [],
            },
            securityLoading: true,
            security: {
                twoFactorEnabled: false,
            },
            apiKeyFields: [
                {
                    key: 'comment',
                    isRowHeader: true,
                    label: this.$gettext('API Key Description/Comments'),
                    sortable: false
                },
                {key: 'actions', label: this.$gettext('Actions'), sortable: false, class: 'shrink'}
            ]
        }
    },
    mounted() {
        this.relist();
    },
    methods: {
        relist() {
            this.userLoading = true;
            this.$wrapWithLoading(
                this.axios.get(this.userUrl)
            ).then((resp) => {
                this.user = {
                    name: resp.data.name,
                    email: resp.data.email,
                    roles: resp.data.roles,
                    avatar: {
                        url: resp.data.avatar.url_64,
                        service: resp.data.avatar.service_name,
                        serviceUrl: resp.data.avatar.service_url
                    }
                };
                this.userLoading = false;
            });

            this.securityLoading = true;
            this.$wrapWithLoading(
                this.axios.get(this.twoFactorUrl)
            ).then((resp) => {
                this.security.twoFactorEnabled = resp.data.two_factor_enabled;
                this.securityLoading = false;
            });

            this.$refs.datatable.relist();
        },
        doEditProfile() {
            this.$refs.editModal.open();
        },
        doChangePassword() {
            this.$refs.changePasswordModal.open();
        },
        enableTwoFactor() {
            this.$refs.twoFactorModal.open();
        },
        disableTwoFactor() {
            this.$confirmDelete({
                title: this.$gettext('Disable two-factor authentication?'),
            }).then((result) => {
                if (result.value) {
                    this.$wrapWithLoading(
                        this.axios.delete(this.twoFactorUrl)
                    ).then((resp) => {
                        this.$notifySuccess(resp.data.message);
                        this.relist();
                    });
                }
            });
        },
        createApiKey() {
            this.$refs.apiKeyModal.create();
        },
        deleteApiKey(url) {
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

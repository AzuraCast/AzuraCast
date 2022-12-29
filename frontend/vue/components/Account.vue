<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('My Account') }}
        </h2>

        <b-row>
            <b-col sm="12" md="6" lg="5">
                <section class="card mb-3" role="region">
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">{{ $gettext('Profile') }}</h2>
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
                                    {{ $gettext('AzuraCast User') }}
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
                            {{ $gettext('Edit Profile') }}
                        </b-button>
                    </div>
                </section>

                <section class="card" role="region">
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">{{ $gettext('Security') }}</h2>
                    </b-card-header>

                    <b-overlay variant="card" :show="securityLoading">
                        <b-card-body>
                            <h5>
                                {{ $gettext('Two-Factor Authentication') }}
                                <enabled-badge :enabled="security.twoFactorEnabled"></enabled-badge>
                            </h5>

                            <p class="card-text mt-2">
                                {{
                                    $gettext('Two-factor authentication improves the security of your account by requiring a second one-time access code in addition to your password when you log in.')
                                }}
                            </p>
                        </b-card-body>
                    </b-overlay>

                    <div class="card-actions">
                        <b-button variant="outline-primary" @click.prevent="doChangePassword">
                            <icon icon="vpn_key"></icon>
                            {{ $gettext('Change Password') }}
                        </b-button>
                        <b-button v-if="security.twoFactorEnabled" variant="outline-danger"
                                  @click.prevent="disableTwoFactor">
                            <icon icon="lock_open"></icon>
                            {{ $gettext('Disable Two-Factor') }}
                        </b-button>
                        <b-button v-else variant="outline-success" @click.prevent="enableTwoFactor">
                            <icon icon="lock"></icon>
                            {{ $gettext('Enable Two-Factor') }}
                        </b-button>
                    </div>
                </section>
            </b-col>
            <b-col sm="12" md="6" lg="7">
                <b-card no-body>
                    <b-card-header header-bg-variant="primary-dark">
                        <h2 class="card-title">{{ $gettext('API Keys') }}</h2>
                    </b-card-header>

                    <info-card>
                        {{
                            $gettext('Use API keys to authenticate with the AzuraCast API using the same permissions as your user account.')
                        }}
                        <a href="/api" target="_blank">
                            {{ $gettext('API Documentation') }}
                        </a>
                    </info-card>

                    <b-card-body body-class="card-padding-sm">
                        <b-button variant="outline-primary" @click.prevent="createApiKey">
                            <icon icon="add"></icon>
                            {{ $gettext('Add API Key') }}
                        </b-button>
                    </b-card-body>

                    <data-table ref="$dataTable" id="account_api_keys" :show-toolbar="false" :fields="apiKeyFields"
                                :api-url="apiKeysApiUrl">
                        <template #cell(actions)="row">
                            <b-button-group size="sm">
                                <b-button size="sm" variant="danger" @click.prevent="deleteApiKey(row.item.links.self)">
                                    {{ $gettext('Delete') }}
                                </b-button>
                            </b-button-group>
                        </template>
                    </data-table>
                </b-card>
            </b-col>
        </b-row>

        <account-edit-modal ref="$editModal" :user-url="userUrl" :supported-locales="supportedLocales"
                            @reload="reload"></account-edit-modal>

        <account-change-password-modal ref="$changePasswordModal" :change-password-url="changePasswordUrl"
                                       @relist="relist"></account-change-password-modal>

        <account-two-factor-modal ref="$twoFactorModal" :two-factor-url="twoFactorUrl"
                                  @relist="relist"></account-two-factor-modal>

        <account-api-key-modal ref="$apiKeyModal" :create-url="apiKeysApiUrl" @relist="relist"></account-api-key-modal>
    </div>
</template>

<script setup>
import Icon from "~/components/Common/Icon";
import DataTable from "~/components/Common/DataTable";
import AccountChangePasswordModal from "./Account/ChangePasswordModal";
import AccountApiKeyModal from "./Account/ApiKeyModal";
import AccountTwoFactorModal from "./Account/TwoFactorModal";
import AccountEditModal from "./Account/EditModal";
import Avatar from "~/components/Common/Avatar";
import InfoCard from "~/components/Common/InfoCard";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {onMounted, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    userUrl: String,
    changePasswordUrl: String,
    twoFactorUrl: String,
    apiKeysApiUrl: String,
    supportedLocales: Object
});

const userLoading = ref(true);
const user = ref({
    name: null,
    email: null,
    avatar: {
        url: null,
        service: null,
        serviceUrl: null
    },
    roles: [],
});

const securityLoading = ref(true);
const security = ref({
    twoFactorEnabled: false,
});

const {$gettext} = useTranslate();

const apiKeyFields = [
    {
        key: 'comment',
        isRowHeader: true,
        label: $gettext('API Key Description/Comments'),
        sortable: false
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $dataTable = ref(); // DataTable
const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const relist = () => {
    userLoading.value = true;

    wrapWithLoading(
        axios.get(props.userUrl)
    ).then((resp) => {
        user.value = {
            name: resp.data.name,
            email: resp.data.email,
            roles: resp.data.roles,
            avatar: {
                url: resp.data.avatar.url_64,
                service: resp.data.avatar.service_name,
                serviceUrl: resp.data.avatar.service_url
            }
        };
        userLoading.value = false;
    });

    securityLoading.value = true;

    wrapWithLoading(
        axios.get(props.twoFactorUrl)
    ).then((resp) => {
        security.value.twoFactorEnabled = resp.data.two_factor_enabled;
        securityLoading.value = false;
    });

    $dataTable.value?.relist();
};

onMounted(relist);

const reload = () => {
    location.reload();
};

const $editModal = ref(); // EditModal

const doEditProfile = () => {
    $editModal.value?.open();
};

const $changePasswordModal = ref(); // ChangePasswordModal

const doChangePassword = () => {
    $changePasswordModal.value?.open();
};

const $twoFactorModal = ref(); // TwoFactorModal

const enableTwoFactor = () => {
    $twoFactorModal.value?.open();
};

const {confirmDelete} = useSweetAlert();

const disableTwoFactor = () => {
    confirmDelete({
        title: $gettext('Disable two-factor authentication?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(props.twoFactorUrl)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};

const $apiKeyModal = ref(); // ApiKeyModal

const createApiKey = () => {
    $apiKeyModal.value?.create();
};

const deleteApiKey = (url) => {
    confirmDelete({
        title: $gettext('Delete API Key?'),
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(url)
            ).then((resp) => {
                notifySuccess(resp.data.message);
                relist();
            });
        }
    });
};
</script>

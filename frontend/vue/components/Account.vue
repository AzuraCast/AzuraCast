<template>
    <div>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('My Account') }}
        </h2>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-md-6 col-lg-5">
                <card-page
                    header-id="hdr_profile"
                    :title="$gettext('Profile')"
                >
                    <loading :loading="userLoading">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div
                                    v-if="user.avatar.url_128"
                                    class="flex-shrink-0 pe-2"
                                >
                                    <avatar
                                        :url="user.avatar.url_128"
                                        :service="user.avatar.service_name"
                                        :service-url="user.avatar.service_url"
                                    />
                                </div>
                                <div class="flex-fill">
                                    <h2
                                        v-if="user.name"
                                        class="card-title"
                                    >
                                        {{ user.name }}
                                    </h2>
                                    <h2
                                        v-else
                                        class="card-title"
                                    >
                                        {{ $gettext('AzuraCast User') }}
                                    </h2>
                                    <h3 class="card-subtitle">
                                        {{ user.email }}
                                    </h3>

                                    <div
                                        v-if="user.roles.length > 0"
                                        class="mt-2"
                                    >
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            class="badge text-bg-secondary me-2"
                                        >{{ role.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </loading>

                    <template #footer_actions>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="doEditProfile"
                        >
                            <icon icon="edit" />
                            <span>
                                {{ $gettext('Edit Profile') }}
                            </span>
                        </button>
                    </template>
                </card-page>

                <card-page
                    header-id="hdr_security"
                    :title="$gettext('Security')"
                >
                    <loading :loading="securityLoading">
                        <div class="card-body">
                            <h5>
                                {{ $gettext('Two-Factor Authentication') }}
                                <enabled-badge :enabled="security.twoFactorEnabled" />
                            </h5>

                            <p class="card-text mt-2">
                                {{
                                    $gettext('Two-factor authentication improves the security of your account by requiring a second one-time access code in addition to your password when you log in.')
                                }}
                            </p>
                        </div>
                    </loading>

                    <template #footer_actions>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="doChangePassword"
                        >
                            <icon icon="vpn_key" />
                            <span>
                                {{ $gettext('Change Password') }}
                            </span>
                        </button>
                        <button
                            v-if="security.twoFactorEnabled"
                            type="button"
                            class="btn btn-danger"
                            @click="disableTwoFactor"
                        >
                            <icon icon="lock_open" />
                            <span>
                                {{ $gettext('Disable Two-Factor') }}
                            </span>
                        </button>
                        <button
                            v-else
                            type="button"
                            class="btn btn-success"
                            @click="enableTwoFactor"
                        >
                            <icon icon="lock" />
                            <span>
                                {{ $gettext('Enable Two-Factor') }}
                            </span>
                        </button>
                    </template>
                </card-page>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-7">
                <card-page
                    header-id="hdr_api_keys"
                    :title="$gettext('API Keys')"
                >
                    <template #info>
                        {{
                            $gettext('Use API keys to authenticate with the AzuraCast API using the same permissions as your user account.')
                        }}

                        <a
                            href="/api"
                            target="_blank"
                        >
                            {{ $gettext('API Documentation') }}
                        </a>
                    </template>
                    <template #actions>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="createApiKey"
                        >
                            <icon icon="add" />
                            <span>
                                {{ $gettext('Add API Key') }}
                            </span>
                        </button>
                    </template>

                    <data-table
                        id="account_api_keys"
                        ref="$dataTable"
                        :show-toolbar="false"
                        :fields="apiKeyFields"
                        :api-url="apiKeysApiUrl"
                    >
                        <template #cell(actions)="row">
                            <div class="btn-group btn-group-sm">
                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    @click="deleteApiKey(row.item.links.self)"
                                >
                                    {{ $gettext('Delete') }}
                                </button>
                            </div>
                        </template>
                    </data-table>
                </card-page>
            </div>
        </div>

        <account-edit-modal
            ref="$editModal"
            :user-url="userUrl"
            :supported-locales="supportedLocales"
            @reload="reload"
        />

        <account-change-password-modal
            ref="$changePasswordModal"
            :change-password-url="changePasswordUrl"
            @relist="relist"
        />

        <account-two-factor-modal
            ref="$twoFactorModal"
            :two-factor-url="twoFactorUrl"
            @relist="relist"
        />

        <account-api-key-modal
            ref="$apiKeyModal"
            :create-url="apiKeysApiUrl"
            @relist="relist"
        />
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
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useAxios} from "~/vendor/axios";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState";
import CardPage from "~/components/Common/CardPage.vue";
import Loading from "~/components/Common/Loading.vue";

const props = defineProps({
    userUrl: {
        type: String,
        required: true,
    },
    changePasswordUrl: {
        type: String,
        required: true
    },
    twoFactorUrl: {
        type: String,
        required: true
    },
    apiKeysApiUrl: {
        type: String,
        required: true
    },
    supportedLocales: {
        type: Object,
        default: () => {
            return {};
        }
    }
});

const {axios} = useAxios();

const {state: user, isLoading: userLoading, execute: reloadUser} = useRefreshableAsyncState(
    () => axios.get(props.userUrl).then((r) => r.data),
    {
        name: null,
        email: null,
        avatar: {
            url_128: null,
            service_name: null,
            service_url: null
        },
        roles: [],
    },
);

const {state: security, isLoading: securityLoading, execute: reloadSecurity} = useRefreshableAsyncState(
    () => axios.get(props.twoFactorUrl).then((r) => {
        return {
            twoFactorEnabled: r.data.two_factor_enabled
        };
    }),
    {
        twoFactorEnabled: false,
    },
);

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

const relist = () => {
    reloadUser();
    reloadSecurity();
    $dataTable.value?.relist();
};

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

const {doDelete: doDisableTwoFactor} = useConfirmAndDelete(
    $gettext('Disable two-factor authentication?'),
    relist
);
const disableTwoFactor = () => doDisableTwoFactor(props.twoFactorUrl);

const $apiKeyModal = ref(); // ApiKeyModal

const createApiKey = () => {
    $apiKeyModal.value?.create();
};

const {doDelete: deleteApiKey} = useConfirmAndDelete(
    $gettext('Delete API Key?'),
    relist
);
</script>

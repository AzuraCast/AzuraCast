<template>
    <card-page header-id="hdr_security">
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <div class="flex-fill">
                    <h3
                        :id="id"
                        class="card-title"
                    >
                        {{ $gettext('Security') }}
                    </h3>
                </div>
                <div class="flex-shrink-0">
                    <button
                        type="button"
                        class="btn btn-dark"
                        @click="doChangePassword"
                    >
                        <icon :icon="IconVpnKey" />
                        <span>
                            {{ $gettext('Change Password') }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

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

                <div class="buttons">
                    <button
                        v-if="security.twoFactorEnabled"
                        type="button"
                        class="btn btn-danger"
                        @click="disableTwoFactor"
                    >
                        <icon :icon="IconLockOpen" />
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
                        <icon :icon="IconLock" />
                        <span>
                            {{ $gettext('Enable Two-Factor') }}
                        </span>
                    </button>
                </div>
            </div>
        </loading>

        <div class="card-body">
            <h5>
                {{ $gettext('Passkey Authentication') }}
            </h5>

            <p class="card-text mt-2">
                {{
                    $gettext('Using a passkey (like Windows Hello, YubiKey, or your smartphone) allows you to securely log in without needing to enter your password or two-factor code.')
                }}
            </p>

            <div class="buttons">
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="doAddPasskey"
                >
                    <icon :icon="IconAdd" />
                    <span>
                        {{ $gettext('Add New Passkey') }}
                    </span>
                </button>
            </div>
        </div>

        <data-table
            id="account_passkeys"
            ref="$dataTable"
            :show-toolbar="false"
            :fields="passkeyFields"
            :api-url="passkeysApiUrl"
        >
            <template #cell(actions)="row">
                <div class="btn-group btn-group-sm">
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="deletePasskey(row.item.links.self)"
                    >
                        {{ $gettext('Delete') }}
                    </button>
                </div>
            </template>
        </data-table>
    </card-page>

    <account-change-password-modal ref="$changePasswordModal" />

    <account-two-factor-modal
        ref="$twoFactorModal"
        :two-factor-url="twoFactorUrl"
        @relist="reloadSecurity"
    />

    <passkey-modal
        ref="$passkeyModal"
        @relist="reloadPasskeys"
    />
</template>

<script setup lang="ts">

import {IconAdd, IconLock, IconLockOpen, IconVpnKey} from "~/components/Common/icons.ts";
import CardPage from "~/components/Common/CardPage.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import Icon from "~/components/Common/Icon.vue";
import Loading from "~/components/Common/Loading.vue";
import AccountTwoFactorModal from "~/components/Account/TwoFactorModal.vue";
import AccountChangePasswordModal from "~/components/Account/ChangePasswordModal.vue";
import {useAxios} from "~/vendor/axios.ts";
import {getApiUrl} from "~/router.ts";
import useRefreshableAsyncState from "~/functions/useRefreshableAsyncState.ts";
import {ref} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import useHasDatatable, {DataTableTemplateRef} from "~/functions/useHasDatatable.ts";
import PasskeyModal from "~/components/Account/PasskeyModal.vue";

const {axios} = useAxios();

const twoFactorUrl = getApiUrl('/frontend/account/two-factor');

const {state: security, isLoading: securityLoading, execute: reloadSecurity} = useRefreshableAsyncState(
    () => axios.get(twoFactorUrl.value).then((r) => {
        return {
            twoFactorEnabled: r.data.two_factor_enabled
        };
    }),
    {
        twoFactorEnabled: false,
    },
);

const $changePasswordModal = ref<InstanceType<typeof AccountChangePasswordModal> | null>(null);

const doChangePassword = () => {
    $changePasswordModal.value?.open();
};

const $twoFactorModal = ref<InstanceType<typeof AccountTwoFactorModal> | null>(null);

const enableTwoFactor = () => {
    $twoFactorModal.value?.open();
};

const {$gettext} = useTranslate();

const {doDelete: doDisableTwoFactor} = useConfirmAndDelete(
    $gettext('Disable two-factor authentication?'),
    reloadSecurity
);
const disableTwoFactor = () => doDisableTwoFactor(twoFactorUrl.value);

const passkeysApiUrl = getApiUrl('/frontend/account/passkeys');

const passkeyFields: DataTableField[] = [
    {
        key: 'name',
        isRowHeader: true,
        label: $gettext('Passkey Nickname'),
        sortable: false
    },
    {
        key: 'actions',
        label: $gettext('Actions'),
        sortable: false,
        class: 'shrink'
    }
];

const $dataTable = ref<DataTableTemplateRef>(null);
const {relist: reloadPasskeys} = useHasDatatable($dataTable);

const {doDelete: deletePasskey} = useConfirmAndDelete(
    $gettext('Delete Passkey?'),
    reloadPasskeys
);

const $passkeyModal = ref<InstanceType<typeof PasskeyModal> | null>(null);

const doAddPasskey = () => {
    $passkeyModal.value?.create();
};

</script>

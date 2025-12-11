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
                        <icon-ic-vpn-key/>

                        <span>
                            {{ $gettext('Change Password') }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <loading :loading="securityLoading">
            <div class="card-body" v-if="security">
                <h5>
                    {{ $gettext('Two-Factor Authentication') }}
                    <enabled-badge :enabled="security.two_factor_enabled"/>
                </h5>

                <p class="card-text mt-2">
                    {{
                        $gettext('Two-factor authentication improves the security of your account by requiring a second one-time access code in addition to your password when you log in.')
                    }}
                </p>

                <div class="buttons">
                    <button
                        v-if="security.two_factor_enabled"
                        type="button"
                        class="btn btn-danger"
                        @click="disableTwoFactor"
                    >
                        <icon-ic-lock-open/>

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
                        <icon-ic-lock/>
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
                    <icon-ic-add/>

                    <span>
                        {{ $gettext('Add New Passkey') }}
                    </span>
                </button>
            </div>
        </div>

        <data-table
            id="account_passkeys"
            :show-toolbar="false"
            :fields="passkeyFields"
            :provider="passkeysItemProvider"
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
        @relist="() => reloadSecurity()"
    />

    <passkey-modal
        ref="$passkeyModal"
        @relist="() => reloadPasskeys()"
    />
</template>

<script setup lang="ts">
import CardPage from "~/components/Common/CardPage.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import Loading from "~/components/Common/Loading.vue";
import AccountTwoFactorModal from "~/components/Account/TwoFactorModal.vue";
import AccountChangePasswordModal from "~/components/Account/ChangePasswordModal.vue";
import {useAxios} from "~/vendor/axios.ts";
import {useTemplateRef} from "vue";
import useConfirmAndDelete from "~/functions/useConfirmAndDelete.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import DataTable, {DataTableField} from "~/components/Common/DataTable.vue";
import PasskeyModal from "~/components/Account/PasskeyModal.vue";
import {ApiAccountTwoFactorStatus} from "~/entities/ApiInterfaces.ts";
import {useApiItemProvider} from "~/functions/dataTable/useApiItemProvider.ts";
import {QueryKeys} from "~/entities/Queries.ts";
import {useQuery} from "@tanstack/vue-query";
import IconIcAdd from "~icons/ic/baseline-add";
import IconIcLock from "~icons/ic/baseline-lock";
import IconIcLockOpen from "~icons/ic/baseline-lock-open";
import IconIcVpnKey from "~icons/ic/baseline-vpn-key";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {axios} = useAxios();

const {getApiUrl} = useApiRouter();
const twoFactorUrl = getApiUrl('/frontend/account/two-factor');

const {
    data: security,
    isLoading: securityLoading,
    refetch
} = useQuery<ApiAccountTwoFactorStatus>({
    queryKey: [QueryKeys.AccountIndex, 'two-factor'],
    queryFn: async ({signal}) => {
        const {data} = await axios.get<ApiAccountTwoFactorStatus>(twoFactorUrl.value, {signal});
        return data;
    },
    placeholderData: () => ({
        two_factor_enabled: false,
    }),
});

const reloadSecurity = () => {
    void refetch();
}

const $changePasswordModal = useTemplateRef('$changePasswordModal');

const doChangePassword = () => {
    $changePasswordModal.value?.open();
};

const $twoFactorModal = useTemplateRef('$twoFactorModal');

const enableTwoFactor = () => {
    $twoFactorModal.value?.open();
};

const {$gettext} = useTranslate();

const {doDelete: doDisableTwoFactor} = useConfirmAndDelete(
    $gettext('Disable two-factor authentication?'),
    () => {
        void reloadSecurity();
    }
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

const passkeysItemProvider = useApiItemProvider(
    passkeysApiUrl,
    [QueryKeys.AccountPasskeys]
);

const reloadPasskeys = () => {
    void passkeysItemProvider.refresh();
};

const {doDelete: deletePasskey} = useConfirmAndDelete(
    $gettext('Delete Passkey?'),
    () => reloadPasskeys()
);

const $passkeyModal = useTemplateRef('$passkeyModal');

const doAddPasskey = () => {
    $passkeyModal.value?.create();
};

</script>

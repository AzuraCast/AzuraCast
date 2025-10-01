<template>
    <dashboard-no-sidebar>
        <h2 class="outside-card-header mb-1">
            {{ $gettext('My Account') }}
        </h2>

        <section
            class="card mb-4"
            role="region"
            :aria-label="$gettext('Account Details')"
        >
            <user-info-panel>
                <button
                    type="button"
                    class="btn btn-dark"
                    @click="doEditProfile"
                >
                    <icon-ic-edit/>
                    <span>
                        {{ $gettext('Edit Profile') }}
                    </span>
                </button>
            </user-info-panel>
        </section>

        <div class="row row-of-cards">
            <div class="col-sm-12 col-md-6">
                <security-panel/>
            </div>
            <div class="col-sm-12 col-md-6">
                <api-keys-panel/>
            </div>
        </div>

        <account-edit-modal
            ref="$editModal"
            :supported-locales="supportedLocales"
            @reload="onProfileEdited"
        />
    </dashboard-no-sidebar>
</template>

<script setup lang="ts">
import {useTemplateRef} from "vue";
import AccountEditModal from "~/components/Account/EditModal.vue";
import UserInfoPanel from "~/components/Account/UserInfoPanel.vue";
import SecurityPanel from "~/components/Account/SecurityPanel.vue";
import ApiKeysPanel from "~/components/Account/ApiKeysPanel.vue";
import DashboardNoSidebar from "~/components/Layout/DashboardNoSidebar.vue";
import {useAzuraCastDashboardGlobals} from "~/vendor/azuracast.ts";
import IconIcEdit from "~icons/ic/baseline-edit";

const {supportedLocales} = useAzuraCastDashboardGlobals();

const $editModal = useTemplateRef('$editModal');

const doEditProfile = () => {
    $editModal.value?.open();
};

const onProfileEdited = () => {
    location.reload();
};
</script>

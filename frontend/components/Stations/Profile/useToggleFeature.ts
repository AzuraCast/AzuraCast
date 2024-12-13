import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {getStationApiUrl} from "~/router.ts";
import {useRouter} from "vue-router";
import {set} from "lodash";
import {useNotify} from "~/functions/useNotify";
import {useDialog} from "~/functions/useDialog.ts";

export default function useToggleFeature(feature, newValue) {
    const {axios} = useAxios();
    const {showAlert} = useDialog();
    const {notifySuccess} = useNotify();
    const {$gettext} = useTranslate();
    const router = useRouter();

    const profileEditUrl = getStationApiUrl('/profile/edit');

    return () => {
        showAlert({
            title: (newValue)
                ? $gettext('Enable feature?')
                : $gettext('Disable feature?'),
            confirmButtonText: (newValue)
                ? $gettext('Enable')
                : $gettext('Disable'),
            confirmButtonClass: (newValue)
                ? 'btn-success'
                : 'btn-danger'
        }).then((result) => {
            if (result.value) {
                const remoteData = {};
                set(remoteData, feature, newValue);

                axios.put(
                    profileEditUrl.value,
                    remoteData
                ).then((resp) => {
                    notifySuccess(resp.data.message);
                    router.go(0);
                });
            }
        });
    };
}

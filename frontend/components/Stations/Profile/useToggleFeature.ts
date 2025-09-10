import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {getStationApiUrl} from "~/router.ts";
import {useRouter} from "vue-router";
import {set} from "es-toolkit/compat";
import {useNotify} from "~/functions/useNotify";
import {useDialog} from "~/functions/useDialog.ts";
import {Ref} from "vue";
import {ApiStatus} from "~/entities/ApiInterfaces.ts";

export default function useToggleFeature(
    feature: string,
    currentValue: Ref<boolean>
) {
    const {axios} = useAxios();
    const {showAlert} = useDialog();
    const {notifySuccess} = useNotify();
    const {$gettext} = useTranslate();
    const router = useRouter();

    const profileEditUrl = getStationApiUrl('/profile/edit');

    return async () => {
        const newValue: boolean = !currentValue.value;

        const {value} = await showAlert({
            title: (newValue)
                ? $gettext('Enable feature?')
                : $gettext('Disable feature?'),
            confirmButtonText: (newValue)
                ? $gettext('Enable')
                : $gettext('Disable'),
            confirmButtonClass: (newValue)
                ? 'btn-success'
                : 'btn-danger'
        });

        if (!value) {
            return;
        }

        const remoteData = {};
        set(remoteData, feature, newValue);

        const {data} = await axios.put<ApiStatus>(
            profileEditUrl.value,
            remoteData
        );

        notifySuccess(data.message);
        router.go(0);
    };
}

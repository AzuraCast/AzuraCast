import { set } from "es-toolkit/compat";
import { nextTick, Ref } from "vue";
import { useDialog } from "~/components/Common/Dialogs/useDialog.ts";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import { useClearProfileData } from "~/components/Stations/Profile/useProfileQuery.ts";
import { ApiStatus } from "~/entities/ApiInterfaces.ts";
import { useApiRouter } from "~/functions/useApiRouter.ts";
import { useClearStationGlobalsQuery } from "~/functions/useStationQuery.ts";
import { useAxios } from "~/vendor/axios";
import { useTranslate } from "~/vendor/gettext";

export default function useToggleFeature(
    feature: string,
    currentValue: Ref<boolean>,
) {
    const { axios } = useAxios();
    const { showAlert } = useDialog();
    const { notifySuccess } = useNotify();
    const { $gettext } = useTranslate();
    const { getStationApiUrl } = useApiRouter();

    const profileEditUrl = getStationApiUrl("/profile/edit");

    const clearStationGlobalsQuery = useClearStationGlobalsQuery();
    const clearProfileData = useClearProfileData();

    return async () => {
        const newValue: boolean = !currentValue.value;

        const { value } = await showAlert({
            title: newValue
                ? $gettext("Enable feature?")
                : $gettext("Disable feature?"),
            confirmButtonText: newValue
                ? $gettext("Enable")
                : $gettext("Disable"),
            confirmButtonClass: newValue ? "btn-success" : "btn-danger",
        });

        if (!value) {
            return;
        }

        const remoteData = {};
        set(remoteData, feature, newValue);

        const { data } = await axios.put<ApiStatus>(
            profileEditUrl.value,
            remoteData,
        );

        notifySuccess(data.message);

        await nextTick();

        await Promise.all([clearStationGlobalsQuery(), clearProfileData()]);
    };
}

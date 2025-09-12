import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import {DialogOptions, useDialog} from "~/functions/useDialog.ts";
import {FlashLevels} from "~/entities/ApiInterfaces.ts";
import {MaybeRef, toValue} from "vue";

export default function useMakeApiCall(
    uri: MaybeRef<string>,
    options: Partial<DialogOptions> = {},
) {
    const {axios} = useAxios();
    const {showAlert} = useDialog();
    const {notify} = useNotify();

    return async () => {
        const {value} = await showAlert(options);

        if (!value) {
            return;
        }

        const {data} = await axios.post(toValue(uri));

        notify(data.formatted_message, {
            variant: (data.success) ? FlashLevels.Success : FlashLevels.Warning
        });
    };
}

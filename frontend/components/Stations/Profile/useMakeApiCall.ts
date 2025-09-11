import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import {DialogOptions, useDialog} from "~/functions/useDialog.ts";
import {FlashLevels} from "~/entities/ApiInterfaces.ts";

export default function useMakeApiCall(
    uri: string,
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

        const {data} = await axios.post(uri);

        notify(data.formatted_message, {
            variant: (data.success) ? FlashLevels.Success : FlashLevels.Warning
        });
    };
}

import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import {useDialog} from "~/functions/useDialog.ts";
import {DialogOptions} from "~/components/Common/Dialog.vue";

export default function useMakeApiCall(
    uri: string,
    options: Partial<DialogOptions> = {},
) {
    const {axios} = useAxios();
    const {showAlert} = useDialog();
    const {notify} = useNotify();

    return () => {
        void showAlert(options).then((result) => {
            if (!result.value) {
                return;
            }

            void axios.post(uri).then(({data}) => {
                notify(data.formatted_message, {
                    variant: (data.success) ? 'success' : 'warning'
                });
            });
        });
    };
}

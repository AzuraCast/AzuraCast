import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useDialog} from "~/functions/useDialog.ts";

export default function useConfirmAndDelete(
    confirmMessage: string,
    onSuccess?: (data: any) => void
) {
    const {confirmDelete} = useDialog();
    const {notifySuccess} = useNotify();
    const {axios} = useAxios();

    const doDelete = (deleteUrl: string) => {
        void confirmDelete({
            title: confirmMessage
        }).then((result) => {
            if (result.value) {
                void axios.delete(deleteUrl).then((resp) => {
                    notifySuccess(resp.data.message);

                    if (typeof onSuccess === 'function') {
                        onSuccess(resp.data);
                    }
                });
            }
        });
    };

    return {
        doDelete
    };
}

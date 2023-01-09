import {useSweetAlert} from "~/vendor/sweetalert";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

export default function confirmAndDelete(
    deleteUrl,
    confirmMessage,
    onSuccess = null
) {
    const {confirmDelete} = useSweetAlert();
    const {wrapWithLoading, notifySuccess} = useNotify();
    const {axios} = useAxios();

    confirmDelete({
        title: confirmMessage
    }).then((result) => {
        if (result.value) {
            wrapWithLoading(
                axios.delete(deleteUrl)
            ).then((resp) => {
                notifySuccess(resp.data.message);

                if (typeof onSuccess === 'function') {
                    onSuccess(resp.data);
                }
            });
        }
    });
}

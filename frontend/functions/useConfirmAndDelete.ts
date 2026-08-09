import { useDialog } from "~/components/Common/Dialogs/useDialog.ts";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import { ApiStatus } from "~/entities/ApiInterfaces.ts";
import { useAxios } from "~/vendor/axios";

export default function useConfirmAndDelete<T extends ApiStatus = ApiStatus>(
    confirmMessage: string,
    onSuccess?: (data: T) => void,
) {
    const { confirmDelete } = useDialog();
    const { notifySuccess } = useNotify();
    const { axios } = useAxios();

    const doDelete = async (deleteUrl: string) => {
        const { value } = await confirmDelete({
            title: confirmMessage,
        });

        if (value) {
            const { data } = await axios.delete<T>(deleteUrl);

            notifySuccess(data.message);
            if (typeof onSuccess === "function") {
                onSuccess(data);
            }
        }
    };

    return {
        doDelete,
    };
}

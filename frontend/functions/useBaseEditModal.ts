import {computed, ComputedRef, MaybeRef, nextTick, Ref, ref, ShallowRef, toValue} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import ModalForm from "~/components/Common/ModalForm.vue";
import {AxiosError, AxiosRequestConfig} from "axios";
import {ApiError, ApiGenericForm, ApiStatus} from "~/entities/ApiInterfaces.ts";
import {useMutation, UseMutationOptions, UseMutationReturnType} from "@tanstack/vue-query";

export type ModalFormTemplateRef = InstanceType<typeof ModalForm>;

export type BaseEditModalProps = {
    createUrl: string
}

export type HasRelistEmit = {
    (e: 'relist'): void
}

type Form = ApiGenericForm

type AxiosMutateResponse<T extends Form = Form> = ApiStatus & T

type MutationOptions<
    SubmittedForm extends Form = Form,
    ResponseBody extends Form = SubmittedForm
> = UseMutationOptions<
    AxiosMutateResponse<ResponseBody>,
    AxiosError<ApiError>,
    SubmittedForm
>

type MutationReturn<
    SubmittedForm extends Form = Form,
    ResponseBody extends Form = SubmittedForm
> = UseMutationReturnType<
    AxiosMutateResponse<ResponseBody>,
    AxiosError<ApiError>,
    SubmittedForm,
    unknown
>

export type BaseEditModalEmits = HasRelistEmit;

export type BaseEditModalOptions<SubmittedForm extends Form = Form> = {
    buildSubmitRequest?: (data: SubmittedForm) => AxiosRequestConfig,
    onSubmitSuccess?: (
        data: AxiosMutateResponse,
        variables: SubmittedForm,
        context: unknown
    ) => void,
    onSubmitError?: (
        error: AxiosError<ApiError, any>,
        variables: SubmittedForm,
        context: unknown
    ) => void,
}

export type ValidateReturn<T extends Form = Form> = {
    valid: boolean,
    data?: T
}

export function useBaseEditModal<
    SubmittedForm extends Form = Form,
    ResponseBody extends Form = SubmittedForm
>(
    createUrl: MaybeRef<string | null>,
    emit: BaseEditModalEmits,
    $modal: Readonly<ShallowRef<ModalFormTemplateRef | null>>,
    resetForm: () => void,
    populateForm: (data: Partial<ResponseBody>) => void,
    validateForm: (isEditMode: boolean) => Promise<ValidateReturn<SubmittedForm>>,
    options: BaseEditModalOptions<SubmittedForm> = {},
    mutationOptions: Partial<MutationOptions<SubmittedForm, ResponseBody>> = {},
): {
    loading: Ref<boolean>,
    error: Ref<any>,
    editUrl: Ref<string | null>,
    isEditMode: ComputedRef<boolean>,
    mutation: MutationReturn<SubmittedForm, ResponseBody>,
    clearContents: () => void,
    create: () => void,
    edit: (recordUrl: string) => Promise<void>,
    doSubmit: () => Promise<void>,
    close: () => void
} {
    const fetchLoading = ref<boolean>(false);
    const error = ref<any>(null);
    const editUrl = ref<string | null>(null);

    const isEditMode: ComputedRef<boolean> = computed(() => {
        return editUrl.value !== null;
    });

    const clearContents = (): void => {
        resetForm();

        fetchLoading.value = false;
        error.value = null;
        editUrl.value = null;
    };

    const create = (): void => {
        clearContents();

        $modal.value?.show();

        void nextTick(() => {
            resetForm();
        });
    };

    const {notifySuccess} = useNotify();
    const {axios} = useAxios();

    const doLoad = async (): Promise<void> => {
        fetchLoading.value = true;

        if (!editUrl.value) {
            throw new Error("No edit URL!");
        }

        try {
            const {data} = await axios.get(editUrl.value);
            populateForm(data);
        } catch {
            close();
        } finally {
            fetchLoading.value = false;
        }
    };

    const edit = async (recordUrl: string): Promise<void> => {
        clearContents();

        editUrl.value = recordUrl;
        $modal.value?.show();

        await nextTick();

        resetForm();
        await doLoad();
    };

    const buildSubmitRequest = (data: SubmittedForm): AxiosRequestConfig<SubmittedForm> => {
        if (typeof options.buildSubmitRequest === 'function') {
            return options.buildSubmitRequest(data);
        }

        const url = (isEditMode.value && editUrl.value)
            ? editUrl.value
            : toValue(createUrl);

        if (url === null) {
            throw new Error("No valid URL to submit to!");
        }

        return {
            method: (isEditMode.value)
                ? 'PUT'
                : 'POST',
            url,
            data: data
        };
    };

    const close = (): void => {
        $modal.value?.hide();
    };

    const mutation: MutationReturn<SubmittedForm, ResponseBody> = useMutation({
        mutationFn: async (data: SubmittedForm): Promise<AxiosMutateResponse<ResponseBody>> => {
            const {data: returnData} = await axios<AxiosMutateResponse<ResponseBody>>(
                buildSubmitRequest(data)
            );

            return returnData;
        },
        onSuccess: (
            data,
            variables,
            context
        ): void => {
            if (typeof options.onSubmitSuccess === 'function') {
                options.onSubmitSuccess(data, variables, context);
                return;
            }

            notifySuccess();
            emit('relist');
            close();
        },
        onError: (err, variables, context): void => {
            if (typeof options.onSubmitError === 'function') {
                options.onSubmitError(err, variables, context);
                return;
            }

            error.value = err.response?.data?.message;
        },
        ...mutationOptions
    });

    const doSubmit = async (): Promise<void> => {
        const {valid, data} = await validateForm(isEditMode.value);

        if (!valid || !data) {
            return;
        }

        error.value = null;
        mutation.mutate(data);
    };

    const loading = computed(
        () => fetchLoading.value || mutation.isPending.value
    );

    return {
        loading,
        error,
        editUrl,
        isEditMode,
        mutation,
        clearContents,
        create,
        edit,
        doSubmit,
        close
    };
}

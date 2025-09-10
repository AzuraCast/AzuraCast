import {computed, ComputedRef, nextTick, Ref, ref, ShallowRef, toRef} from "vue";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import ModalForm from "~/components/Common/ModalForm.vue";
import {AxiosError, AxiosRequestConfig} from "axios";
import {ApiError, ApiGenericForm, ApiStatus} from "~/entities/ApiInterfaces.ts";
import {useMutation, UseMutationOptions, UseMutationReturnType} from "@tanstack/vue-query";

export type ModalFormTemplateRef = InstanceType<typeof ModalForm>;

export interface BaseEditModalProps {
    createUrl?: string
}

export interface HasRelistEmit {
    (e: 'relist'): void
}

type Form = ApiGenericForm

type AxiosMutateResponse = ApiStatus & Form

type MutationOptions = UseMutationOptions<
    AxiosMutateResponse,
    AxiosError<ApiError>,
    Form
>

type MutationReturn = UseMutationReturnType<
    AxiosMutateResponse,
    AxiosError<ApiError>,
    Form,
    unknown
>

export type BaseEditModalEmits = HasRelistEmit;

export interface BaseEditModalOptions<T extends Form = Form> {
    clearContents?: (resetForm: () => void) => void,
    populateForm?: (data: Partial<T>, form: Ref<T>) => void,
    getSubmittableFormData?: (form: Ref<T>, isEditMode: ComputedRef<boolean>) => Form,
    buildSubmitRequest?: (data: Form) => AxiosRequestConfig,
    onSubmitSuccess?: (
        data: AxiosMutateResponse,
        variables: Form,
        context: unknown
    ) => void,
    onSubmitError?: (
        error: AxiosError<ApiError, any>,
        variables: Form,
        context: unknown
    ) => void,
}

export function useBaseEditModal<T extends Form = Form>(
    form: Ref<T>,
    props: BaseEditModalProps,
    emit: BaseEditModalEmits,
    $modal: Readonly<ShallowRef<ModalFormTemplateRef | null>>,
    resetForm: () => void,
    validateForm?: () => Promise<boolean>,
    options: BaseEditModalOptions<T> = {},
    mutationOptions: Partial<MutationOptions> = {},
): {
    loading: Ref<boolean>,
    error: Ref<any>,
    editUrl: Ref<string | null>,
    isEditMode: ComputedRef<boolean>,
    mutation: MutationReturn,
    clearContents: () => void,
    create: () => void,
    edit: (recordUrl: string) => Promise<void>,
    doSubmit: () => Promise<void>,
    close: () => void
} {
    const createUrl = toRef(props, 'createUrl');

    const fetchLoading = ref<boolean>(false);
    const error = ref<any>(null);
    const editUrl = ref<string | null>(null);

    const isEditMode: ComputedRef<boolean> = computed(() => {
        return editUrl.value !== null;
    });

    const clearContents = (): void => {
        if (typeof options.clearContents === 'function') {
            options.clearContents(resetForm);
            return;
        }

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

    const populateForm = (data: Partial<T>): void => {
        if (typeof options.populateForm === 'function') {
            options.populateForm(data, form);
            return;
        }

        form.value = mergeExisting(form.value, data);
    }

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

    const getSubmittableFormData = (): ApiGenericForm => {
        if (typeof options.getSubmittableFormData === 'function') {
            return options.getSubmittableFormData(form, isEditMode);
        }

        return form.value;
    };

    const buildSubmitRequest = (data: ApiGenericForm): AxiosRequestConfig => {
        if (typeof options.buildSubmitRequest === 'function') {
            return options.buildSubmitRequest(data);
        }

        return {
            method: (isEditMode.value)
                ? 'PUT'
                : 'POST',
            url: (isEditMode.value && editUrl.value)
                ? editUrl.value
                : createUrl.value,
            data: data
        };
    };

    const close = (): void => {
        $modal.value?.hide();
    };

    const mutation: MutationReturn = useMutation({
        mutationFn: async (data: ApiGenericForm) => (await axios<AxiosMutateResponse>(buildSubmitRequest(data))).data,
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
        if (typeof validateForm === 'function') {
            const valid = await validateForm();

            if (!valid) {
                return;
            }

            error.value = null;
            mutation.mutate(getSubmittableFormData());
        } else {
            error.value = null;
            mutation.mutate(getSubmittableFormData());
        }
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

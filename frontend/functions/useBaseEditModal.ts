import {computed, ComputedRef, nextTick, Ref, ref, toRef} from "vue";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import ModalForm from "~/components/Common/ModalForm.vue";
import {AxiosRequestConfig} from "axios";
import {GlobalConfig, Validation} from "@vuelidate/core";

export type ModalFormTemplateRef = InstanceType<typeof ModalForm> | null;

export interface BaseEditModalProps {
    createUrl: string
}

export interface BaseEditModalEmits {
    (e: 'relist'): void
}

export type Form = Ref<Record<string, any>>

export interface BaseEditModalOptions extends GlobalConfig {
    resetForm?(originalResetForm: () => void): void,

    clearContents?(resetForm: () => void): void,

    populateForm?(data: Record<string, any>, form: Form): void,

    getSubmittableFormData?(form: Form, isEditMode: ComputedRef<boolean>): Record<string, any>,

    buildSubmitRequest?(): AxiosRequestConfig,

    onSubmitSuccess?(): void,

    onSubmitError?(error: any): void,
}

export function useBaseEditModal(
    props: BaseEditModalProps,
    emit: BaseEditModalEmits,
    $modal: Ref<ModalFormTemplateRef>,
    validations = {},
    blankForm = {},
    userOptions: BaseEditModalOptions = {}
): {
    loading: Ref<boolean>,
    error: Ref<any | null>,
    editUrl: Ref<string>,
    isEditMode: ComputedRef<boolean>,
    form: Form,
    v$: Ref<Validation>,
    resetForm(): void,
    clearContents(): void,
    create(): void,
    edit(recordUrl: string): void,
    doSubmit(): void,
    close(): void
} {
    const createUrl = toRef(props, 'createUrl');

    const loading: Ref<boolean> = ref<boolean>(false);
    const error: Ref<any | null> = ref(null);
    const editUrl: Ref<string> = ref<string>(null);

    const isEditMode: ComputedRef<boolean> = computed(() => {
        return editUrl.value !== null;
    });

    const options: BaseEditModalOptions = {
        resetForm: null,
        clearContents: null,
        populateForm: null,
        getSubmittableFormData: null,
        buildSubmitRequest: null,
        onSubmitSuccess: null,
        onSubmitError: null,
        ...userOptions
    };

    const {
        form,
        v$,
        resetForm: originalResetForm
    } = useVuelidateOnForm(
        validations,
        blankForm,
        options
    );

    const resetForm = (): void => {
        if (typeof options.resetForm === 'function') {
            options.resetForm(originalResetForm);
            return;
        }

        originalResetForm();
    };

    const clearContents = (): void => {
        if (typeof options.clearContents === 'function') {
            options.clearContents(resetForm);
            return;
        }

        resetForm();

        loading.value = false;
        error.value = null;
        editUrl.value = null;
    };

    const create = (): void => {
        clearContents();

        $modal.value.show();

        nextTick(() => {
            resetForm();
        });
    };

    const populateForm = (data: Record<string, any>): void => {
        if (typeof options.populateForm === 'function') {
            options.populateForm(data, form);
            return;
        }

        form.value = mergeExisting(form.value, data);
    }

    const {notifySuccess} = useNotify();
    const {axios} = useAxios();

    const doLoad = (): void => {
        loading.value = true;

        axios.get(editUrl.value).then((resp) => {
            populateForm(resp.data);
        }).catch(() => {
            close();
        }).finally(() => {
            loading.value = false;
        });
    };

    const edit = (recordUrl: string): void => {
        clearContents();

        editUrl.value = recordUrl;
        $modal.value.show();

        nextTick(() => {
            resetForm();
            doLoad();
        })
    };

    const getSubmittableFormData = (): Record<string, any> => {
        if (typeof options.getSubmittableFormData === 'function') {
            return options.getSubmittableFormData(form, isEditMode);
        }

        return form.value;
    };

    const buildSubmitRequest = (): AxiosRequestConfig => {
        if (typeof options.buildSubmitRequest === 'function') {
            return options.buildSubmitRequest();
        }

        return {
            method: (isEditMode.value)
                ? 'PUT'
                : 'POST',
            url: (isEditMode.value)
                ? editUrl.value
                : createUrl.value,
            data: getSubmittableFormData()
        };
    };

    const close = (): void => {
        $modal.value.hide();
    };

    const onSubmitSuccess = (): void => {
        if (typeof options.onSubmitSuccess === 'function') {
            options.onSubmitSuccess();
            return;
        }

        notifySuccess();
        emit('relist');
        close();
    };

    const onSubmitError = (err: any): void => {
        if (typeof options.onSubmitError === 'function') {
            options.onSubmitError(err);
            return;
        }

        error.value = err.response.data.message;
    };

    const doSubmit = (): void => {
        v$.value.$touch();
        v$.value.$validate().then((isValid) => {
            if (!isValid) {
                return;
            }

            error.value = null;

            axios(buildSubmitRequest()).then(() => {
                onSubmitSuccess();
            }).catch((err) => {
                onSubmitError(err);
            });
        });
    };

    return {
        loading,
        error,
        editUrl,
        isEditMode,
        form,
        v$,
        resetForm,
        clearContents,
        create,
        edit,
        doSubmit,
        close
    };
}

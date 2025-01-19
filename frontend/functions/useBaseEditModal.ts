import {computed, ComputedRef, nextTick, Ref, ref, toRef} from "vue";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {
    useVuelidateOnForm,
    VuelidateBlankForm,
    VuelidateRef,
    VuelidateValidations
} from "~/functions/useVuelidateOnForm";
import ModalForm from "~/components/Common/ModalForm.vue";
import {AxiosRequestConfig} from "axios";
import {GlobalConfig} from "@vuelidate/core";
import {GenericForm} from "~/entities/Forms.ts";

export type ModalFormTemplateRef = InstanceType<typeof ModalForm> | null;

export interface BaseEditModalProps {
    createUrl?: string
}

export interface HasRelistEmit {
    (e: 'relist'): void
}

export type BaseEditModalEmits = HasRelistEmit;

export interface BaseEditModalOptions<T extends GenericForm = GenericForm> extends GlobalConfig {
    resetForm?(originalResetForm: () => void): void,

    clearContents?(resetForm: () => void): void,

    populateForm?(data: Partial<T>, form: Ref<T>): void,

    getSubmittableFormData?(form: Ref<T>, isEditMode: ComputedRef<boolean>): Record<string, any>,

    buildSubmitRequest?(): AxiosRequestConfig,

    onSubmitSuccess?(): void,

    onSubmitError?(error: any): void,
}

export function useBaseEditModal<T extends GenericForm = GenericForm>(
    props: BaseEditModalProps,
    emit: BaseEditModalEmits,
    $modal: Ref<ModalFormTemplateRef>,
    validations?: VuelidateValidations<T>,
    blankForm?: VuelidateBlankForm<T>,
    options: BaseEditModalOptions<T> = {}
): {
    loading: Ref<boolean>,
    error: Ref<any | null>,
    editUrl: Ref<string>,
    isEditMode: ComputedRef<boolean>,
    form: Ref<T>,
    v$: VuelidateRef<T>,
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

    const populateForm = (data: Partial<T>): void => {
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

import {computed, nextTick, Ref, ref, toRef} from "vue";
import mergeExisting from "~/functions/mergeExisting";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import ModalForm from "~/components/Common/ModalForm.vue";

export type ModalFormTemplateRef = InstanceType<typeof ModalForm> | null;

export const baseEditModalProps = {
    createUrl: {
        type: String,
        required: true
    }
};

export function useBaseEditModal(
    props,
    emit,
    $modal: Ref<ModalFormTemplateRef>,
    validations = {},
    blankForm = {},
    userOptions = {}
) {
    const createUrl = toRef(props, 'createUrl');

    const loading = ref(false);
    const error = ref(null);
    const editUrl = ref(null);

    const isEditMode = computed(() => {
        return editUrl.value !== null;
    });

    const options = {
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

    const resetForm = () => {
        if (typeof options.resetForm === 'function') {
            return options.resetForm(originalResetForm);
        }

        originalResetForm();
    };

    const clearContents = () => {
        if (typeof options.clearContents === 'function') {
            return options.clearContents(resetForm);
        }

        resetForm();

        loading.value = false;
        error.value = null;
        editUrl.value = null;
    };

    const create = () => {
        clearContents();

        $modal.value.show();

        nextTick(() => {
            resetForm();
        });
    };

    const populateForm = (data) => {
        if (typeof options.populateForm === 'function') {
            return options.populateForm(data, form);
        }

        form.value = mergeExisting(form.value, data);
    }

    const {notifySuccess} = useNotify();
    const {axios} = useAxios();

    const doLoad = () => {
        loading.value = true;

        axios.get(editUrl.value).then((resp) => {
            populateForm(resp.data);
        }).catch(() => {
            close();
        }).finally(() => {
            loading.value = false;
        });
    };

    const edit = (recordUrl) => {
        clearContents();

        editUrl.value = recordUrl;
        $modal.value.show();

        nextTick(() => {
            resetForm();
            doLoad();
        })
    };

    const getSubmittableFormData = () => {
        if (typeof options.getSubmittableFormData === 'function') {
            return options.getSubmittableFormData(form, isEditMode);
        }

        return form.value;
    };

    const buildSubmitRequest = () => {
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

    const close = () => {
        $modal.value.hide();
    };

    const onSubmitSuccess = () => {
        if (typeof options.onSubmitSuccess === 'function') {
            return options.onSubmitSuccess();
        }

        notifySuccess();
        emit('relist');
        close();
    };

    const onSubmitError = (err) => {
        if (typeof options.onSubmitError === 'function') {
            return options.onSubmitError(err);
        }

        error.value = err.response.data.message;
    };

    const doSubmit = () => {
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

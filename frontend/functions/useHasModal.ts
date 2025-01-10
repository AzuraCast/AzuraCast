import Modal from "~/components/Common/Modal.vue";
import {Ref} from "vue";
import {ModalFormTemplateRef} from "~/functions/useBaseEditModal.ts";

export type ModalTemplateRef = InstanceType<typeof Modal> | null;

export function useHasModal(modalRef: Ref<ModalTemplateRef | ModalFormTemplateRef>) {
    const hide = () => {
        modalRef.value?.hide();
    };

    const show = () => {
        modalRef.value?.show();
    };

    return {
        hide,
        show
    }
}

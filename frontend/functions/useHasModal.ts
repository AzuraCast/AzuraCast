import Modal from "~/components/Common/Modal.vue";
import {Ref} from "vue";

export type ModalTemplateRef = InstanceType<typeof Modal>;

export function useHasModal(modalRef: Ref<ModalTemplateRef>) {
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

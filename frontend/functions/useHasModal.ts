import {ShallowRef} from "vue";

interface ModalCompatible {
    show(): void,

    hide(): void,
}

export function useHasModal(modalRef: Readonly<ShallowRef<ModalCompatible | null>>) {
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

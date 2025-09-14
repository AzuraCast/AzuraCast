import {Ref} from "vue";

interface EditModalCompatible {
    create(): void,
    edit(editUrl: string): void
}

export default function useHasEditModal($modalRef: Ref<EditModalCompatible | null>) {
    const doCreate = (): void => {
        $modalRef.value?.create();
    };

    const doEdit = (editUrl: string): void => {
        $modalRef.value?.edit(editUrl);
    };

    return {
        doCreate,
        doEdit
    };
}

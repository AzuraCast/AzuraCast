import {Ref} from "vue";

interface EditModalCompatible {
    create(),

    edit(editUrl: string)
}

export type EditModalTemplateRef = InstanceType<EditModalCompatible> | null;


export default function useHasEditModal($modalRef: Ref<EditModalTemplateRef>) {
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

export default function useHasEditModal($modalRef) {
    const doCreate = () => {
        $modalRef.value?.create();
    };

    const doEdit = (editUrl) => {
        $modalRef.value?.edit(editUrl);
    };

    return {
        doCreate,
        doEdit
    };
}

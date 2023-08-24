export default function useHasDatatable($datatableRef) {
    const relist = () => {
        return $datatableRef.value?.relist();
    }

    return {
        relist
    };
}

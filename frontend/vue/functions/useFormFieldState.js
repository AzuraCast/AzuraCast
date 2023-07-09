import {computed} from "vue";

export default function useFormFieldState(field) {
    return computed(() => {
        if (!field.$dirty) {
            return null;
        }

        return field.$error
            ? 'is-invalid'
            : 'is-valid';
    });
}

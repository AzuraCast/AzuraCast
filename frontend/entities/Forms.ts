export type GenericForm = Record<string, any>

export interface HasGenericFormProps {
    form: GenericForm
}

export interface HasGenericFormEmits {
    (e: 'update:form', form: GenericForm): void
}

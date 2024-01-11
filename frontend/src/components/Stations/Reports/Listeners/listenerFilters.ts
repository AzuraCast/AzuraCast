export enum ListenerTypeFilter {
    All = 'all',
    Mobile = 'mobile',
    Desktop = 'desktop'
}

export interface ListenerFilters {
    type: ListenerTypeFilter,
    minLength: number | null,
    maxLength: number | null
}

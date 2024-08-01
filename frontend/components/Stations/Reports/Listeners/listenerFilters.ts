export enum ListenerTypeFilter {
    All = 'all',
    Mobile = 'mobile',
    Desktop = 'desktop',
    Bot = 'bot'
}

export interface ListenerFilters {
    type: ListenerTypeFilter,
    minLength: number | null,
    maxLength: number | null
}

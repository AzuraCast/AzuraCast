export const ListenerTypeFilters = Object.freeze({
    All: 'all',
    Mobile: 'mobile',
    Desktop: 'desktop',
    Bot: 'bot'
} as const);

export type ListenerTypeFilter = typeof ListenerTypeFilters[keyof typeof ListenerTypeFilters];

export interface ListenerFilters {
    type: ListenerTypeFilter,
    minLength: number | null,
    maxLength: number | null
}

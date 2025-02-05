export const FrontendAdapters = Object.freeze({
    Icecast: 'icecast',
    Shoutcast: 'shoutcast2',
    Rsas: 'rsas',
    Remote: 'remote'
} as const);

export type FrontendAdapter = typeof FrontendAdapters[keyof typeof FrontendAdapters];

export const BackendAdapters = Object.freeze({
    Liquidsoap: 'liquidsoap',
    None: 'none'
} as const);

export type BackendAdapter = typeof BackendAdapters[keyof typeof BackendAdapters];

export const RemoteAdapters = Object.freeze({
    Shoutcast1: 'shoutcast1',
    Shoutcast2: 'shoutcast2',
    Icecast: 'icecast',
    AzuraRelay: 'azurarelay'
} as const);

export type RemoteAdapter = typeof RemoteAdapters[keyof typeof RemoteAdapters];

export const AudioProcessingMethods = Object.freeze({
    None: 'none',
    Liquidsoap: 'nrj',
    MasterMe: 'master_me',
    StereoTool: 'stereo_tool'
} as const);

export type AudioProcessingMethod = typeof AudioProcessingMethods[keyof typeof AudioProcessingMethods];

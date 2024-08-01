export enum FrontendAdapter {
    Icecast = 'icecast',
    Shoutcast = 'shoutcast2',
    Remote = 'remote'
}

export enum BackendAdapter {
    Liquidsoap = 'liquidsoap',
    None = 'none'
}

export enum RemoteAdapter {
    Shoutcast1 = 'shoutcast1',
    Shoutcast2 = 'shoutcast2',
    Icecast = 'icecast',
    AzuraRelay = 'azurarelay'
}

export enum AudioProcessingMethod {
    None = 'none',
    Liquidsoap = 'nrj',
    MasterMe = 'master_me',
    StereoTool = 'stereo_tool'
}

export enum MasterMePreset {
    MusicGeneral = 'music_general',
    SpeechGeneral = 'speech_general',
    EbuR128 = 'ebu_r128',
    ApplePodcasts = 'apple_podcasts',
    YouTube = 'youtube'
}

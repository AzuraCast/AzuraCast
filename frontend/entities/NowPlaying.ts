import {ApiNowPlaying} from "~/entities/ApiInterfaces";

const npEmpty: ApiNowPlaying = {
    station: {
        id: 1,
        name: 'Station Name',
        shortcode: 'station_name',
        description: 'Station Description.',
        frontend: 'icecast',
        backend: 'liquidsoap',
        listen_url: '',
        url: '',
        playlist_pls_url: '',
        playlist_m3u_url: '',
        is_public: true,
        requests_enabled: false,
        mounts: [],
        remotes: [],
        timezone: 'UTC',
        public_player_url: '',
        hls_enabled: false,
        hls_is_default: false,
        hls_url: '',
        hls_listeners: 0
    },
    listeners: {
        current: 0,
        unique: 0,
        total: 0
    },
    live: {
        is_live: false,
        streamer_name: '',
        broadcast_start: null,
        art: null
    },
    now_playing: {
        elapsed: 0,
        remaining: 0,
        sh_id: 0,
        played_at: 0,
        duration: 0,
        playlist: 'default',
        streamer: '',
        is_request: false,
        song: {
            id: '',
            text: '',
            artist: '',
            title: '',
            album: '',
            genre: '',
            lyrics: '',
            art: '',
            custom_fields: []
        }
    },
    playing_next: {
        cued_at: 0,
        played_at: 0,
        duration: 0,
        playlist: 'default',
        is_request: false,
        song: {
            id: '',
            text: '',
            artist: '',
            title: '',
            album: '',
            genre: '',
            lyrics: '',
            art: '',
            custom_fields: []
        }
    },
    song_history: [],
    is_online: false,
    cache: 'station'
};

export default npEmpty;

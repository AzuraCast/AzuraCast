import {useAppRegle} from "~/vendor/regle.ts";
import {
    AudioProcessingMethods,
    BackendAdapters,
    CrossfadeModes,
    FrontendAdapters,
    MasterMePresets,
    Station,
    StationBackendConfiguration,
    StationFrontendConfiguration,
    StreamFormats
} from "~/entities/ApiInterfaces.ts";
import {defineStore} from "pinia";
import {numeric, required, url} from "@regle/rules";
import {ref} from "vue";

export type StationRecord = Omit<
    Required<Station>,
    | 'id'
    | 'is_streamer_live'
    | 'branding_config'
> & {
    frontend_config: Required<StationFrontendConfiguration>,
    backend_config: Omit<
        Required<StationBackendConfiguration>,
        | 'stereo_tool_configuration_path' | 'custom_config_top' | 'custom_config_pre_playlists'
        | 'custom_config_pre_live' | 'custom_config_pre_fade' | 'custom_config' | 'custom_config_bottom'
    >,
    media_storage_location: string | number,
    recordings_storage_location: string | number,
    podcasts_storage_location: string | number,
}

export const useAdminStationsForm = defineStore(
    'form-admin-stations',
    () => {
        const form = ref<StationRecord>({
            name: '',
            description: '',
            genre: '',
            url: '',
            timezone: 'UTC',
            enable_public_page: true,
            enable_on_demand: false,
            enable_on_demand_download: true,
            short_name: '',
            api_history_items: 5,
            frontend_type: FrontendAdapters.Icecast,
            frontend_config: {
                sc_license_id: '',
                sc_user_id: '',
                source_pw: '',
                admin_pw: '',
                relay_pw: '',
                streamer_pw: '',
                port: null,
                max_listeners: null,
                custom_config: '',
                banned_ips: '',
                banned_countries: [],
                allowed_ips: '',
                banned_user_agents: '',
            },
            backend_type: BackendAdapters.Liquidsoap,
            backend_config: {
                crossfade_type: CrossfadeModes.Normal,
                crossfade: 2,
                write_playlists_to_liquidsoap: true,
                share_encoders: false,
                audio_processing_method: AudioProcessingMethods.None,
                post_processing_include_live: true,
                master_me_preset: MasterMePresets.MusicGeneral,
                master_me_loudness_target: -16,
                stereo_tool_license_key: '',
                enable_auto_cue: false,
                enable_replaygain_metadata: false,
                telnet_port: null,
                autodj_queue_length: 3,
                use_manual_autodj: false,
                charset: 'UTF-8',
                performance_mode: 'disabled',
                duplicate_prevention_time_range: 120,
                hls_enable_on_public_player: false,
                hls_is_default: false,
                hls_segment_length: 4,
                hls_segments_in_playlist: 5,
                hls_segments_overhead: 2,
                record_streams: false,
                record_streams_format: StreamFormats.Mp3,
                record_streams_bitrate: 128,
                dj_buffer: 5,
                live_broadcast_text: 'Live Broadcast',
                dj_port: null,
                dj_mount_point: '/',
            },
            enable_hls: false,
            enable_requests: false,
            request_delay: 5,
            request_threshold: 15,
            enable_streamers: false,
            disconnect_deactivate_streamer: 0,
            media_storage_location: '',
            recordings_storage_location: '',
            podcasts_storage_location: '',
            is_enabled: true,
            max_bitrate: 0,
            max_mounts: 0,
            max_hls_streams: 0,
            radio_base_dir: '',
        });

        const {r$} = useAppRegle(
            form,
            {
                name: {required},
                url: {url},
                frontend_type: {required},
                backend_type: {required},
                request_delay: {numeric},
                request_threshold: {numeric},
                backend_config: {
                    dj_buffer: {numeric},
                    live_broadcast_text: {},
                    dj_port: {numeric},
                }
            },
            {
                validationGroups: (fields) => ({
                    profileTab: [
                        fields.name,
                        fields.description,
                        fields.genre,
                        fields.url,
                        fields.timezone,
                        fields.enable_public_page,
                        fields.enable_on_demand,
                        fields.enable_on_demand_download,
                        fields.short_name,
                        fields.api_history_items,
                    ],
                    frontendTab: [
                        fields.frontend_type,
                        fields.frontend_config.sc_license_id,
                        fields.frontend_config.sc_user_id,
                        fields.frontend_config.source_pw,
                        fields.frontend_config.admin_pw,
                        fields.frontend_config.port,
                        fields.frontend_config.max_listeners,
                        fields.frontend_config.custom_config,
                        fields.frontend_config.banned_ips,
                        fields.frontend_config.banned_countries,
                        fields.frontend_config.allowed_ips,
                        fields.frontend_config.banned_user_agents,
                    ],
                    backendTab: [
                        fields.backend_type,
                        fields.backend_config.crossfade_type,
                        fields.backend_config.crossfade,
                        fields.backend_config.write_playlists_to_liquidsoap,
                        fields.backend_config.share_encoders,
                        fields.backend_config.audio_processing_method,
                        fields.backend_config.post_processing_include_live,
                        fields.backend_config.master_me_preset,
                        fields.backend_config.master_me_loudness_target,
                        fields.backend_config.stereo_tool_license_key,
                        fields.backend_config.enable_auto_cue,
                        fields.backend_config.enable_replaygain_metadata,
                        fields.backend_config.telnet_port,
                        fields.backend_config.autodj_queue_length,
                        fields.backend_config.use_manual_autodj,
                        fields.backend_config.charset,
                        fields.backend_config.performance_mode,
                        fields.backend_config.duplicate_prevention_time_range,
                    ],
                    hlsTab: [
                        fields.enable_hls,
                        fields.backend_config.hls_enable_on_public_player,
                        fields.backend_config.hls_is_default,
                        fields.backend_config.hls_segment_length,
                        fields.backend_config.hls_segments_in_playlist,
                        fields.backend_config.hls_segments_overhead,
                    ],
                    requestsTab: [
                        fields.enable_requests,
                        fields.request_delay,
                        fields.request_threshold,
                    ],
                    streamersTab: [
                        fields.enable_streamers,
                        fields.disconnect_deactivate_streamer,
                        fields.backend_config.record_streams,
                        fields.backend_config.record_streams_format,
                        fields.backend_config.record_streams_bitrate,
                        fields.backend_config.dj_buffer,
                        fields.backend_config.live_broadcast_text,
                        fields.backend_config.dj_port,
                        fields.backend_config.dj_mount_point,
                    ],
                    adminTab: [
                        fields.media_storage_location,
                        fields.recordings_storage_location,
                        fields.podcasts_storage_location,
                        fields.is_enabled,
                        fields.max_bitrate,
                        fields.max_mounts,
                        fields.max_hls_streams,
                        fields.radio_base_dir,
                    ]
                })
            }
        );

        const $reset = () => {
            r$.$reset({toOriginalState: true});
        }

        return {
            form,
            r$,
            $reset
        }
    }
);

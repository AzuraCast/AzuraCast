import { ApiWidgetCustomization } from "~/entities/ApiInterfaces.ts";

export const defaultWidgetSettings: Required<ApiWidgetCustomization> = {
    showAlbumArt: true,
    roundedCorners: false,
    autoplay: false,
    showVolumeControls: true,
    showTrackProgress: true,
    showStreamSelection: true,
    showHistoryButton: true,
    showRequestButton: true,
    showPlaylistButton: true,
    initialVolume: 75,
    layout: "horizontal",
    enablePopupPlayer: false,
    continuousPlay: false,
    customCss: "",
    primaryColor: null,
    backgroundColor: null,
    textColor: null,
};

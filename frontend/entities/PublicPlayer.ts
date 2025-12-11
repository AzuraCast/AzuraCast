import {ApiWidgetCustomization} from "~/entities/ApiInterfaces.ts";

export const defaultWidgetSettings: ApiWidgetCustomization = {
    showAlbumArt: true,
    roundedCorners: false,
    autoplay: false,
    showVolumeControls: true,
    showTrackProgress: true,
    showStreamSelection: true,
    showHistoryButton: true,
    showRequestButton: true,
    initialVolume: 75,
    layout: 'horizontal',
    enablePopupPlayer: false,
    continuousPlay: false,
    customCss: '',
    primaryColor: undefined,
    backgroundColor: undefined,
    textColor: undefined
}

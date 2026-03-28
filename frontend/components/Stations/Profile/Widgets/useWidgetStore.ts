import {defineStore} from "pinia";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {defaultWidgetSettings} from "~/entities/PublicPlayer.ts";
import {ApiWidgetCustomization} from "~/entities/ApiInterfaces.ts";

type WidgetType =
    | 'player'
    | 'history'
    | 'podcasts'
    | 'schedule'
    | 'requests'
    | 'ondemand';

type WidgetTheme =
    | 'browser'
    | 'light'
    | 'dark'

export type WidgetTemplate = {
    type: WidgetType,
    theme: WidgetTheme,
    width: string | number,
    height: number,
    customization: ApiWidgetCustomization
}

export const useWidgetStore = defineStore(
    'stations-profile-widget-builder',
    () => {
        const {
            record: selectedType,
            reset: resetType
        } = useResettableRef<WidgetType>('player');

        const {
            record: selectedTheme,
            reset: resetTheme
        } = useResettableRef<WidgetTheme>('browser');

        const {
            record: customization,
            reset: resetCustomization
        } = useResettableRef<ApiWidgetCustomization>({
            ...defaultWidgetSettings,
            primaryColor: '#2196F3',
            backgroundColor: '#ffffff',
            textColor: '#000000',
        });

        const {
            record: customWidth,
            reset: resetWidth
        } = useResettableRef<string | number>('100%');

        const {
            record: customHeight,
            reset: resetHeight
        } = useResettableRef<number>(150);

        const $reset = () => {
            resetType();
            resetTheme();
            resetCustomization();
            resetWidth();
            resetHeight();
        }

        const getTemplate = (): WidgetTemplate => ({
            type: selectedType.value,
            theme: selectedTheme.value,
            customization: customization.value,
            width: customWidth.value,
            height: customHeight.value
        });

        const setFromTemplate = (template: WidgetTemplate) => {
            $reset();
            selectedType.value = template.type;
            selectedTheme.value = template.theme;
            customization.value = template.customization;
            customWidth.value = template.width;
            customHeight.value = template.height;
        }

        return {
            selectedType,
            selectedTheme,
            customization,
            customWidth,
            customHeight,
            $reset,
            getTemplate,
            setFromTemplate,
        };
    }
);

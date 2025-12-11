<template>
    <player v-bind="props"
            :class="widgetClasses"
            :style="widgetStyles"/>
</template>

<script setup lang="ts">
import {defaultWidgetSettings} from "~/entities/PublicPlayer.ts";
import Player, {PlayerProps} from "~/components/Public/Player.vue";
import {computed} from "vue";
import {useHead, useHeadSafe} from "@unhead/vue";

defineOptions({
    inheritAttrs: false
});

const props = withDefaults(
    defineProps<PlayerProps>(),
    {
        widgetCustomization: () => defaultWidgetSettings
    }
);

const popupLayout = computed(() => props.widgetCustomization?.layout ?? 'horizontal');
const isPopupContext = new URLSearchParams(window.location.search).has('popup');

const widgetClasses = computed(() => {
    const classes: string[] = [];

    if (props.widgetCustomization?.layout) {
        classes.push(`layout-${props.widgetCustomization.layout}`);
    }

    if (props.widgetCustomization?.roundedCorners) {
        classes.push('rounded-corners');
    }

    if (isPopupContext) {
        classes.push('popup-context');
    }

    return classes;
});

const widgetStyles = computed(() => {
    const styles: Record<string, string> = {};

    if (props.widgetCustomization?.primaryColor) {
        styles['--widget-primary-color'] = `#${props.widgetCustomization.primaryColor}`;
    }

    if (props.widgetCustomization?.backgroundColor) {
        styles['--widget-bg-color'] = `#${props.widgetCustomization.backgroundColor}`;
    }

    if (props.widgetCustomization?.textColor) {
        styles['--widget-text-color'] = `#${props.widgetCustomization.textColor}`;
    }

    if (props.widgetCustomization?.roundedCorners) {
        styles['--widget-border-radius'] = '12px';
    }

    return styles;
});

// Inject custom CSS if provided
useHeadSafe({
    style: [
        {
            key: 'custom-css',
            textContent: () => props.widgetCustomization?.customCss ?? ''
        }
    ]
});

useHead({
    bodyAttrs: {
        class: computed(() => {
            const classes: string[] = [
                'embed-player'
            ];

            if (isPopupContext) {
                classes.push('embed-player-popup');
            }

            const requiresScroll = popupLayout.value === 'vertical' || popupLayout.value === 'large';
            if (requiresScroll) {
                classes.push('embed-player-scrollable');
            }

            return classes;
        }),
        style: [
            'margin: 0',
            'overflow: hidden'
        ],
    },
    htmlAttrs: {
        style: [
            'overflow: hidden'
        ]
    }
});
</script>

<style lang="scss">
.radio-player-widget {
    // CSS Custom Properties for widget customization
    --widget-padding: 1rem;

    // Layout variants
    &.layout-vertical {
        .now-playing-details {
            flex-direction: column;
            text-align: center;

            .now-playing-art {
                margin-bottom: 1rem;
                margin-right: 0;
            }
        }

        .radio-controls {
            flex-direction: column;
            gap: 0.5rem;

            .radio-control-play-button {
                margin: 0 auto;
            }
        }
    }

    &.layout-compact {
        --widget-gap: 0.5rem;
        padding: 0.5rem;

        .now-playing-details {
            align-items: center;

            .now-playing-art {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;

                img {
                    width: 40px;
                    height: 40px;
                }
            }

            .now-playing-main {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }

        .now-playing-title {
            font-size: 0.9rem;
        }

        .now-playing-artist {
            font-size: 0.8rem;
        }

        .radio-controls {
            gap: 0.5rem;
        }
    }

    &.layout-large {
        padding: 2rem;

        .now-playing-details {
            .now-playing-art {
                width: 120px;
                height: 120px;
                margin-right: 2rem;
            }
        }

        .now-playing-title {
            font-size: 1.5rem;
        }

        .now-playing-artist {
            font-size: 1.2rem;
        }
    }

    &.rounded-corners {
        border-radius: 12px;
        overflow: hidden;
    }

    &.popup-context {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: clamp(1rem, 2vw, 1.75rem);
        height: 100%;

        .now-playing-details {
            align-items: flex-start;
            gap: clamp(1.25rem, 3vw, 2rem);

            .now-playing-art img {
                width: clamp(96px, 18vw, 150px);
                height: clamp(96px, 18vw, 150px);
                object-fit: cover;
                border-radius: 12px;
            }
        }

        .radio-controls {
            margin-top: auto;
            width: 100%;
            padding-top: clamp(0.75rem, 2vw, 1.25rem);

            .radio-control-volume {
                .radio-control-volume-slider {
                    max-width: 60%;
                }
            }
        }
    }
}

body.embed-player-scrollable {
    overflow: auto;

    .radio-player-widget {
        height: auto;
        min-height: auto;
        max-height: none;
    }
}

body.embed-player-popup {
    --popup-padding: clamp(1.25rem, 3vw, 3rem);
    margin: 0;
    background: var(--bs-body-bg);
    display: flex;
    align-items: stretch;
    justify-content: center;
    padding: var(--popup-padding);
    min-height: 100vh;
    overflow: hidden;

    .radio-player-widget {
        width: min(640px, calc(100vw - (var(--popup-padding) * 2)));
        min-height: calc(100vh - (var(--popup-padding) * 2));
        max-height: 100vh;
        --widget-padding: clamp(1.25rem, 2.5vw, 2.25rem);
        --widget-gap: clamp(0.9rem, 1.8vw, 1.4rem);
        --widget-bg-color: var(--bs-card-bg);
        box-shadow: var(--bs-box-shadow-lg, 0 1.25rem 2.5rem rgba(15, 23, 42, 0.16));
        border: 1px solid var(--bs-border-color-translucent, rgba(15, 23, 42, 0.12));
    }

    .radio-player-widget.rounded-corners {
        border-radius: 18px;
    }

    .radio-player-widget.layout-vertical {
        justify-content: flex-start;
    }
}

@media (max-width: 575px) {
    body.embed-player-popup {
        --popup-padding: 1rem;
        --widget-padding: 1.25rem;

        .radio-player-widget {
            width: 100%;
            height: calc(100vh - (var(--popup-padding) * 2));
            box-shadow: var(--bs-box-shadow, 0 0.75rem 1.5rem rgba(15, 23, 42, 0.16));
        }
    }
}
</style>

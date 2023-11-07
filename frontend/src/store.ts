import {defineStore} from "pinia";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery.ts";

export interface StreamDescriptor {
    url: string | null,
    isHls: boolean,
    isStream: boolean
}

interface PlayerStore {
    isPlaying: boolean,
    current: StreamDescriptor
}

export const usePlayerStore = defineStore(
    'player',
    {
        state: (): PlayerStore => ({
            isPlaying: false,
            current: {
                url: null,
                isHls: false,
                isStream: false
            }
        }),
        actions: {
            toggle(payload: StreamDescriptor): void {
                const currentUrl = getUrlWithoutQuery(this.current.url);
                const newUrl = getUrlWithoutQuery(payload.url);

                if (currentUrl === newUrl) {
                    this.current = {
                        url: null,
                        isHls: false,
                        isStream: false
                    };
                } else {
                    this.current = payload;
                }
            },
            startPlaying(): void {
                this.isPlaying = true;
            },
            stopPlaying(): void {
                this.isPlaying = false;
            }
        }
    }
);

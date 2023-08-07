import {defineStore, StoreDefinition} from "pinia";

export const usePlayerStore: StoreDefinition = defineStore(
    'player',
    {
        state: () => {
            return {
                isPlaying: false,
                current: {
                    url: null,
                    isStream: true
                }
            };
        },
        actions: {
            toggle(payload): void {
                const url = payload.url;

                if (this.current.url === url) {
                    this.current = {
                        url: null,
                        isStream: true
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

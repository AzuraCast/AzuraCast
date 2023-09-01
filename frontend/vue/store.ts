import {defineStore} from "pinia";

export const usePlayerStore = defineStore(
    'player',
    {
        state: () => ({
            isPlaying: false,
            current: {
                url: null,
                isStream: true
            }
        }),
        actions: {
            resetCurrent(): void {
                this.current = {
                    url: null,
                    isStream: true
                };
            },
            toggle(payload): void {
                const url = payload.url;

                if (this.current.url === url) {
                    this.resetCurrent();
                } else {
                    this.current = payload;
                }
            },
            startPlaying(): void {
                this.isPlaying = true;
            },
            stopPlaying(): void {
                this.isPlaying = false;
                this.resetCurrent();
            }
        }
    }
);

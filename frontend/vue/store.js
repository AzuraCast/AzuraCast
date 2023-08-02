import {defineStore} from "pinia";

export const usePlayerStore = defineStore(
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
            toggle(payload) {
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
            startPlaying() {
                this.isPlaying = true;
            },
            stopPlaying() {
                this.isPlaying = false;
            }
        }
    }
);

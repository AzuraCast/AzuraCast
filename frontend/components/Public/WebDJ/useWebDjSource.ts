import {useInjectWebDjNode} from "~/components/Public/WebDJ/useWebDjNode";
import {WebcasterMetadata} from "~/components/Public/WebDJ/useWebcaster.ts";

interface StreamAudioSourceWithStop extends MediaStreamAudioSourceNode {
    stop?(): void
}

interface TagLibAudio {
    length: string
}

export interface TagLibProcessResult {
    audio: TagLibAudio
    metadata: WebcasterMetadata | null
}

export interface WebDjFilePointer {
    file: File,
    audio: TagLibAudio
    metadata: WebcasterMetadata
}

export function useWebDjSource() {
    const {context} = useInjectWebDjNode();

    const createAudioSource = (
        pointer: WebDjFilePointer,
        cb: (source: MediaElementAudioSourceNode) => void,
        onEnd?: () => void
    ) => {
        const el = new Audio(URL.createObjectURL(pointer.file));
        el.controls = false;
        el.autoplay = false;
        el.loop = false;

        let source: any = null;

        el.addEventListener("ended", () => {
            if (typeof onEnd === "function") {
                onEnd();
            }
        });

        el.addEventListener("canplay", () => {
            if (source) {
                return;
            }

            source = context.value.createMediaElementSource(el);

            source.play = () => el.play()
            source.position = () => el.currentTime;
            source.duration = () => el.duration;
            source.paused = () => el.paused;
            source.stop = () => {
                el.pause();
                return el.remove();
            };
            source.pause = () => el.pause();
            source.seek = (percent: number) => {
                const time = percent * parseFloat(pointer.audio.length);
                el.currentTime = time;
                return time;
            };

            cb(source);
        });
    };

    const createMicrophoneSource = (
        audioDeviceId: ConstrainDOMString,
        cb: (source: StreamAudioSourceWithStop) => void
    ): void => {
        void (async () => {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: false,
                audio: {
                    deviceId: audioDeviceId
                }
            });

            const source: StreamAudioSourceWithStop = context.value.createMediaStreamSource(stream);
            source.stop = () => {
                const ref = stream.getAudioTracks();
                return (ref !== null)
                    ? ref[0].stop()
                    : 0;
            }

            cb(source);
        })();
    };

    return {
        createAudioSource,
        createMicrophoneSource
    }
}

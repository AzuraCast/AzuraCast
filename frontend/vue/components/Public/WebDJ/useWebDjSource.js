import {useInjectWebDjNode} from "~/components/Public/WebDJ/useWebDjNode";

export function useWebDjSource() {
    const {context} = useInjectWebDjNode();

    const createAudioSource = ({file, audio}, cb, onEnd) => {
        const el = new Audio(URL.createObjectURL(file));
        el.controls = false;
        el.autoplay = false;
        el.loop = false;

        let source = null;

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
            source.pause = () => el.pause;
            source.seek = (percent) => {
                const time = percent * parseFloat(audio.length);
                el.currentTime = time;
                return time;
            };

            return cb(source);
        });
    };

    const createMicrophoneSource = (audioDeviceId, cb) => {
        navigator.mediaDevices.getUserMedia({
            video: false,
            audio: {
                deviceId: audioDeviceId
            }
        }).then((stream) => {
            const source = context.value.createMediaStreamSource(stream);
            source.stop = () => {
                const ref = stream.getAudioTracks();
                return (ref !== null)
                    ? ref[0].stop()
                    : 0;
            }

            return cb(source);
        });
    };

    return {
        createAudioSource,
        createMicrophoneSource
    }
}

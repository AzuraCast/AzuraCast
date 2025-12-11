import {ref, shallowRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useTranslate} from "~/vendor/gettext";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

export interface WebcasterProps {
    baseUri: string
}

export interface WebcasterMetadata {
    title: string,
    artist: string
}

export const [useProvideWebcaster, useInjectWebcaster] = createRequiredInjectionState(
    (props: WebcasterProps) => {
        const { baseUri } = props;

        const { notifySuccess, notifyError } = useNotify();
        const { $gettext } = useTranslate();

        const metadata = shallowRef<WebcasterMetadata | null>(null);
        const isConnected = ref(false);

        let socket: WebSocket;

        const sendMetadata = (data: WebcasterMetadata) => {
            metadata.value = data;

            if (isConnected.value && socket) {
                socket.send(JSON.stringify({
                    type: "metadata",
                    data,
                }));
            }
        }

        const connect = (
            mediaRecorder: MediaRecorder,
            username: string | null = null,
            password: string | null = null
        ) => {
            socket = new WebSocket(baseUri, "webcast");

            const hello: {
                [key: string]: any
            } = {
                mime: mediaRecorder.mimeType,
            };

            if (null !== username) {
                hello.user = username;
            }
            if (null !== password) {
                hello.password = password;
            }

            socket.onopen = () => {
                socket.send(JSON.stringify({
                    type: "hello",
                    data: hello
                }));

                isConnected.value = true;

                // Timeout as Liquidsoap won't return any success/failure message, so the only
                // way we know if we're still connected is to set a timer.
                setTimeout(() => {
                    if (isConnected.value) {
                        notifySuccess($gettext('Web DJ connected!'));

                        if (metadata.value !== null) {
                            socket.send(JSON.stringify({
                                type: "metadata",
                                data: metadata.value
                            }));
                        }
                    }
                }, 1000);
            };

            socket.onerror = () => {
                notifyError($gettext('An error occurred with the Web DJ socket.'));
            }

            socket.onclose = () => {
                isConnected.value = false;
            };

            mediaRecorder.ondataavailable = async (e: BlobEvent) => {
                const data = await e.data.arrayBuffer();
                if (isConnected.value) {
                    socket.send(data);
                }
            };

            mediaRecorder.onstop = () => {
                if (isConnected.value) {
                    socket.close();
                }
            };
        };

        return {
            isConnected,
            connect,
            metadata,
            sendMetadata
        }
    }
);

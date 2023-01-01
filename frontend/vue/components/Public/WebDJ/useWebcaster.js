import {ref, shallowRef} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useTranslate} from "~/vendor/gettext";

export const webcasterProps = {
    baseUri: {
        type: String,
        required: true
    }
};

export function useWebcaster(props) {
    const {baseUri} = props;

    const {notifySuccess, notifyError} = useNotify();
    const {$gettext} = useTranslate();

    const metadata = shallowRef(null);
    const isConnected = ref(false);

    let socket = null;

    const sendMetadata = (data) => {
        metadata.value = data;

        if (isConnected.value) {
            socket.send(JSON.stringify({
                type: "metadata",
                data,
            }));
        }
    }

    const connect = (mediaRecorder, username = null, password = null) => {
        socket = new WebSocket(baseUri, "webcast");

        let hello = {
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

            setTimeout(() => {
                if (isConnected.value) {
                    notifySuccess($gettext('WebDJ connected!'));

                    if (metadata.value !== null) {
                        sendMetadata(metadata.value);
                    }
                }
            }, 2000);
        };

        socket.onerror = () => {
            notifyError($gettext('An error occurred with the WebDJ socket.'));
        }

        socket.onclose = () => {
            isConnected.value = false;
        };

        mediaRecorder.ondataavailable = async (e) => {
            const data = await e.data.arrayBuffer();
            if (isConnected()) {
                socket.send(data);
            }
        };

        mediaRecorder.onstop = () => {
            if (isConnected()) {
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

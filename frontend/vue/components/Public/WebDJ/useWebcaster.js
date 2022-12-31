export const webcasterProps = {
    baseUri: {
        type: String,
        required: true
    }
};

export function useWebcaster(props) {
    const {baseUri} = props;

    let socket = null;
    let mediaRecorder = null;

    const isConnected = () => {
        return socket !== null && socket.readyState === WebSocket.OPEN;
    };

    const connect = (newMediaRecorder, username = null, password = null) => {
        socket = new WebSocket(baseUri, "webcast");
        mediaRecorder = newMediaRecorder;

        let hello = {
            mime: mediaRecorder.mimeType,
        };

        if (null !== username) {
            hello.username = username;
        }
        if (null !== password) {
            hello.password = password;
        }

        socket.onopen = () => {
            socket.send(JSON.stringify({
                type: "hello",
                data: hello
            }))
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

    const sendMetadata = (data) => {
        socket.send(JSON.stringify({
            type: "metadata",
            data,
        }));
    }

    return {
        isConnected,
        connect,
        sendMetadata
    }
}

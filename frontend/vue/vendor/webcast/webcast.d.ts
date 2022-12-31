export declare const version = "1.0.1";
export declare class Socket {
    socket: WebSocket;
    constructor({ mediaRecorder, url: rawUrl, info, onError, onOpen, }: {
        mediaRecorder: MediaRecorder;
        url: string;
        info: Record<string, unknown>;
        onError?: (_: Event) => void;
        onOpen?: (_: Event) => void;
    });
    isConnected(): boolean;
    sendMetadata(data: Record<string, unknown>): void;
}
declare global {
    interface Window {
        Webcast: {
            Socket: typeof Socket;
            version: string;
        };
    }
}

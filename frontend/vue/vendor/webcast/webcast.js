"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Socket = exports.version = void 0;
exports.version = "1.0.1";
class Socket {
    constructor({ mediaRecorder, url: rawUrl, info, onError, onOpen, }) {
        const parser = document.createElement("a");
        parser.href = rawUrl;
        const user = parser.username;
        const password = parser.password;
        parser.username = parser.password = "";
        const url = parser.href;
        this.socket = new WebSocket(url, "webcast");
        if (onError)
            this.socket.onerror = onError;
        const hello = Object.assign(Object.assign(Object.assign({ mime: mediaRecorder.mimeType }, (user ? { user } : {})), (password ? { password } : {})), info);
        this.socket.onopen = function onopen(event) {
            if (onOpen)
                onOpen(event);
            this.send(JSON.stringify({
                type: "hello",
                data: hello,
            }));
        };
        mediaRecorder.ondataavailable = (e) => __awaiter(this, void 0, void 0, function* () {
            const data = yield e.data.arrayBuffer();
            if (this.isConnected()) {
                this.socket.send(data);
            }
        });
        mediaRecorder.onstop = (e) => {
            if (this.isConnected()) {
                this.socket.close();
            }
        };
    }
    isConnected() {
        return this.socket.readyState === WebSocket.OPEN;
    }
    sendMetadata(data) {
        this.socket.send(JSON.stringify({
            type: "metadata",
            data,
        }));
    }
}
exports.Socket = Socket;
if (typeof window !== "undefined") {
    window.Webcast = {
        version: "1.0.0",
        Socket,
    };
}

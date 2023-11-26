import {cloneDeep} from "lodash";

export default function useWebAuthn() {
    const recursiveBase64StrToArrayBuffer = (obj) => {
        const prefix = '=?BINARY?B?';
        const suffix = '?=';
        if (typeof obj === 'object') {
            for (const key in obj) {
                if (typeof obj[key] === 'string') {
                    let str = obj[key];
                    if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                        str = str.substring(prefix.length, str.length - suffix.length);

                        const binary_string = window.atob(str);
                        const len = binary_string.length;
                        const bytes = new Uint8Array(len);
                        for (let i = 0; i < len; i++) {
                            bytes[i] = binary_string.charCodeAt(i);
                        }
                        obj[key] = bytes.buffer;
                    }
                } else {
                    recursiveBase64StrToArrayBuffer(obj[key]);
                }
            }
        }
    }

    const arrayBufferToBase64 = (buffer) => {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    const isSupported: boolean = !!window.fetch && !!navigator.credentials && !!navigator.credentials.create;

    const processServerArgs = (serverArgs) => {
        const newArgs = cloneDeep(serverArgs);
        recursiveBase64StrToArrayBuffer(newArgs);
        return newArgs;
    };

    const processRegisterResponse = (cred) => {
        return {
            transports: cred.response.getTransports ? cred.response.getTransports() : null,
            clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null
        };
    }

    const processValidateResponse = (cred) => {
        return {
            id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
            clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
            signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
            userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
        };
    };

    return {
        isSupported,
        processServerArgs,
        processRegisterResponse,
        processValidateResponse,
    };
}

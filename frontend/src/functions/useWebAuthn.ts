import {cloneDeep} from "lodash";

export default function useWebAuthn() {
    let abortController = null;
    const abortAndCreateNew = (message: string) => {
        if (abortController) {
            const abortError = new Error(message);
            abortError.name = 'AbortError';
            abortController.abort(abortError);
        }

        abortController = new AbortController();
        return abortController.signal;
    };

    const cancel = () => {
        if (abortController) {
            const abortError = new Error('Operation cancelled.');
            abortError.name = 'AbortError';
            abortController.abort(abortError);
        }
    }

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

    const isSupported: boolean =
        window?.PublicKeyCredential !== undefined &&
        typeof window.PublicKeyCredential === 'function';

    const isConditionalSupported = async (): Promise<boolean> => {
        if (!isSupported) {
            return false;
        }

        if (!window.PublicKeyCredential || !PublicKeyCredential.isConditionalMediationAvailable) {
            return false;
        }

        return await PublicKeyCredential.isConditionalMediationAvailable();
    };

    const processServerArgs = (serverArgs) => {
        const newArgs = cloneDeep(serverArgs);
        recursiveBase64StrToArrayBuffer(newArgs);
        return newArgs;
    };

    // Registration (private creation)
    const processRegisterResponse = (cred) => {
        return {
            transports: cred.response.getTransports ? cred.response.getTransports() : null,
            clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null
        };
    }

    const doRegister = async (rawArgs: object) => {
        const registerArgs = processServerArgs(rawArgs);

        const signal = abortAndCreateNew('New registration started.');

        const options = {
            ...registerArgs,
            signal: signal
        };

        const rawResp = await navigator.credentials.create(options);
        return processRegisterResponse(rawResp);
    };

    // Validation (public login)
    const processValidateResponse = (cred) => {
        return {
            id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
            clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
            authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
            signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
            userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
        };
    };

    const doValidate = async (rawArgs: object, isConditional: boolean = false) => {
        const validateArgs = processServerArgs(rawArgs);

        const mediation = (isConditional) ? {
            mediation: 'conditional'
        } : {};

        const signal = abortAndCreateNew('New validation started.');

        const options = {
            ...validateArgs,
            ...mediation,
            signal: signal
        };

        const rawResp = await navigator.credentials.get(options);
        return processValidateResponse(rawResp);
    };

    return {
        isSupported,
        isConditionalSupported,
        doValidate,
        doRegister,
        cancel
    };
}

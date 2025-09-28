import {useAppRegle} from "~/vendor/regle.ts";
import {StorageLocation, StorageLocationAdapters} from "~/entities/ApiInterfaces.ts";
import {defineStore} from "pinia";
import {literal, required} from "@regle/rules";
import {createVariant} from "@regle/core";
import {ref} from "vue";

export type StorageLocationRecord = Omit<StorageLocation, 'id'>;

export const useAdminStorageLocationsForm = defineStore(
    'form-admin-storage-locations',
    () => {
        const form = ref<StorageLocationRecord>({
            adapter: StorageLocationAdapters.Local,
            path: '',
            storageQuota: '',
            dropboxAppKey: null,
            dropboxAppSecret: null,
            dropboxAuthToken: null,
            s3CredentialKey: null,
            s3CredentialSecret: null,
            s3Region: null,
            s3Version: 'latest',
            s3Bucket: null,
            s3Endpoint: null,
            s3UsePathStyle: false,
            sftpHost: null,
            sftpPort: 22,
            sftpUsername: null,
            sftpPassword: null,
            sftpPrivateKey: null,
            sftpPrivateKeyPassPhrase: null,
        });

        const {r$} = useAppRegle(
            form,
            () => {
                const variant = createVariant(form, 'adapter', [
                    {
                        adapter: {
                            literal: literal(StorageLocationAdapters.Dropbox),
                            required
                        },
                        dropboxAuthToken: {required},
                    },
                    {
                        adapter: {
                            literal: literal(StorageLocationAdapters.S3),
                            required
                        },
                        s3CredentialKey: {required},
                        s3CredentialSecret: {required},
                        s3Version: {required},
                        s3Bucket: {required},
                        s3Endpoint: {required},
                    },
                    {
                        adapter: {
                            literal: literal(StorageLocationAdapters.Sftp),
                            required
                        },
                        sftpHost: {required},
                        sftpPort: {required},
                        sftpUsername: {required},
                    },
                    {
                        adapter: {required}
                    }
                ]);

                return variant.value;
            },
            {
                validationGroups: (fields) => ({
                    generalTab: [
                        fields.adapter,
                        fields.path,
                        fields.storageQuota
                    ],
                    dropboxTab: [
                        fields.dropboxAppKey,
                        fields.dropboxAppSecret,
                        fields.dropboxAuthToken,
                    ],
                    s3Tab: [
                        fields.s3CredentialKey,
                        fields.s3CredentialSecret,
                        fields.s3Region,
                        fields.s3Version,
                        fields.s3Bucket,
                        fields.s3Endpoint,
                        fields.s3UsePathStyle,
                    ],
                    sftpTab: [
                        fields.sftpHost,
                        fields.sftpPort,
                        fields.sftpUsername,
                        fields.sftpPassword,
                        fields.sftpPrivateKey,
                        fields.sftpPrivateKeyPassPhrase,
                    ]
                })
            }
        );

        const $reset = () => {
            r$.$reset({toOriginalState: true});
        }

        return {
            form,
            r$,
            $reset
        }
    }
);

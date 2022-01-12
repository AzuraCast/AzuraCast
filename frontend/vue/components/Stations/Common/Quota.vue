<template>
    <div v-if="!loading" style="line-height: 1;">
        <template v-if="quota.available">
            <b-progress :value="quota.used_percent" :variant="progressVariant" show-progress
                        height="15px" class="mb-1"></b-progress>

            {{ langSpaceUsed }}
        </template>
        <template v-else>
            {{ langSpaceUsed }}
        </template>
    </div>
</template>

<script>
import mergeExisting from "~/functions/mergeExisting";

export default {
    name: 'StationsCommonQuota',
    emits: ['updated'],
    props: {
        quotaUrl: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            loading: true,
            quota: {
                used: null,
                used_bytes: null,
                used_percent: null,
                available: null,
                available_bytes: null,
                quota: null,
                quota_bytes: null,
                is_full: null,
                num_files: null
            },
        }
    },
    mounted() {
        this.update();
    },
    computed: {
        progressVariant() {
            if (this.quota.used_percent > 85) {
                return 'danger';
            } else if (this.quota.used_percent > 65) {
                return 'warning';
            } else {
                return 'default';
            }
        },
        langSpaceUsed() {
            const lang = (this.quota.available)
                ? this.$gettext('%{spaceUsed} of %{spaceTotal} Used')
                : this.$gettext('%{spaceUsed} Used');

            const langParsed = this.$gettextInterpolate(lang, {
                spaceUsed: this.quota.used,
                spaceTotal: this.quota.available
            });

            if (null !== this.quota.num_files) {
                const langNumFiles = this.$ngettext('%{filesCount} File', '%{filesCount} Files', this.quota.num_files);
                const langNumFilesParsed = this.$gettextInterpolate(langNumFiles, {filesCount: this.quota.num_files});
                return langParsed + ' (' + langNumFilesParsed + ')';
            }

            return langParsed;
        },
    },
    methods: {
        update() {
            this.axios.get(this.quotaUrl)
                .then((resp) => {
                    this.quota = mergeExisting(this.quota, resp.data);
                    this.loading = false;

                    this.$emit('updated', this.quota);
                });
        }
    }
}
</script>

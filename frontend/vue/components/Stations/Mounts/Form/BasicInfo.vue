<template>
    <b-tab
        :title="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="form-row mb-3">
                <b-wrapped-form-group
                    id="edit_form_name"
                    class="col-md-6"
                    :field="form.name"
                >
                    <template #label>
                        {{ $gettext('Mount Point URL') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('This name should always begin with a slash (/), and must be a valid URL, such as /autodj.mp3')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
                    id="edit_form_display_name"
                    class="col-md-6"
                    :field="form.display_name"
                >
                    <template #label>
                        {{ $gettext('Display Name') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('The display name assigned to this mount point when viewing it on administrative or public pages. Leave blank to automatically generate one.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox
                    id="edit_form_is_visible_on_public_pages"
                    class="col-md-6"
                    :field="form.is_visible_on_public_pages"
                >
                    <template #label>
                        {{ $gettext('Show on Public Pages') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Enable to allow listeners to select this mount point on this station\'s public pages.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-checkbox
                    id="edit_form_is_default"
                    class="col-md-6"
                    :field="form.is_default"
                >
                    <template #label>
                        {{ $gettext('Set as Default Mount Point') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('If this mount is the default, it will be played on the radio preview and the public radio page in this system.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group
                    id="edit_form_relay_url"
                    class="col-md-6"
                    :field="form.relay_url"
                >
                    <template #label>
                        {{ $gettext('Relay Stream URL') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Enter the full URL of another stream to relay its broadcast through this mount point.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox
                    id="edit_form_is_public"
                    class="col-md-6"
                    :field="form.is_public"
                >
                    <template #label>
                        {{ $gettext('Publish to "Yellow Pages" Directories') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Enable to advertise this mount point on "Yellow Pages" public radio directories.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group
                    id="edit_form_max_listener_duration"
                    class="col-md-6"
                    :field="form.max_listener_duration"
                    input-type="number"
                    :input-attrs="{min: '0', max: '2147483647'}"
                >
                    <template #label>
                        {{ $gettext('Max Listener Duration') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Set the length of time (seconds) a listener will stay connected to the stream. If set to 0, listeners can stay connected infinitely.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <template v-if="isShoutcast">
                    <b-wrapped-form-group
                        id="edit_form_authhash"
                        class="col-md-6"
                        :field="form.authhash"
                    >
                        <template #label>
                            {{ $gettext('YP Directory Authorization Hash') }}
                        </template>
                        <template #description>
                            <span v-html="langAuthhashDesc" />
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="isIcecast">
                    <b-wrapped-form-group
                        id="edit_form_fallback_mount"
                        class="col-md-6"
                        :field="form.fallback_mount"
                    >
                        <template #label>
                            {{ $gettext('Fallback Mount') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('If this mount point is not playing audio, listeners will automatically be redirected to this mount point. The default is /error.mp3, a repeating error message.')
                            }}
                        </template>
                    </b-wrapped-form-group>
                </template>
            </div>
        </b-form-group>
    </b-tab>
</template>

<script setup>
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    stationFrontendType: {
        type: String,
        required: true
    }
});

const {$gettext} = useTranslate();

const langAuthhashDesc = $gettext(
    'If your stream is set to advertise to YP directories above, you must specify an authorization hash. You can manage authhashes <a href="%{ url }" target="_blank">on the Shoutcast web site</a>.',
    {url: 'https://radiomanager.shoutcast.com/'}
);

const isIcecast = computed(() => {
    return FRONTEND_ICECAST === props.stationFrontendType;
});

const isShoutcast = computed(() => {
    return FRONTEND_SHOUTCAST === props.stationFrontendType;
});
</script>

<template>
    <o-tab-item
        :label="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="row g-3 mb-3">
                <form-group-field
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
                </form-group-field>

                <form-group-field
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
                </form-group-field>

                <form-group-checkbox
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
                </form-group-checkbox>

                <form-group-checkbox
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
                </form-group-checkbox>

                <form-group-field
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
                </form-group-field>

                <form-group-checkbox
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
                </form-group-checkbox>

                <form-group-field
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
                </form-group-field>

                <template v-if="isShoutcast">
                    <form-group-field
                        id="edit_form_authhash"
                        class="col-md-6"
                        :field="form.authhash"
                    >
                        <template #label>
                            {{ $gettext('YP Directory Authorization Hash') }}
                        </template>
                        <template #description>
                            {{
                                $gettext('If your stream is set to advertise to YP directories above, you must specify an authorization hash. You can manage these on the Shoutcast web site.')
                            }}
                            <br>
                            <a
                                href="https://radiomanager.shoutcast.com/"
                                target="_blank"
                            >
                                {{ $gettext('Shoutcast Radio Manager') }}
                            </a>
                        </template>
                    </form-group-field>
                </template>
                <template v-if="isIcecast">
                    <form-group-field
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
                    </form-group-field>
                </template>
            </div>
        </b-form-group>
    </o-tab-item>
</template>

<script setup>
import {FRONTEND_ICECAST, FRONTEND_SHOUTCAST} from '~/components/Entity/RadioAdapters';
import FormGroupField from "~/components/Form/FormGroupField";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox";
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

const isIcecast = computed(() => {
    return FRONTEND_ICECAST === props.stationFrontendType;
});

const isShoutcast = computed(() => {
    return FRONTEND_SHOUTCAST === props.stationFrontendType;
});
</script>

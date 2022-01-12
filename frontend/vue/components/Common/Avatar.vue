<template>
    <a :href="serviceUrl" class="avatar" target="_blank" v-b-tooltip.hover.right :title="langAvatar"
       v-if="'' !== serviceUrl">
        <img :src="url" :style="{ width: this.width+'px', height: 'auto' }" alt="">
    </a>
</template>

<script>
export default {
    name: 'Avatar',
    props: {
        url: String,
        service: String,
        serviceUrl: String,
        width: {
            type: Number,
            default: 64
        }
    },
    computed: {
        langAvatar() {
            let translated = this.$gettext('Avatars are retrieved based on your e-mail address from the %{service} service. Click to manage your %{service} settings.');
            return this.$gettextInterpolate(translated, {service: this.service});
        }
    }
};

</script>

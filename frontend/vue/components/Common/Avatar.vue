<template>
    <a :href="avatarServiceUrl" target="_blank" v-b-tooltip.hover.right :title="langAvatar" v-if="'' !== this.avatarServiceUrl">
        <img :src="avatar" style="width: 64px; height: auto;" alt="">
    </a>
</template>

<script>
export const avatarProps = {
    props: {
        avatar: String,
        avatarServiceUrl: String,
        avatarServiceName: String
    }
};

export default {
    name: 'Avatar',
    mixins: [avatarProps],
    computed: {
        langAvatar () {
            let translated = this.$gettext('Avatars are retrieved based on your e-mail address from the %{service} service. Click to manage your %{service} settings.');
            return this.$gettextInterpolate(translated, { service: this.avatarServiceName });
        }
    }
};

</script>

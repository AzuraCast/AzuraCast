export default {
    requestListUri: {
        type: String,
        required: true
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
    customFields: {
        type: Array,
        required: false,
        default: () => []
    }
}

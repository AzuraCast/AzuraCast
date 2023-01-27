export default {
    // Global
    showAdminTab: {
        type: Boolean,
        default: true
    },
    showAdvanced: {
        type: Boolean,
        default: true
    },
    // Profile
    timezones: Object,
    // Frontend
    isShoutcastInstalled: {
        type: Boolean,
        default: false
    },
    isStereoToolInstalled: {
        type: Boolean,
        default: false
    },
    countries: Object,
    // Admin
    storageLocationApiUrl: String
}

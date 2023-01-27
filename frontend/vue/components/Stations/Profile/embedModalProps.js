export default {
    stationSupportsStreamers: {
        type: Boolean,
        required: true
    },
    stationSupportsRequests: {
        type: Boolean,
        required: true
    },
    enablePublicPage: {
        type: Boolean,
        required: true
    },
    enableStreamers: {
        type: Boolean,
        required: true
    },
    enableOnDemand: {
        type: Boolean,
        required: true
    },
    enableRequests: {
        type: Boolean,
        required: true
    },
    publicPageEmbedUri: {
        type: String,
        required: true
    },
    publicOnDemandEmbedUri: {
        type: String,
        required: true
    },
    publicRequestEmbedUri: {
        type: String,
        required: true
    },
    publicHistoryEmbedUri: {
        type: String,
        required: true
    },
    publicScheduleEmbedUri: {
        type: String,
        required: true
    }
}

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
    publicPageUri: {
        type: String,
        required: true
    },
    publicWebDjUri: {
        type: String,
        required: true
    },
    publicOnDemandUri: {
        type: String,
        required: true
    },
    publicPodcastsUri: {
        type: String,
        required: true
    },
    publicScheduleUri: {
        type: String,
        required: true
    },
}

export default {
    apiUrl: {
        type: String,
        required: true,
    },
    testMessageUrl: {
        type: String,
        required: true
    },
    acmeUrl: {
        type: String,
        required: true
    },
    releaseChannel: {
        type: String,
        default: 'rolling',
        required: false
    }
};

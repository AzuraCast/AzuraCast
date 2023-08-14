export default {
    frontendType: {
        type: String,
        required: true
    },
    frontendAdminUri: {
        type: String,
        required: true
    },
    frontendAdminPassword: {
        type: String,
        required: true
    },
    frontendSourcePassword: {
        type: String,
        required: true
    },
    frontendRelayPassword: {
        type: String,
        required: true
    },
    frontendPort: {
        type: Number,
        required: true
    },
    frontendRestartUri: {
        type: String,
        required: true
    },
    frontendStartUri: {
        type: String,
        required: true
    },
    frontendStopUri: {
        type: String,
        required: true
    },
    hasStarted: {
        type: Boolean,
        required: true
    }
}

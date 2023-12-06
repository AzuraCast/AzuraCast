import {ApiNowPlaying} from "~/entities/ApiInterfaces.ts";
import {Channel, createChannel, createSession, Session} from "better-sse";
import {App} from '@tinyhttp/app';
import {json} from "milliparsec";

const publicPort: number = 6050;
const internalPort: number = 6055;

interface NowPlayingSubmission {
    channel: string,
    payload: ApiNowPlaying
}

interface StationChannelState extends Record<string, unknown> {
    timestamp: number,
    lastMessage: ApiNowPlaying
}

const unixTimestamp = (): number => Math.floor(Date.now() / 1000);

const timeChannel = createChannel();
timeChannel.on("session-registered", (session: Session) => {
    session.push({
        type: 'time',
        payload: {
            timestamp: unixTimestamp()
        }
    });
});

const stationChannels: Map<string, Channel<StationChannelState>> = new Map();

// Routine time ping.
setInterval(() => {
    console.debug('Sending time ping...');
    timeChannel.broadcast({
        type: 'time',
        payload: {
            timestamp: unixTimestamp()
        }
    });
}, 15000);

// If a station hasn't posted NP updates in a specified time, close its channel and garbage-collect its sessions.
setInterval(() => {
    const threshold = unixTimestamp() - 120;

    for (const [key, channel] of stationChannels) {
        if (channel.state.timestamp < threshold) {
            channel.activeSessions.forEach((session) => {
                channel.deregister(session);
            });
            stationChannels.delete(key);
        }
    }
}, 60000);

const publicServer = new App();

publicServer.get('/:station', async (req, res) => {
    const stationId: string = req.params.station;

    if (!stationChannels.has(stationId)) {
        res.status(404).send('Station Not Found');
    }

    const session = await createSession(req, res, {
        retry: 5000,
        headers: {
            "Access-Control-Allow-Origin": "*",
            "X-Accel-Buffering": "no",
        }
    });

    timeChannel.register(session);

    const stationChannel = stationChannels.get(stationId);
    stationChannel.register(session);
});

publicServer.listen(publicPort, () => {
    console.debug(`Public server listening on port ${publicPort}...`);
});

const privateServer = new App();

privateServer.use(json());

privateServer.post('/', async (req, res) => {
    const body: NowPlayingSubmission = req.body;

    console.debug(
        `NP Update received for channel ${body.channel}.`
    );

    let channel: Channel<StationChannelState>;
    if (stationChannels.has(body.channel)) {
        channel = stationChannels.get(body.channel);
    } else {
        // Create a new channel if none exists.
        channel = createChannel();
        channel.on("session-registered", (session: Session) => {
            session.push({
                type: 'nowplaying',
                payload: channel.state.lastMessage
            });
        });
        stationChannels.set(body.channel, channel);
    }

    channel.state.timestamp = unixTimestamp();
    channel.state.lastMessage = body.payload;
    channel.broadcast({
        type: 'nowplaying',
        payload: body.payload
    });

    return res.send('OK');
});

privateServer.listen(internalPort, () => {
    console.debug(`Internal server listening on port ${internalPort}...`);
});

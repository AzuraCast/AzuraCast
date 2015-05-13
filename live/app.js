var express = require('express'),
    http = require('http'),
    Socket = require('socket.io'),
    bodyParser = require('body-parser');

var local_port = 4001;
var remote_port = 4002;

// Set up local and remote servers.
var app_remote = express();
var http_remote = http.Server(app_remote);
var io = Socket(http_remote);

var app_local = express();
var http_local = http.Server(app_local);

// Handle local information update.
var np_cache = {};

var current_clients = 0;
var latest_update = 0;

app_local.use(bodyParser.json({ limit: '50mb' }));

app_local.post('/data', function(req, res) {
    var remote_body = req.body;

    if (remote_body.type == 'nowplaying')
        np_cache = remote_body.contents;

    io.emit(remote_body.type, remote_body.contents);

    latest_update = Math.floor(new Date() / 1000);

    res.end("yes");
});

app_local.get('/data', function(req, res) {
    res.json({
        'clients': current_clients,
        'latest_update': latest_update
    });
});

// Handle remote information publishing.
io.on('connection', function(socket) {
    current_clients++;

    // Immediately send both types of nowplaying data.
    socket.emit('nowplaying', np_cache);

    socket.on('disconnect', function () {
        current_clients--;
    });
});

app_remote.get('/', function(req, res) {
    res.send('Ponyville Live! Live Updates API');
});

// Trigger listening on local and remote HTTP servers.
http_local.listen(local_port, function() {
    console.log('Local listening on %d', local_port);
});

http_remote.listen(remote_port, function() {
    console.log('Remote listening on %d', remote_port);
});
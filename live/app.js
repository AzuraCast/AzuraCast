var express = require('express'),
    http = require('http'),
    Socket = require('socket.io');

var local_port = 4001;
var remote_port = 4002;

// Set up local and remote servers.
var app_remote = express();
var http_remote = http.Server(app_remote);
var io = Socket(http_remote);

var app_local = express();
var http_local = http.Server(app_local);

// Handle local information update.
var current_np = {};

app_local.post('/data', function(req, res) {
    var remote_body = req.body;
    if (remote_body.type == 'nowplaying')
        current_np = remote_body.contents;

    io.emit(remote_body.type, remote_body.contents);
});

// Handle remote information publishing.
io.on('connection', function(socket) {
    socket.emit('nowplaying', currentStatus);
});

// Trigger listening on local and remote HTTP servers.
http_local.listen(local_port, function() {
    console.log('Local listening on %d', local_port);
});

http_remote.listen(remote_port, function() {
    console.log('Remote listening on %d', remote_port);
});
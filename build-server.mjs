import {WebSocketServer} from 'ws';

const server = new WebSocketServer({
    port: 8999
});

let sockets = [];

server.on('connection', function (socket) {
    sockets.push(socket);
    console.log('Connected socket count:', sockets.length);

    // When you receive a message, send that message to every socket.
    socket.on('message', function (msg) {
        console.log('Message from client:', msg.toString());

        if (msg.toString() === 'reload') {
            console.log('Triggering browser clients to reload');
            sockets.forEach(s => s.send('reload-browser'));
        }
    });

    // When a socket closes, or disconnects, remove it from the array.
    socket.on('close', function () {
        sockets = sockets.filter(s => s !== socket);
        console.log('Remaining socket count:', sockets.length);
    });
});

console.log('Websocket Server started');
const express = require('express');
const app = express();
const server = require('http').createServer(app);
const io = require('socket.io')(server, {
    cors: {
        origin: "http://localhost",
        methods: ["GET", "POST"],
        credentials: true
    }
});
const { ExpressPeerServer } = require('peer');
const cors = require('cors');

app.use(cors({
    origin: 'http://localhost',
    methods: ['GET', 'POST'],
    credentials: true
}));

const peerServer = ExpressPeerServer(server, {
    debug: true,
    path: '/peer',
    allow_discovery: true,
    proxied: false,
    generateClient: true // Ensure polling is supported
});

app.use('/peer', (req, res, next) => {
    res.header('Access-Control-Allow-Origin', 'http://localhost');
    res.header('Access-Control-Allow-Methods', 'GET, POST');
    res.header('Access-Control-Allow-Credentials', 'true');
    next();
}, peerServer);

app.use(express.static('public'));

app.get('/', (req, res) => {
    res.send('Node.js server for real-time video and coding. Access via interview.php on your PHP server.');
});

io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    socket.on('join-room', (roomId, userId) => {
        console.log(`User ${userId} joined room ${roomId}`);
        socket.join(roomId);
        socket.to(roomId).emit('user-connected', userId);

        socket.on('code-change', (data) => {
            console.log(`Code change in room ${roomId}:`, data.code);
            socket.to(roomId).emit('code-update', data.code);
        });

        socket.on('disconnect', () => {
            console.log(`User ${userId} disconnected from room ${roomId}`);
            socket.to(roomId).emit('user-disconnected', userId);
        });
    });

    socket.on('error', (err) => {
        console.error('Socket.IO server error:', err);
    });
});

server.on('error', (err) => {
    console.error('Server error:', err);
});

peerServer.on('connection', (client) => {
    console.log('PeerJS client connected:', client.getId());
});

peerServer.on('error', (err) => {
    console.error('PeerJS server error:', err);
});

peerServer.on('disconnect', (client) => {
    console.log('PeerJS client disconnected:', client.getId());
});

server.listen(3000, () => {
    console.log('Server running on http://localhost:3000');
});
/**
 * WebRTC SIP Client using JsSIP
 * Provides WebRTC calling functionality
 */

class WebRTCClient {
    constructor(config) {
        this.config = {
            uri: config.uri || 'sip:1000@domain.com',
            password: config.password || '',
            wsServers: config.wsServers || 'wss://domain.com:7443',
            displayName: config.displayName || 'WebRTC User',
            debug: config.debug || false,
            ...config
        };
        
        this.ua = null;
        this.session = null;
        this.isRegistered = false;
        this.isInCall = false;
        this.localStream = null;
        this.remoteStream = null;
        
        this.callbacks = {
            onRegistered: () => {},
            onUnregistered: () => {},
            onIncomingCall: () => {},
            onCallStarted: () => {},
            onCallEnded: () => {},
            onCallFailed: () => {},
        };
    }

    /**
     * Initialize and connect
     */
    init() {
        const socket = new JsSIP.WebSocketInterface(this.config.wsServers);
        
        const configuration = {
            sockets: [socket],
            uri: this.config.uri,
            password: this.config.password,
            display_name: this.config.displayName,
            session_timers: false,
            register: true,
            register_expires: 600,
        };

        this.ua = new JsSIP.UA(configuration);

        // Set up event handlers
        this.ua.on('connected', () => {
            console.log('WebRTC: Connected to WebSocket');
        });

        this.ua.on('disconnected', () => {
            console.log('WebRTC: Disconnected from WebSocket');
        });

        this.ua.on('registered', () => {
            console.log('WebRTC: Registered');
            this.isRegistered = true;
            this.callbacks.onRegistered();
        });

        this.ua.on('unregistered', () => {
            console.log('WebRTC: Unregistered');
            this.isRegistered = false;
            this.callbacks.onUnregistered();
        });

        this.ua.on('registrationFailed', (data) => {
            console.error('WebRTC: Registration failed', data.cause);
        });

        this.ua.on('newRTCSession', (data) => {
            this.handleNewSession(data.session);
        });

        // Start the UA
        this.ua.start();
    }

    /**
     * Handle new RTC session
     */
    handleNewSession(session) {
        this.session = session;

        // Incoming call
        if (session.direction === 'incoming') {
            this.callbacks.onIncomingCall(session);
        }

        // Session events
        session.on('progress', () => {
            console.log('WebRTC: Call progress');
        });

        session.on('accepted', () => {
            console.log('WebRTC: Call accepted');
            this.isInCall = true;
            this.callbacks.onCallStarted();
        });

        session.on('confirmed', () => {
            console.log('WebRTC: Call confirmed');
            const localStream = session.connection.getLocalStreams()[0];
            const remoteStream = session.connection.getRemoteStreams()[0];
            
            this.localStream = localStream;
            this.remoteStream = remoteStream;
            
            // Attach streams to audio elements
            this.attachStreams(localStream, remoteStream);
        });

        session.on('ended', () => {
            console.log('WebRTC: Call ended');
            this.isInCall = false;
            this.session = null;
            this.callbacks.onCallEnded();
        });

        session.on('failed', (data) => {
            console.error('WebRTC: Call failed', data.cause);
            this.isInCall = false;
            this.session = null;
            this.callbacks.onCallFailed(data.cause);
        });
    }

    /**
     * Make outgoing call
     */
    call(destination, mediaConstraints = { audio: true, video: false }) {
        if (!this.ua || !this.isRegistered) {
            console.error('WebRTC: Not registered');
            return false;
        }

        const options = {
            mediaConstraints: mediaConstraints,
            pcConfig: {
                iceServers: [
                    { urls: ['stun:stun.l.google.com:19302'] }
                ]
            }
        };

        try {
            this.ua.call(destination, options);
            return true;
        } catch (error) {
            console.error('WebRTC: Call failed', error);
            return false;
        }
    }

    /**
     * Answer incoming call
     */
    answer(mediaConstraints = { audio: true, video: false }) {
        if (!this.session) {
            console.error('WebRTC: No active session');
            return false;
        }

        const options = {
            mediaConstraints: mediaConstraints,
            pcConfig: {
                iceServers: [
                    { urls: ['stun:stun.l.google.com:19302'] }
                ]
            }
        };

        try {
            this.session.answer(options);
            return true;
        } catch (error) {
            console.error('WebRTC: Answer failed', error);
            return false;
        }
    }

    /**
     * Hang up call
     */
    hangup() {
        if (!this.session) {
            return false;
        }

        try {
            this.session.terminate();
            return true;
        } catch (error) {
            console.error('WebRTC: Hangup failed', error);
            return false;
        }
    }

    /**
     * Mute/unmute audio
     */
    toggleMute() {
        if (!this.session) {
            return false;
        }

        if (this.session.isMuted().audio) {
            this.session.unmute({ audio: true });
            return false; // not muted
        } else {
            this.session.mute({ audio: true });
            return true; // muted
        }
    }

    /**
     * Hold/unhold call
     */
    toggleHold() {
        if (!this.session) {
            return false;
        }

        if (this.session.isOnHold().local) {
            this.session.unhold();
            return false; // not on hold
        } else {
            this.session.hold();
            return true; // on hold
        }
    }

    /**
     * Send DTMF tone
     */
    sendDTMF(tone) {
        if (!this.session) {
            return false;
        }

        try {
            this.session.sendDTMF(tone);
            return true;
        } catch (error) {
            console.error('WebRTC: DTMF failed', error);
            return false;
        }
    }

    /**
     * Transfer call
     */
    transfer(destination) {
        if (!this.session) {
            return false;
        }

        try {
            this.session.refer(destination);
            return true;
        } catch (error) {
            console.error('WebRTC: Transfer failed', error);
            return false;
        }
    }

    /**
     * Attach audio streams to DOM elements
     */
    attachStreams(localStream, remoteStream) {
        // Attach remote stream to audio element
        const remoteAudio = document.getElementById('remoteAudio');
        if (remoteAudio && remoteStream) {
            remoteAudio.srcObject = remoteStream;
        }

        // Optionally attach local stream (for monitoring)
        const localAudio = document.getElementById('localAudio');
        if (localAudio && localStream) {
            localAudio.srcObject = localStream;
            localAudio.muted = true; // Prevent echo
        }
    }

    /**
     * Unregister and disconnect
     */
    disconnect() {
        if (this.ua) {
            this.ua.stop();
            this.ua = null;
        }
    }

    /**
     * Register callbacks
     */
    on(event, callback) {
        if (this.callbacks[event] !== undefined) {
            this.callbacks[event] = callback;
        }
    }

    /**
     * Get registration status
     */
    getRegistrationStatus() {
        return this.isRegistered;
    }

    /**
     * Get call status
     */
    getCallStatus() {
        return this.isInCall;
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebRTCClient;
} else {
    window.WebRTCClient = WebRTCClient;
}

document.addEventListener("DOMContentLoaded", () => {
    const API_URL = 'http://localhost:8080/api';
    const WS_URL = 'ws://localhost:8081';

    const loginModal = document.getElementById('loginModal');
    const app = document.getElementById('app');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const logoutBtn = document.getElementById('logoutBtn');
    const currentUsernameEl = document.getElementById('currentUsername');
    const channelsList = document.getElementById('channelsList');
    const dmList = document.getElementById('dmList');
    const chatTitle = document.getElementById('chatTitle');
    const chatDescription = document.getElementById('chatDescription');
    const messagesContainer = document.getElementById('messagesContainer');
    const messageInput = document.getElementById('messageInput');
    const messageText = document.getElementById('messageText');
    const sendBtn = document.getElementById('sendBtn');
    const createChannelBtn = document.getElementById('createChannelBtn');
    const createChannelModal = document.getElementById('createChannelModal');
    const createChannelForm = document.getElementById('createChannelForm');
    const closeModalBtns = document.querySelectorAll('.close-btn, .btn-secondary[data-modal]');
    const notifications = document.getElementById('notifications');
    const joinChannelBtn = document.getElementById('joinChannelBtn');
    const leaveChannelBtn = document.getElementById('leaveChannelBtn');

    let state = {
        token: localStorage.getItem('token'),
        user: JSON.parse(localStorage.getItem('user')),
        currentChannel: null,
        currentDM: null,
        websocket: null,
        channels: [],
        dms: []
    };

    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notifications.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 5000);
    };

    const apiRequest = async (endpoint, method = 'GET', body = null) => {
        const headers = {
            'Content-Type': 'application/json'
        };
        if (state.token) {
            headers['Authorization'] = `Bearer ${state.token}`;
        }

        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method,
                headers,
                body: body ? JSON.stringify(body) : null
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'An error occurred');
            }

            return await response.json();
        } catch (error) {
            showNotification(error.message, 'error');
            throw error;
        }
    };

    const handleLogin = async (e) => {
        e.preventDefault();
        const username = loginForm.loginUsername.value;
        const password = loginForm.loginPassword.value;

        try {
            const data = await apiRequest('/login', 'POST', { username, password });
            if (data.success) {
                state.token = data.token;
                state.user = { id: data.user_id, username };
                localStorage.setItem('token', state.token);
                localStorage.setItem('user', JSON.stringify(state.user));
                initApp();
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            // ...
        }
    };

    const handleRegister = async (e) => {
        e.preventDefault();
        const username = registerForm.registerUsername.value;
        const password = registerForm.registerPassword.value;
        const confirmPassword = registerForm.confirmPassword.value;

        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }

        try {
            const data = await apiRequest('/register', 'POST', { username, password });
            if (data.success) {
                showNotification('Registration successful! Please log in.', 'success');
                switchTab('login');
                loginForm.loginUsername.value = username;
                loginForm.loginPassword.value = '';
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            // ...
        }
    };

    const handleLogout = () => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        state.token = null;
        state.user = null;
        if (state.websocket) {
            state.websocket.close();
        }
        showLogin();
    };

    const switchTab = (tabName) => {
        tabButtons.forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add('active');

        document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
        document.getElementById(`${tabName}Form`).classList.add('active');
    };

    const renderChannels = () => {
        channelsList.innerHTML = '';
        state.channels.forEach(channel => {
            const item = document.createElement('div');
            item.className = `channel-item ${state.currentChannel === channel.id ? 'active' : ''}`;
            item.dataset.id = channel.id;
            item.innerHTML = `<i class="fas fa-hashtag"></i> <span>${channel.name}</span>`;
            item.addEventListener('click', () => selectChannel(channel.id));
            channelsList.appendChild(item);
        });
    };

    const renderDMs = () => {
        dmList.innerHTML = '';
    };

    const renderMessages = (messages) => {
        messagesContainer.innerHTML = '<div class="messages-list"></div>';
        const messagesList = messagesContainer.querySelector('.messages-list');
        messages.forEach(msg => {
            const messageEl = createMessageElement(msg);
            messagesList.appendChild(messageEl);
        });
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    const createMessageElement = (msg) => {
        const el = document.createElement('div');
        el.className = 'message';
        el.innerHTML = `
            <div class="message-avatar">${msg.sender_username.charAt(0).toUpperCase()}</div>
            <div class="message-content">
                <div class="message-header">
                    <span class="message-author">${msg.sender_username}</span>
                    <span class="message-time">${new Date(msg.sent_at).toLocaleTimeString()}</span>
                </div>
                <div class="message-text">${msg.content}</div>
            </div>
        `;
        return el;
    };

    const initApp = () => {
        if (state.token && state.user) {
            loginModal.classList.add('hidden');
            app.classList.remove('hidden');
            currentUsernameEl.textContent = state.user.username;
            loadInitialData();
            connectWebSocket();
        } else {
            showLogin();
        }
    };

    const showLogin = () => {
        loginModal.classList.remove('hidden');
        app.classList.add('hidden');
    };

    const loadInitialData = async () => {
        try {
            const [channels, userChannels] = await Promise.all([
                apiRequest('/channels'),
                apiRequest('/user/channels')
            ]);
            state.channels = [...channels, ...userChannels.filter(uc => !channels.some(c => c.id === uc.id))];
            renderChannels();
            renderDMs();
        } catch (error) {
            console.error('Failed to load initial data', error);
        }
    };

    const selectChannel = async (channelId) => {
        state.currentChannel = channelId;
        state.currentDM = null;
        renderChannels();
        const channel = state.channels.find(c => c.id === channelId);
        chatTitle.textContent = channel.name;
        chatDescription.textContent = channel.description || 'No description';
        messageInput.classList.remove('hidden');

        try {
            const messages = await apiRequest(`/channels/${channelId}/messages`);
            renderMessages(messages);
        } catch (error) {
            console.error(`Failed to load messages for channel ${channelId}`, error);
        }
    };

    const handleSendMessage = () => {
        const content = messageText.value.trim();
        if (!content) return;

        if (state.websocket && state.websocket.readyState === WebSocket.OPEN) {
            const message = {
                type: 'message',
                content,
                channel_id: state.currentChannel,
                receiver_id: state.currentDM
            };
            state.websocket.send(JSON.stringify(message));
            messageText.value = '';
        }
    };

    const connectWebSocket = () => {
        state.websocket = new WebSocket(WS_URL);

        state.websocket.onopen = () => {
            console.log('WebSocket connected');
            state.websocket.send(JSON.stringify({ type: 'auth', token: state.token }));
        };

        state.websocket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            handleWebSocketMessage(data);
        };

        state.websocket.onclose = () => {
            console.log('WebSocket disconnected');
        };

        state.websocket.onerror = (error) => {
            console.error('WebSocket error:', error);
            showNotification('WebSocket connection error', 'error');
        };
    };

    const handleWebSocketMessage = (data) => {
        switch (data.type) {
            case 'auth_status':
                if (!data.success) {
                    showNotification('WebSocket authentication failed', 'error');
                    handleLogout();
                }
                break;
            case 'new_message':
                if (data.channel_id === state.currentChannel || 
                    (data.receiver_id === state.user.id && data.sender_id === state.currentDM) ||
                    (data.sender_id === state.user.id && data.receiver_id === state.currentDM)) {
                    const messageEl = createMessageElement(data);
                    messagesContainer.querySelector('.messages-list').appendChild(messageEl);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                } else {
                    showNotification(`New message in ${data.channel_id ? 'channel' : 'DM'}`, 'info');
                }
                break;
            case 'channel_event':
                showNotification(`User ${data.user_id} ${data.event} channel ${data.channel_id}`, 'info');
                loadInitialData();
                break;
            case 'user_status_update':
                // ...
                break;
        }
    };

    loginForm.addEventListener('submit', handleLogin);
    registerForm.addEventListener('submit', handleRegister);

    logoutBtn.addEventListener('click', handleLogout);
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    sendBtn.addEventListener('click', handleSendMessage);
    messageText.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    });

    createChannelBtn.addEventListener('click', () => createChannelModal.classList.remove('hidden'));
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modal;
            document.getElementById(modalId).classList.add('hidden');
        });
    });

    createChannelForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = createChannelForm.channelName.value;
        const description = createChannelForm.channelDescription.value;
        const isPrivate = createChannelForm.channelPrivate.checked;

        try {
            const data = await apiRequest('/channels', 'POST', { name, description, is_private: isPrivate });
            if (data.success) {
                showNotification('Channel created successfully', 'success');
                createChannelModal.classList.add('hidden');
                loadInitialData();
                selectChannel(data.channel_id);
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            // ...
        }
    });

    initApp();
});



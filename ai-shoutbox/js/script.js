document.addEventListener('DOMContentLoaded', () => {
    // Elementų nuorodos
    const loginView = document.getElementById('login-view');
    const messageFormView = document.getElementById('message-form-view');
    const loginButton = document.getElementById('login-button');
    const nameInput = document.getElementById('name-input');
    const messagesDiv = document.getElementById('messages');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const aiButton = document.getElementById('ai-button');

    // Kintamieji būsenai saugoti
    let userName = '';
    let isAIMode = false;
    let lastMessageID = 0;
    let isFetching = false;

    function appendMessageToDOM(sender, text, type) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('message');
        const senderClass = type === 'ai' ? 'ai-sender' : 'sender';
        const senderSpan = document.createElement('span');
        senderSpan.className = senderClass;
        senderSpan.textContent = `${sender}: `;
        const messageText = document.createTextNode(text);
        messageElement.appendChild(senderSpan);
        messageElement.appendChild(messageText);
        messagesDiv.appendChild(messageElement);
        if (messagesDiv.scrollHeight - messagesDiv.clientHeight <= messagesDiv.scrollTop + 50) {
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
    }

    async function fetchNewMessages() {
        if (isFetching) return;
        isFetching = true;

        const formData = new URLSearchParams();
        formData.append('action', 'get_messages');
        formData.append('last_id', lastMessageID);
        formData.append('security', shoutbox_settings.nonce);

        try {
            const response = await fetch(shoutbox_settings.ajax_url, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success && result.data.length > 0) {
                result.data.forEach(msg => {
                    appendMessageToDOM(msg.sender_name, msg.message_text, msg.message_type);
                    lastMessageID = Math.max(lastMessageID, msg.id);
                });
            }
        } catch (error) {
            console.error('Error fetching new messages:', error);
        } finally {
            isFetching = false;
        }
    }

    async function fetchInitialHistory() {
        lastMessageID = 0;
        messagesDiv.innerHTML = '';
        // Panaudojame tą pačią funkciją, bet su pradiniais nustatymais
        const formData = new URLSearchParams();
        formData.append('action', 'get_messages');
        formData.append('last_id', lastMessageID);
        formData.append('security', shoutbox_settings.nonce);
        try {
            const response = await fetch(shoutbox_settings.ajax_url, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success && result.data.length > 0) {
                result.data.forEach(msg => {
                    appendMessageToDOM(msg.sender_name, msg.message_text, msg.message_type);
                    lastMessageID = Math.max(lastMessageID, msg.id);
                });
            }
        } catch(e) {
            console.error("Could not fetch initial history");
        }
    }

    async function handleLogin() {
        const name = nameInput.value.trim();
        if (!name) {
            alert(shoutbox_settings.text.enter_name);
            return;
        }
        loginButton.disabled = true;
        loginButton.textContent = shoutbox_settings.text.checking;
        try {
            const token = await grecaptcha.execute(shoutbox_settings.recaptcha_site_key, { action: 'login' });
            
            const formData = new URLSearchParams();
            formData.append('action', 'shoutbox_login');
            formData.append('token', token);
            formData.append('security', shoutbox_settings.nonce);

            const response = await fetch(shoutbox_settings.ajax_url, { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`Server Error: ${response.status}`);
            
            const result = await response.json();
            if (result.success) {
                userName = name;
                const sessionData = { name: userName, timestamp: new Date().getTime() };
                localStorage.setItem('shoutbox_session', JSON.stringify(sessionData));
                loginView.classList.add('hidden');
                messageFormView.classList.remove('hidden');
            } else {
                alert((result.data && result.data.message) || shoutbox_settings.text.confirmation_failed);
            }
        } catch (error) {
            console.error('Login Error:', error);
            alert(shoutbox_settings.text.login_error);
        } finally {
            loginButton.disabled = false;
            loginButton.textContent = shoutbox_settings.text.join;
        }
    }

    async function sendMessage() {
        const text = messageInput.value.trim();
        if (!text || !userName) return;
        
        sendButton.disabled = true;
        const formData = new URLSearchParams();
        formData.append('action', 'post_message');
        formData.append('sender', userName);
        formData.append('text', text);
        formData.append('isAI', isAIMode);
        formData.append('security', shoutbox_settings.nonce);
        
        messageInput.value = '';
        messageInput.focus();

        try {
            await fetch(shoutbox_settings.ajax_url, { method: 'POST', body: formData });
            // Po sėkmingo išsiuntimo, iškart patikriname naujas žinutes
            await fetchNewMessages();
        } catch (error) {
            console.error('Error sending message:', error);
            alert(shoutbox_settings.text.sending_error);
        } finally {
            sendButton.disabled = false;
        }
    }
    
    function checkSession() {
        const sessionDataString = localStorage.getItem('shoutbox_session');
        if (sessionDataString) {
            const sessionData = JSON.parse(sessionDataString);
            const now = new Date().getTime();
            const sessionAge = now - sessionData.timestamp;
            if (sessionAge < 3600000) { // 1 valanda
                userName = sessionData.name;
                loginView.classList.add('hidden');
                messageFormView.classList.remove('hidden');
            } else {
                localStorage.removeItem('shoutbox_session');
            }
        }
    }

    // --- PRADINIAI VEIKSMAI IR ĮVYKIŲ KLAUSYTOJAI ---
    checkSession();
    fetchInitialHistory();
    setInterval(fetchNewMessages, 5000);

    loginButton.addEventListener('click', handleLogin);
    sendButton.addEventListener('click', sendMessage);

    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    aiButton.addEventListener('click', () => {
        isAIMode = !isAIMode;
        aiButton.classList.toggle('active', isAIMode);
        messageInput.placeholder = isAIMode ? shoutbox_settings.text.ask_ai : shoutbox_settings.text.write_message;
    });
});
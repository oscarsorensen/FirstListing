<!-- Floating chat widget — included in index.php, how.php, helps.php, user.php -->
<div id="chat-bubble">

    <!-- Toggle button shown in the bottom-right corner -->
    <button id="chat-toggle" class="chat-toggle-btn" aria-label="Open chat">
        <!-- Chat icon SVG -->
        <svg id="chat-icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <!-- Close icon SVG (hidden by default) -->
        <svg id="chat-icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="display:none;">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
    </button>

    <!-- Chat panel (hidden until the button is clicked) -->
    <div id="chat-panel" class="chat-panel" style="display:none;">
        <div class="chat-header">
            <div class="chat-header-left">
                <span class="chat-dot"></span>
                <span class="chat-title">Ask about FirstListing</span>
            </div>
            <span class="chat-sub">Powered by GPT-4.1-mini</span>
        </div>

        <!-- Message list — starts with a greeting from the AI -->
        <div id="chat-messages" class="chat-messages">
            <div class="chat-msg chat-msg-ai">
                Hi! I can answer questions about how FirstListing works — crawling, AI extraction, duplicate detection, and more.
            </div>
        </div>

        <!-- Message input form -->
        <form id="chat-form" class="chat-input-row">
            <input
                type="text"
                id="chat-input"
                class="chat-input"
                placeholder="Ask something..."
                autocomplete="off"
                maxlength="500"
            >
            <button type="submit" class="chat-send-btn" id="chat-send">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </form>
    </div>

</div>

<script>
// Chat widget logic — toggle panel, send messages, render replies

const chatToggle   = document.getElementById('chat-toggle');
const chatPanel    = document.getElementById('chat-panel');
const chatMessages = document.getElementById('chat-messages');
const chatForm     = document.getElementById('chat-form');
const chatInput    = document.getElementById('chat-input');
const chatSend     = document.getElementById('chat-send');
const iconOpen     = document.getElementById('chat-icon-open');
const iconClose    = document.getElementById('chat-icon-close');

// Track whether the panel is open
let panelOpen = false;

// Toggle the chat panel open/closed
chatToggle.addEventListener('click', function () {
    panelOpen = !panelOpen;
    chatPanel.style.display = panelOpen ? 'flex' : 'none';
    iconOpen.style.display  = panelOpen ? 'none'  : 'inline';
    iconClose.style.display = panelOpen ? 'inline': 'none';

    // Focus the input when the panel opens
    if (panelOpen) {
        chatInput.focus();
    }
});

// Scroll the message list to the bottom
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Add a message bubble to the message list
// role: 'user' or 'ai'
function addMessage(text, role) {
    const div = document.createElement('div');
    div.className = 'chat-msg chat-msg-' + role;
    div.textContent = text;
    chatMessages.appendChild(div);
    scrollToBottom();
}

// Add a loading bubble while waiting for the AI reply
function addLoadingBubble() {
    const div = document.createElement('div');
    div.className = 'chat-msg chat-msg-ai chat-msg-loading';
    div.id = 'chat-loading-bubble';
    div.textContent = '...';
    chatMessages.appendChild(div);
    scrollToBottom();
    return div;
}

// Handle form submit — send the message to chat.php and show the reply
chatForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const message = chatInput.value.trim();
    if (!message) return;

    // Show the user's message and clear the input
    addMessage(message, 'user');
    chatInput.value = '';
    chatSend.disabled = true;

    // Show a loading indicator while we wait
    const loadingBubble = addLoadingBubble();

    try {
        const body = new URLSearchParams();
        body.set('message', message);

        const res = await fetch('chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: body.toString()
        });

        const data = await res.json();

        // Remove the loading bubble and show the real reply
        loadingBubble.remove();
        addMessage(data.reply || data.error || 'No response.', 'ai');

    } catch (err) {
        loadingBubble.remove();
        addMessage('Something went wrong. Try again.', 'ai');
    }

    chatSend.disabled = false;
    chatInput.focus();
});
</script>

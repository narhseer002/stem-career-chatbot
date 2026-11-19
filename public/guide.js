const form = document.querySelector('#chat-form');
const input = document.querySelector('#question');
const messages = document.querySelector('#messages');
const thread = document.querySelector('#thread');
const welcome = document.querySelector('#welcome');
const followups = document.querySelector('#followups');
const status = document.querySelector('#status');
const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
};
let busy = false;

function addMessage(text, author) {
    const message = document.createElement('div');
    message.className = 'message ' + author;
    const speaker = document.createElement('span');
    speaker.className = 'speaker';
    speaker.textContent = author === 'user' ? 'You' : 'Career guide';
    const body = document.createElement('div');
    body.className = 'message-text';
    body.textContent = text;
    message.append(speaker, body);
    thread.append(message);
}

function formatRecommendations(body) {
    if (body.dataset.formatted || !/^1\. /m.test(body.textContent)) return;

    const blocks = body.textContent.split('\n\n');
    const list = document.createElement('ol');
    list.className = 'recommendations';
    for (const block of blocks) {
        const match = block.match(/^(\d+)\.\s+([^\n]+)\n([\s\S]+)/);
        if (match) {
            const card = document.createElement('li');
            const title = document.createElement('h3');
            title.textContent = match[2];
            const details = document.createElement('p');
            details.textContent = match[3];
            card.append(title, details);
            list.append(card);
        }
    }
    if (!list.children.length) return;

    const introduction = document.createElement('p');
    introduction.textContent = blocks[0];
    const nextStep = document.createElement('p');
    nextStep.textContent = blocks.at(-1);
    body.replaceChildren(introduction, list, nextStep);
    body.dataset.formatted = 'true';
}

function updateConversation() {
    const lastAnswer = thread.querySelector('.bot:last-child .message-text');
    welcome.hidden = Boolean(lastAnswer);
    followups.replaceChildren();

    if (lastAnswer && (lastAnswer.dataset.formatted || /^1\./m.test(lastAnswer.textContent))) {
        for (const label of ['Skills for option 1', 'Project for option 1', 'Compare options 1 and 2']) {
            if (label.startsWith('Compare') && !(lastAnswer.querySelectorAll('li').length > 1 || /^2\./m.test(lastAnswer.textContent))) continue;
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.message = label;
            button.textContent = label;
            followups.append(button);
        }
    }

    thread.querySelectorAll('.bot .message-text').forEach(formatRecommendations);
    messages.scrollTop = messages.scrollHeight;
}

function setBusy(value, text = '') {
    busy = value;
    document.querySelectorAll('button').forEach(button => { button.disabled = value; });
    input.readOnly = value;
    status.textContent = text;
}

async function send(text) {
    if (!text || busy) return;

    setBusy(true, 'Preparing an answer…');
    let notice = '';
    try {
        const response = await fetch('/chat', {
            method: 'POST',
            headers,
            body: JSON.stringify({ message: text }),
        });
        if (!response.ok) throw new Error(response.status === 419 ? 'session' : 'request');
        const { reply } = await response.json();
        addMessage(text, 'user');
        addMessage(reply, 'bot');
        input.value = '';
        updateConversation();
    } catch (error) {
        input.value = text;
        notice = error.message === 'session'
            ? 'Your session expired. Refresh the page and try again.'
            : 'Could not send your message. Please try again.';
    } finally {
        setBusy(false, notice);
    }
}

form.addEventListener('submit', event => {
    event.preventDefault();
    send(input.value.trim());
});

document.addEventListener('click', event => {
    const button = event.target.closest('[data-message]');
    if (button) send(button.dataset.message);
});

document.querySelector('#reset').addEventListener('click', async () => {
    if (busy) return;
    setBusy(true);
    let notice = '';
    try {
        const response = await fetch('/chat', { method: 'DELETE', headers });
        if (!response.ok) throw new Error('Reset failed');
        thread.querySelectorAll('.message').forEach(message => message.remove());
        input.value = '';
        updateConversation();
    } catch {
        notice = 'Could not reset the conversation. Please try again.';
    } finally {
        setBusy(false, notice);
    }
});

updateConversation();

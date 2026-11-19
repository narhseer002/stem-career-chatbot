<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, interactive-widget=resizes-content">
    <meta name="theme-color" content="#191629">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>STEM Pathways | Career Guide</title>
    <link rel="stylesheet" href="{{ asset('guide.css') }}">
    <script src="{{ asset('guide.js') }}" defer></script>
</head>
<body>
    <div class="workspace">
        <aside class="sidebar" aria-label="Career guide">
            <div class="brand">
                <span class="brand-icon" aria-hidden="true">✳</span>
                <span>STEM Pathways</span>
            </div>
            <div class="sidebar-content">
                <h1>Find your<br> STEM path.</h1>
                <p>Explore careers that connect what you enjoy studying with the work you could do.</p>
            </div>
            <div class="sidebar-examples">
                <h2>Conversation starters</h2>
                <button type="button" data-message="Help me choose">Find my career matches</button>
                <button type="button" data-message="I enjoy biology, statistics and coding">Explore my interests</button>
                <button type="button" data-message="Compare Data Analyst and Data Scientist">Compare two careers</button>
            </div>
        </aside>

        <main class="chat" aria-label="Career guidance chat">
            <header class="chat-heading">
                <div>
                    <h2>Career guide</h2>
                    <p>STEM career exploration</p>
                </div>
                <button id="reset" class="secondary" type="button"><span aria-hidden="true">+</span> New chat</button>
            </header>
            <div id="messages" role="log" aria-label="Conversation" tabindex="0">
                <div class="thread" id="thread">
                    <section id="welcome">
                        <span class="welcome-icon" aria-hidden="true">✳</span>
                        <h2>What are you curious about?</h2>
                        <p>Ask about a STEM career or tell me which subjects and activities you enjoy.</p>
                        <div class="quick-actions">
                            <button type="button" data-message="Help me choose">Find my matches</button>
                            <button type="button" data-message="What can I do with a mathematics degree?">Careers in mathematics</button>
                            <button type="button" data-message="What skills do I need to become an Electrical Engineer?">Skills for engineering</button>
                        </div>
                    </section>
                    @foreach (session('guide_transcript', []) as $entry)
                        <div class="message {{ $entry['author'] }}">
                            <span class="speaker">{{ $entry['author'] === 'user' ? 'You' : 'Career guide' }}</span>
                            <div class="message-text">{{ $entry['text'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <footer class="composer">
                <div class="composer-inner">
                    <div id="followups" class="quick-actions" aria-label="Suggested follow-ups"></div>
                    <form id="chat-form">
                        <label class="visually-hidden" for="question">Your message</label>
                        <input id="question" maxlength="600" placeholder="Ask about a career, skill or course…" autocomplete="off" required>
                        <button id="send" type="submit">Send</button>
                    </form>
                    <p id="status" role="status"></p>
                </div>
            </footer>
        </main>
    </div>
</body>
</html>

<?php require '../inc.php';guard('admin');$me=(int)$_SESSION['uid'];
// Lightweight AJAX endpoints keep the conversation live without reloading the page.
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $peer = (int)($_GET['with'] ?? $_POST['to'] ?? 0);
    if ($_GET['ajax'] === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = trim((string)($_POST['body'] ?? ''));
        if ($peer < 1 || $body === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Message and recipient are required']); exit; }
        $result = api('messaging','send',[],['to'=>$peer,'body'=>$body]);
        echo json_encode(['ok'=>true,'result'=>$result]); exit;
    }
    if ($_GET['ajax'] === 'messages' && $peer > 0) {
        $messages = api('messaging','conversation',['with'=>$peer]);
        if (!is_array($messages) || isset($messages['error'])) $messages = [];
        api('messaging','mark-read',[],['with'=>$peer]);
        echo json_encode(['ok'=>true,'messages'=>$messages]); exit;
    }
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid inbox request']); exit;
}
if($_POST){api('messaging','send',[],['to'=>$_POST['to'],'body'=>$_POST['body']]);header('Location: inbox.php?with='.(int)$_POST['to']);exit;}
$th=[];foreach(api('messaging','inbox') as $x){$th[$x['sender_id']==$me?$x['receiver_id']:$x['sender_id']][]=$x;}
$nm=[];foreach(api('user','list',['role'=>'student']) as $u)$nm[$u['user_id']]=$u['first_name'].' '.$u['last_name'];foreach(api('user','list',['role'=>'professor']) as $u)$nm[$u['user_id']]='Prof. '.$u['first_name'].' '.$u['last_name'];
$ct=$nm+array_fill_keys(array_keys($th),'');$with=(int)($_GET['with']??array_key_first($ct)??0);if($with>0){api('messaging','mark-read',[],['with'=>$with]);}top('Inbox','inbox');?>

<style>
/* Inbox / Messaging Page Styling matching design */
.inbox-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: calc(100vh - 70px);
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.inbox-layout {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 24px;
    height: calc(100vh - 140px);
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}

/* Left Sidebar: Conversations List */
.inbox-sidebar {
    border-right: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    overflow: hidden;
}

.sidebar-header-box {
    padding: 20px 20px 12px 20px;
}

.sidebar-header-box h2 {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
}

.search-messages-input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    background: #f8fafc;
    outline: none;
    box-sizing: border-box;
}

.conversations-scroll-area {
    flex: 1;
    overflow-y: auto;
    padding: 0 12px 12px 12px;
}

.convo-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    margin-bottom: 4px;
    transition: background 0.2s;
}

.convo-item:hover {
    background: #f8fafc;
}

.convo-item.on {
    background: #f0fdf4;
}

.convo-avatar {
    width: 40px;
    height: 40px;
    background: #15803d;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.convo-details {
    flex: 1;
    min-width: 0;
}

.convo-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Right Chat Area */
.chat-main-pane {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    overflow: hidden;
}

.chat-header-banner {
    padding: 16px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-messages-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: #ffffff;
}

/* Message Bubbles */
.msg-bubble-row {
    display: flex;
    flex-direction: column;
    max-width: 65%;
}

.msg-bubble-row.received {
    align-self: flex-start;
}

.msg-bubble-row.sent {
    align-self: flex-end;
}

.msg-bubble {
    padding: 12px 16px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
}

.msg-bubble-row.received .msg-bubble {
    background: #f1f5f9;
    color: #1e293b;
    border-bottom-left-radius: 4px;
}

.msg-bubble-row.sent .msg-bubble {
    background: #15803d;
    color: #ffffff;
    border-bottom-right-radius: 4px;
}

.msg-timestamp {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
    padding: 0 4px;
}

.msg-bubble-row.sent .msg-timestamp {
    text-align: right;
}

/* Chat Input Form Bar */
.chat-input-footer {
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
}

.chat-input-row {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 12px;
}

.chat-input-row input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 14px;
    outline: none;
    color: #1e293b;
}

.chat-send-btn {
    background: #15803d;
    color: #ffffff;
    border: none;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.chat-send-btn:hover {
    background: #166534;
}

@media(max-width: 900px) {
    .inbox-layout { grid-template-columns: 1fr; }
    .inbox-sidebar { display: none; }
}
</style>

<div class="inbox-container">
    <div class="inbox-layout">
        <!-- Left Sidebar: Conversations -->
        <div class="inbox-sidebar">
            <div class="sidebar-header-box">
                <h2>Inbox</h2>
                <input type="text" class="search-messages-input" placeholder="Search messages...">
            </div>
            <div class="conversations-scroll-area">
                <?php foreach($ct as $id=>$n): 
                    $isActive = ($id == $with);
                    $displayName = $n ?: 'User #'.$id;
                ?>
                <a class="convo-item <?= $isActive ? 'on' : '' ?>" href="?with=<?=$id?>">
                    <div class="convo-avatar">
                        <?= strtoupper(substr($displayName, 0, 1)) ?>
                    </div>
                    <div class="convo-details">
                        <div class="convo-name"><?=h($displayName)?></div>
                    </div>
                </a>
                <?php endforeach;?>
            </div>
        </div>

        <!-- Right Main Chat Pane -->
        <div class="chat-main-pane">
            <?php if($with): 
                $activeName = $ct[$with] ?? ('User #'.$with);
            ?>
            <div class="chat-header-banner">
                <div class="chat-header-profile">
                    <div class="convo-avatar" style="width:36px; height:36px; font-size:13px;">
                        <?= strtoupper(substr($activeName, 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:14px; color:#0f172a;"><?=h($activeName)?></div>
                    </div>
                </div>
            </div>

            <div class="chat-messages-scroll" data-current-user="<?=$me?>">
                <?php foreach($th[$with]??[] as $m): 
                    $isMe = ($m['sender_id'] == $me);
                ?>
                <div class="msg-bubble-row <?= $isMe ? 'sent' : 'received' ?>" data-message-id="<?= (int)($m['message_id'] ?? 0) ?>">
                    <div class="msg-bubble">
                        <?=h($m['body'])?>
                    </div>
                    <div class="msg-timestamp"><?=h(substr($m['sent_at'],11,5))?></div>
                </div>
                <?php endforeach;?>
            </div>

            <div class="chat-input-footer">
                <form method="post" class="chat-input-row" id="chatSendForm">
                    <input type="hidden" name="to" value="<?=$with?>">
                    <input name="body" placeholder="Type a message..." required autocomplete="off">
                    <button class="chat-send-btn" type="submit">Send</button>
                </form>
            </div>
            <?php endif;?>
        </div>
    </div>
</div>

<script>
(() => {
  const pane = document.querySelector('.chat-messages-scroll');
  const form = document.getElementById('chatSendForm');
  if (!pane || !form) return;
  const input = form.querySelector('[name="body"]');
  const sendButton = form.querySelector('button[type="submit"], .chat-send-btn');
  const peer = form.querySelector('[name="to"]')?.value;
  window.LMS_CURRENT_USER_ID = pane.dataset.currentUser;
  const endpoint = new URL(window.location.pathname, window.location.origin);
  endpoint.searchParams.set('with', peer);
  const nearBottom = () => pane.scrollHeight - pane.scrollTop - pane.clientHeight < 90;
  const scrollBottom = () => { pane.scrollTop = pane.scrollHeight; };
  const knownIds = new Set(Array.from(pane.querySelectorAll('[data-message-id]')).map(n => n.dataset.messageId).filter(id => id && id !== '0'));
  const escapeText = value => String(value ?? '');
  function appendMessage(m) {
    const id = String(m.message_id ?? '');
    if (id && knownIds.has(id)) return;
    const wasAtBottom = nearBottom();
    const row = document.createElement('div');
    row.className = 'msg-bubble-row ' + (Number(m.sender_id) === Number(window.LMS_CURRENT_USER_ID) ? 'sent' : 'received');
    if (id) { row.dataset.messageId = id; knownIds.add(id); }
    const bubble = document.createElement('div'); bubble.className = 'msg-bubble'; bubble.textContent = escapeText(m.body);
    const time = document.createElement('div'); time.className = 'msg-timestamp';
    const stamp = String(m.sent_at ?? ''); time.textContent = stamp.length >= 16 ? stamp.slice(11,16) : '';
    row.append(bubble, time); pane.appendChild(row);
    if (wasAtBottom) scrollBottom();
  }
  let refreshInProgress = false;
  async function refreshMessages() {
    if (refreshInProgress || document.visibilityState !== 'visible') return;
    refreshInProgress = true;
    try {
      const url = new URL(endpoint); url.searchParams.set('ajax','messages');
      const response = await fetch(url, {credentials:'same-origin', cache:'no-store', headers:{'Accept':'application/json'}});
      if (!response.ok) return;
      const data = await response.json(); if (!data.ok || !Array.isArray(data.messages)) return;
      data.messages.forEach(appendMessage);
    } catch (_) { /* transient polling errors are retried on the next interval */ }
    finally { refreshInProgress = false; }
  }
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const body = input.value.trim(); if (!body || !peer) return;
    if (sendButton) sendButton.disabled = true;
    const payload = new URLSearchParams({to:peer, body});
    try {
      const url = new URL(endpoint); url.searchParams.set('ajax','send');
      const response = await fetch(url, {method:'POST', credentials:'same-origin', cache:'no-store', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'}, body:payload.toString()});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.error || 'Message could not be sent');
      input.value = '';
      await refreshMessages();
      input.focus();
    } catch (error) {
      alert(error.message || 'Could not send message. Please try again.');
    } finally { if (sendButton) sendButton.disabled = false; }
  });
  // Initial bottom alignment, then refresh incoming messages without page reload.
  scrollBottom();
  refreshMessages();
  // Reduce repeated message polling load while keeping inbox updates responsive.
  window.setInterval(refreshMessages, 15000);
  document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refreshMessages(); });
})();
</script>
<?php bottom();?>
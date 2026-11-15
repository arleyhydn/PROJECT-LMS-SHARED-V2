<?php
require __DIR__ . '/inc.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'professor', 'student'], true)) {
    header('Location: ' . BASE . '/web/index.php');
    exit;
}
guard($role);

$resetUsers = [];
if ($role === 'admin') {
    foreach (['admin', 'professor', 'student'] as $userRole) {
        $users = api('user', 'list', ['role' => $userRole]);
        if (is_array($users) && !isset($users['error'])) {
            foreach ($users as $user) $resetUsers[] = $user;
        }
    }
    usort($resetUsers, static fn($left, $right) => strcasecmp($left['last_name'] ?? '', $right['last_name'] ?? ''));
}

top('Help Center', 'settings');
?>
<div class="page-heading">
    <h1>Help Center</h1>
    <p class="sub">Guides and support for using the FEU TED Learning Portal.</p>
</div>
<div class="help-center-layout">
    <section class="card help-walkthrough-card">
        <div>
            <span class="help-center-eyebrow">Learning the portal</span>
            <h2>Interactive walkthrough</h2>
            <p>Review the main pages and tools available to your account.</p>
        </div>
        <button class="assignment-publish-button" id="restartWalkthrough" type="button">Start Walkthrough Guide</button>
        <p class="help-center-status" id="helpCenterStatus" role="status" aria-live="polite"></p>
    </section>

    <?php if ($role === 'admin'): ?>
        <section class="card help-admin-reset-card">
            <div>
                <span class="help-center-eyebrow">Administrator</span>
                <h2>Reset a user's walkthrough</h2>
                <p>Reset onboarding progress for a user who needs to repeat the required guide.</p>
            </div>
            <form id="resetUserWalkthrough" class="help-reset-form">
                <label for="walkthroughUser">User</label>
                <select id="walkthroughUser" name="user_id" required>
                    <option value="">Select a user</option>
                    <?php foreach ($resetUsers as $user): ?>
                        <option value="<?= (int)$user['user_id'] ?>"><?= h(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?> · <?= h(ucfirst($user['role'] ?? 'user')) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="help-reset-button" type="submit">Reset walkthrough</button>
            </form>
        </section>
    <?php endif; ?>
</div>
<style>
.help-center-layout{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,360px),1fr));gap:18px;max-width:980px}
.help-walkthrough-card,.help-admin-reset-card{display:grid;align-content:start;gap:18px;margin:0;padding:24px;border:1px solid var(--bd);border-radius:16px;background:var(--card);box-shadow:0 5px 20px rgba(15,23,42,.04)}
.help-walkthrough-card h2,.help-admin-reset-card h2{margin:8px 0 6px;color:var(--ink);font-size:18px}
.help-walkthrough-card p,.help-admin-reset-card p{margin:0;color:var(--mut);font-size:13px;line-height:1.6}
.help-center-eyebrow{color:var(--g);font-size:11px;font-weight:750;text-transform:uppercase}
.help-center-status{min-height:18px}
.help-reset-form{display:grid;gap:10px}
.help-reset-form label{color:var(--ink);font-size:12px;font-weight:650}
.help-reset-form select{min-height:42px;padding:9px 11px;border:1px solid var(--bd);border-radius:10px;background:var(--card);color:var(--ink);font:inherit;font-size:13px}
.help-reset-button{min-height:40px;padding:9px 13px;border:1px solid var(--bd);border-radius:10px;background:var(--bg);color:var(--ink);font:inherit;font-size:12px;font-weight:700;cursor:pointer}
.help-reset-button:hover{border-color:var(--g);color:var(--g)}
body.dark .help-walkthrough-card,body.dark .help-admin-reset-card{background:var(--card);border-color:var(--bd);box-shadow:0 8px 24px rgba(0,0,0,.16)}
@media(max-width:640px){.help-walkthrough-card,.help-admin-reset-card{padding:18px}}
</style>
<script>
const walkthroughActionUrl = <?= json_encode(BASE . '/web/onboarding_action.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const dashboardUrl = <?= json_encode(BASE . '/web/' . $role . '/dashboard.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const helpStatus = document.getElementById('helpCenterStatus');

document.getElementById('restartWalkthrough')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    if (helpStatus) helpStatus.textContent = 'Preparing walkthrough…';
    try {
        const response = await fetch(walkthroughActionUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: new URLSearchParams({ action: 'restart' })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'Could not start the walkthrough.');
        window.location.assign(dashboardUrl);
    } catch (error) {
        if (helpStatus) helpStatus.textContent = error.message;
        button.disabled = false;
    }
});

document.getElementById('resetUserWalkthrough')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    if (helpStatus) helpStatus.textContent = 'Resetting walkthrough…';
    try {
        const body = new URLSearchParams(new FormData(form));
        body.set('action', 'reset_user');
        const response = await fetch(walkthroughActionUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'Could not reset the walkthrough.');
        if (helpStatus) helpStatus.textContent = 'Walkthrough progress reset.';
    } catch (error) {
        if (helpStatus) helpStatus.textContent = error.message;
    } finally {
        button.disabled = false;
    }
});
</script>
<?php bottom(); ?>

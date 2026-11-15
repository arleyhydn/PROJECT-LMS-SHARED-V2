<?php require '../inc.php';guard('admin');$st=api('user','list',['role'=>'student']);$pr=api('user','list',['role'=>'professor']);$cn=api('course','count')['n']??0;$pe=api('enrollment','pending')['n']??0;top('Admin Dashboard','dashboard');?>

<style>
/* Admin Dashboard Page Styling matching reference design */
.admin-dashboard-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.admin-header {
    margin-bottom: 24px;
}

.admin-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.admin-header p.sub {
    font-size: 14px;
    color: #64748b;
}

/* Top Stat Cards Grid */
.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.admin-stat-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 120px;
}

.admin-stat-card.c1 { background-color: #f0fdf4; border-color: #dcfce7; }
.admin-stat-card.c2 { background-color: #f0fdf4; border-color: #dcfce7; }
.admin-stat-card.c3 { background-color: #faf5ff; border-color: #f3e8ff; }
.admin-stat-card.c4 { background-color: #fef2f2; border-color: #fee2e2; }

.admin-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.admin-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 2px;
}

.admin-stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.admin-stat-subtext {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 6px;
}

/* Main Content Grid */
.admin-main-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 24px;
}

.admin-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 24px;
}

.admin-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.admin-card-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.manage-link {
    font-size: 13px;
    color: #15803d;
    font-weight: 600;
    text-decoration: none;
}

.manage-link:hover {
    text-decoration: underline;
}

/* Recent Students List */
.student-row-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}

.student-row-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.student-info-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-avatar {
    width: 36px;
    height: 36px;
    background: #15803d;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}

.student-names h4 {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 2px;
}

.student-names p {
    font-size: 12px;
    color: #64748b;
}

.student-meta-right {
    text-align: right;
}

.student-year {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 2px;
}

.status-tag {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-block;
}

.status-tag.active { background: #dcfce7; color: #166534; }
.status-tag.inactive, .status-tag.off { background: #f1f5f9; color: #64748b; }

/* System Status List */
.system-status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
    font-size: 14px;
    color: #334155;
}

.system-status-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.sys-tag {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
}

.sys-tag.operational { background: #dcfce7; color: #166534; }
.sys-tag.down, .sys-tag.off { background: #fee2e2; color: #dc2626; }

/* Quick Actions Box */
.quick-actions-box {
    background: #15803d;
    border-radius: 16px;
    padding: 24px;
    color: #ffffff;
}

.quick-actions-box h3 {
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 16px;
}

.quick-action-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.quick-action-btn {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 12px 16px;
    border-radius: 12px;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    transition: background 0.2s;
    cursor: pointer;
}

.quick-action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

@media(max-width: 1024px) {
    .admin-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .admin-main-grid { grid-template-columns: 1fr; }
}

body.dark .admin-dashboard-container .feed-type {
    background: #153323 !important;
    border-color: #28543a !important;
    color: #a7e0b7 !important;
}

body.dark .admin-dashboard-container .feed-actions .feed-action {
    background: #172638 !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}

body.dark .admin-dashboard-container .feed-actions .feed-action:hover {
    background: #263449 !important;
    border-color: #475569 !important;
    color: #f1f5f9 !important;
}

body.dark .admin-dashboard-container .feed-actions .feed-action.selected {
    background: #293454 !important;
    border-color: #4f5d91 !important;
    color: #c7d2fe !important;
}

body.dark .admin-dashboard-container .feed-actions .feed-read-action {
    background: #153323 !important;
    border-color: #28543a !important;
    color: #a7e0b7 !important;
}

body.dark .admin-dashboard-container .feed-actions .feed-read-action:hover,
body.dark .admin-dashboard-container .feed-announcement-card.is-read .feed-read-action {
    background: #20392b !important;
    border-color: #4b8b62 !important;
    color: #d1fae5 !important;
}

body.dark .admin-dashboard-container .admin-card {
    background: #111c2e !important;
    border-color: #334155 !important;
    color: #e5e7eb !important;
}

body.dark .admin-dashboard-container .admin-card-header h3,
body.dark .admin-dashboard-container .student-names h4 {
    color: #e5e7eb !important;
}

body.dark .admin-dashboard-container .student-names p,
body.dark .admin-dashboard-container .student-year,
body.dark .admin-dashboard-container .system-status-row {
    color: #b6c2d1 !important;
}

body.dark .admin-dashboard-container .student-row-item,
body.dark .admin-dashboard-container .system-status-row {
    border-color: #334155 !important;
}

body.dark .admin-dashboard-container .status-tag.active,
body.dark .admin-dashboard-container .sys-tag.operational {
    background: #153323 !important;
    color: #a7e0b7 !important;
}

body.dark .admin-dashboard-container .status-tag.inactive,
body.dark .admin-dashboard-container .status-tag.off,
body.dark .admin-dashboard-container .sys-tag.off {
    background: #263449 !important;
    color: #cbd5e1 !important;
}

body.dark .admin-dashboard-container .sys-tag.down {
    background: #42252a !important;
    color: #fda4af !important;
}
</style>

<div class="admin-dashboard-container">
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <p class="sub">System overview · <?=date('F j, Y')?></p>
    </div>

    <!-- Top Metric Cards -->
    <div class="admin-stats-grid">
        <div class="admin-stat-card c1">
            <div class="admin-stat-top">
                <div class="admin-stat-icon" style="background: #dcfce7; color: #16a34a;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <span style="font-size:12px; color:#16a34a; font-weight:700;">↗</span>
            </div>
            <div>
                <div class="admin-stat-number"><?=count($st)?></div>
                <div class="admin-stat-label">Total Students</div>
            </div>
            <div class="admin-stat-subtext">Active enrollment</div>
        </div>

        <div class="admin-stat-card c2">
            <div class="admin-stat-top">
                <div class="admin-stat-icon" style="background: #dcfce7; color: #16a34a;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span style="font-size:12px; color:#16a34a; font-weight:700;">↗</span>
            </div>
            <div>
                <div class="admin-stat-number"><?=count($pr)?></div>
                <div class="admin-stat-label">Total Professors</div>
            </div>
            <div class="admin-stat-subtext">Faculty members</div>
        </div>

        <div class="admin-stat-card c3">
            <div class="admin-stat-top">
                <div class="admin-stat-icon" style="background: #f3e8ff; color: #9333ea;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                </div>
                <span style="font-size:12px; color:#9333ea; font-weight:700;">↗</span>
            </div>
            <div>
                <div class="admin-stat-number"><?=$cn?></div>
                <div class="admin-stat-label">Active Courses</div>
            </div>
            <div class="admin-stat-subtext">This semester</div>
        </div>

        <div class="admin-stat-card c4">
            <div class="admin-stat-top">
                <div class="admin-stat-icon" style="background: #fee2e2; color: #dc2626;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
            </div>
            <div>
                <div class="admin-stat-number"><?=$pe?></div>
                <div class="admin-stat-label">Pending Requests</div>
            </div>
            <div class="admin-stat-subtext" style="color:#dc2626;">Needs attention</div>
        </div>
    </div>

    <?php $dashboardAnnouncements=api('announcement','all'); announcement_feed($dashboardAnnouncements,4); ?>

    <!-- Main Content Layout Grid -->
    <div class="admin-main-grid">
        <!-- Left Column: Recent Students -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Recent Students</h3>
                <a href="users.php" class="manage-link">Manage &rarr;</a>
            </div>
            <div>
                <?php foreach(array_slice($st,0,5) as $u):
                    $isActive = ($u['status'] === 'active');
                ?>
                <div class="student-row-item">
                    <div class="student-info-left">
                        <div class="student-avatar"><?=strtoupper(substr($u['first_name'],0,1))?></div>
                        <div class="student-names">
                            <h4><?=h($u['first_name'].' '.$u['last_name'])?></h4>
                            <p><?=h($u['email'])?></p>
                        </div>
                    </div>
                    <div class="student-meta-right">
                        <div class="student-year">Student</div>
                        <span class="status-tag <?= $isActive ? 'active' : 'off' ?>"><?=h($u['status'])?></span>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>

        <!-- Right Column: System Status & Quick Actions -->
        <div>
            <!-- System Status Widget -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Service health checks</h3>
                </div>
                <p class="system-status-note">Services are checked as you use each LMS feature. This overview loads without waiting on background health checks.</p>
            </div>

            <!-- Quick Actions Box -->
            <div class="quick-actions-box">
                <h3>Quick Actions</h3>
                <div class="quick-action-grid">
                    <a href="users.php?add=1" class="quick-action-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        Register Student
                    </a>
                    <a href="users.php?tab=professor&add=1" class="quick-action-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle><line x1="19" y1="11" x2="23" y2="11"></line></svg>
                        Add Professor
                    </a>
                    <a href="announcements.php?new=1" class="quick-action-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9H9l-3-9H2v-2h20v2zM5 7l7-5 7 5"></path></svg>
                        Post Announcement
                    </a>
                    <a href="settings.php" class="quick-action-btn">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l-.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php bottom();?>
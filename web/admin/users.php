<?php require '../inc.php';guard('admin');
if($_POST){$a=$_POST['do'];if($a==='save')api('user',$_POST['user_id']?'update':'create',[],$_POST);else api('user','delete',[],['user_id'=>$_POST['user_id']]);header('Location: users.php?tab='.$_POST['role']);exit;}
$tab=$_GET['tab']??'student';$all=api('user','list',['role'=>$tab]);$q=strtolower($_GET['q']??'');$rows=array_filter($all,fn($u)=>!$q||str_contains(strtolower($u['first_name'].$u['last_name'].$u['email']),$q));
$ed=['user_id'=>'','first_name'=>'','last_name'=>'','email'=>'','status'=>'active'];foreach($all as $u)if($u['user_id']==($_GET['edit']??0))$ed=$u;top('Users','users');?>

<style>
/* User Management Page Styling matching reference design */
.users-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.users-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.users-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.users-header p.sub {
    font-size: 14px;
    color: #64748b;
}

.btn-add-user {
    background-color: #15803d;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}

.btn-add-user:hover {
    background-color: #166534;
}

/* Controls Bar (Search + Filter Tabs) */
.controls-bar {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-bottom: 24px;
}

.search-wrapper {
    position: relative;
    width: 300px;
}

.search-input {
    width: 100%;
    padding: 10px 14px 10px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    background: #ffffff;
    outline: none;
    color: #1e293b;
    box-sizing: border-box;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.role-tabs-group {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}

.role-tab-btn {
    padding: 8px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    color: #64748b;
    transition: all 0.2s;
}

.role-tab-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

/* User List Table Card */
.users-table-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 16px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

.users-table td {
    padding: 16px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}

.users-table tr:last-child td {
    border-bottom: none;
}

.user-info-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-circle {
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
    flex-shrink: 0;
}

.user-name-text {
    font-weight: 700;
    color: #0f172a;
}

.status-pill-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.status-pill-badge.active { background: #dcfce7; color: #166534; }
.status-pill-badge.off, .status-pill-badge.inactive { background: #f1f5f9; color: #64748b; }

.action-link {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
    font-size: 12px;
    margin-right: 12px;
}

.action-link:hover { text-decoration: underline; }

.btn-delete-link {
    background: transparent;
    border: none;
    color: #dc2626;
    font-weight: 600;
    cursor: pointer;
    font-size: 12px;
    padding: 0;
}

.btn-delete-link:hover { text-decoration: underline; }

/* Modal Form Popup Styling */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
}

.modal-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 32px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 20px;
}

.modal-role-toggle {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.modal-role-btn {
    padding: 10px;
    text-align: center;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    color: #64748b;
}

.modal-role-btn.active {
    background: #15803d;
    color: #ffffff;
    border-color: #15803d;
}

.modal-form-group {
    margin-bottom: 16px;
}

.modal-form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}

.modal-form-group input, .modal-form-group select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
}

.modal-form-group input:focus, .modal-form-group select:focus {
    border-color: #15803d;
}

.modal-actions-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 24px;
}

.btn-cancel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #475569;
    padding: 12px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
}

.btn-save-modal {
    background: #15803d;
    border: none;
    color: #ffffff;
    padding: 12px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}
</style>

<div class="users-container">
    <div class="users-header-row">
        <div class="users-header">
            <h1>User Management</h1>
            <p class="sub"><?=count($all)?> <?=ucfirst($tab)?>s</p>
        </div>
        <a href="?tab=<?=$tab?>&add=1" class="btn-add-user">+ Add User</a>
    </div>

    <!-- Controls Bar -->
    <form method="get" class="controls-bar">
        <input type="hidden" name="tab" value="<?=h($tab)?>">
        <div class="search-wrapper">
            <span class="search-icon"><svg class="ui-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
            <input name="q" class="search-input" placeholder="Search users..." value="<?=h($_GET['q']??'')?>">
        </div>
        <div class="role-tabs-group">
            <a href="?tab=student" class="role-tab-btn <?= $tab==='student'?'active':'' ?>">Students</a>
            <a href="?tab=professor" class="role-tab-btn <?= $tab==='professor'?'active':'' ?>">Professors</a>
        </div>
    </form>

    <!-- Modal Form for Add / Edit -->
    <?php if(isset($_GET['add'])||isset($_GET['edit'])):?>
    <div class="modal-backdrop">
        <div class="modal-card">
            <h2 class="modal-title"><?= isset($_GET['edit']) ? 'Edit User' : 'Register New User' ?></h2>
            
            <div class="modal-role-toggle">
                <a href="?tab=student&<?=isset($_GET['add'])?'add=1':''?>" class="modal-role-btn <?=$tab==='student'?'active':''?>">Student</a>
                <a href="?tab=professor&<?=isset($_GET['add'])?'add=1':''?>" class="modal-role-btn <?=$tab==='professor'?'active':''?>">Professor</a>
            </div>

            <form method="post">
                <input type="hidden" name="do" value="save">
                <input type="hidden" name="role" value="<?=h($tab)?>">
                <input type="hidden" name="user_id" value="<?=h($ed['user_id'])?>">

                <div class="modal-form-group">
                    <label>First Name</label>
                    <input name="first_name" placeholder="First name" value="<?=h($ed['first_name'])?>" required>
                </div>

                <div class="modal-form-group">
                    <label>Last Name</label>
                    <input name="last_name" placeholder="Last name" value="<?=h($ed['last_name'])?>" required>
                </div>

                <div class="modal-form-group">
                    <label>FEU Email</label>
                    <input name="email" type="email" placeholder="Email Address" value="<?=h($ed['email'])?>" required>
                </div>

                <?php if(!$ed['user_id']):?>
                <div class="modal-form-group">
                    <label>Password</label>
                    <input name="password" placeholder="Password (default: password123)">
                </div>
                <?php endif;?>

                <div class="modal-form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?=$ed['status']==='active'?'selected':''?>>active</option>
                        <option value="inactive" <?=$ed['status']==='inactive'?'selected':''?>>inactive</option>
                    </select>
                </div>

                <div class="modal-actions-row">
                    <a href="?tab=<?=$tab?>" class="btn-cancel">Cancel</a>
                    <button class="btn-save-modal" type="submit"><?= isset($_GET['edit']) ? 'Update User' : 'Register User' ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif;?>

    <!-- User Data Table -->
    <div class="users-table-card">
        <table class="users-table">
            <tr>
                <th>NAME</th>
                <th>EMAIL</th>
                <th>PROGRAM</th>
                <th>YEAR</th>
                <th>STATUS</th>
                <th>ACTIONS</th>
            </tr>
            <?php foreach($rows as $u):
                $isActive = ($u['status'] === 'active');
            ?>
            <tr>
                <td>
                    <div class="user-info-cell">
                        <div class="user-avatar-circle"><?=strtoupper(substr($u['first_name'], 0, 1))?></div>
                        <span class="user-name-text"><?=h($u['first_name'].' '.$u['last_name'])?></span>
                    </div>
                </td>
                <td><?=h($u['email'])?></td>
                <td>BSIT</td>
                <td>2nd Year</td>
                <td>
                    <span class="status-pill-badge <?= $isActive ? 'active' : 'off' ?>"><?=h($u['status'])?></span>
                </td>
                <td>
                    <a class="action-link" href="?tab=<?=$tab?>&edit=<?=$u['user_id']?>">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?')">
                        <input type="hidden" name="do" value="del">
                        <input type="hidden" name="role" value="<?=h($tab)?>">
                        <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                        <button class="btn-delete-link" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
        </table>
    </div>
</div>

<?php bottom();?>
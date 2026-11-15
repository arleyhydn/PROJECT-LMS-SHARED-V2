<?php require '../inc.php';guard('student');
if($_POST){isset($_POST['title'])?api('planning','add_todo',[],['title'=>$_POST['title']]):api('planning','toggle_todo',[],['id'=>$_POST['id']]);header('Location: todo.php');exit;}
$t=api('planning','todos');$d=count(array_filter($t,fn($x)=>strcasecmp($x['status'],'completed')===0));$p=$t?round($d/count($t)*100):0;top('To-Do','todo');?>

<style>
/* To-Do Page Styling matching reference design */
.todo-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.todo-header {
    margin-bottom: 24px;
}

.todo-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.todo-header p.sub {
    font-size: 14px;
    color: #64748b;
}

/* Overall Progress Card */
.progress-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.progress-header-text {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.progress-header-text b {
    color: #15803d;
    font-size: 16px;
}

.bar-bg {
    width: 100%;
    height: 8px;
    background-color: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}

.bar-bg i {
    display: block;
    height: 100%;
    background-color: #15803d;
    border-radius: 4px;
    font-style: normal;
    transition: width 0.3s ease;
}

/* Add Task Form Row */
.add-form-row {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.add-form-row input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    background-color: #ffffff;
    outline: none;
    transition: all 0.2s;
}

.add-form-row input:focus {
    border-color: #15803d;
    box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.1);
}

.add-form-row .btn {
    background-color: #15803d;
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 0 24px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.add-form-row .btn:hover {
    background-color: #166534;
}

/* Filter Pills Row */
.filter-pills {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}

.filter-pill {
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    text-decoration: none;
}

.filter-pill.active {
    background-color: #15803d;
    color: #ffffff;
}

.filter-pill.inactive {
    background-color: #ffffff;
    color: #475569;
    border-color: #e2e8f0;
}

.filter-pill.inactive:hover {
    background-color: #f8fafc;
}

/* Todo Card Items */
.todo-card-item {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 16px 20px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    transition: border-color 0.2s;
}

.todo-card-item:hover {
    border-color: #cbd5e1;
}

.todo-card-item.done {
    opacity: 0.75;
}

.todo-card-item.done span {
    text-decoration: line-through;
    color: #94a3b8;
}

.chk-btn {
    background: transparent;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
}

.todo-content-wrap {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.todo-main-text {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
}

.todo-meta-details {
    display: flex;
    gap: 8px;
    align-items: center;
}

.badge-tag-code {
    background: #dcfce7;
    color: #166534;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
}

.badge-priority-tag {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
}

.badge-priority-tag.high {
    background: #fee2e2;
    color: #991b1b;
}

.badge-priority-tag.medium {
    background: #fef3c7;
    color: #92400e;
}

.todo-due-date {
    font-size: 12px;
    color: #94a3b8;
}

body.dark .todo-container .bar-bg {
    background-color: #334155 !important;
}

body.dark .todo-container .bar-bg i {
    background-color: #22c55e;
}
</style>

<div class="todo-container">
    <div class="todo-header">
        <h1>To-Do List</h1>
        <p class="sub"><?=count($t)-$d?> pending · <?=$d?> completed</p>
    </div>

    <!-- Overall Progress Component -->
    <div class="progress-card">
        <div class="progress-header-text">
            <span>Overall progress</span>
            <b><?=$p?>%</b>
        </div>
        <div class="bar-bg">
            <i style="width:<?=$p?>%"></i>
        </div>
    </div>

    <!-- Add Task Form -->
    <form method="post" class="add-form-row">
        <input name="title" placeholder="Add a new task..." required autocomplete="off">
        <button class="btn">+ Add</button>
    </form>

    <!-- Filter Pills -->
    <div class="filter-pills">
        <a href="#" class="filter-pill active">All (<?=count($t)?>)</a>
        <a href="#" class="filter-pill inactive">Pending (<?=count($t)-$d?>)</a>
        <a href="#" class="filter-pill inactive">Completed (<?=$d?>)</a>
    </div>

    <!-- Todo Items List -->
    <?php foreach($t as $x): 
        $o = strcasecmp($x['status'], 'completed') === 0;
    ?>
    <form method="post" class="todo-card-item <?= $o ? 'done' : '' ?>">
        <input type="hidden" name="id" value="<?=$x['todo_id']?>">
        <button class="chk-btn" type="submit"><?= $o ? '☑' : '☐' ?></button>
        <div class="todo-content-wrap">
            <span class="todo-main-text"><?=h($x['title'])?></span>
            <div class="todo-meta-details">
                <?php if(!empty($x['course_code'])): ?>
                    <span class="badge-tag-code"><?=h($x['course_code'])?></span>
                <?php endif; ?>
                <?php if(!empty($x['priority'])): ?>
                    <span class="badge-priority-tag <?=h($x['priority'])?>"><?=h($x['priority'])?></span>
                <?php endif; ?>
                <span class="todo-due-date">Due: <?=h(substr($x['due_date'],0,10))?></span>
            </div>
        </div>
    </form>
    <?php endforeach; ?>

</div>

<?php bottom();?>
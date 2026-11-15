<?php require '../inc.php';guard('student');[$enr,$ids]=ctx();$rows=api('assignment','student_list',['ids'=>$ids]);top('Assignments','assignments'); ?>
<div class="page-heading"><h1>Assignments</h1><p class="sub">View your coursework and submit your files.</p></div>
<?php if(isset($_GET['sent'])):?><div class="card" role="status">Assignment submitted successfully.</div><?php endif; ?>
<?php if(!$rows): ?><div class="card"><p>No published assignments are available right now.</p></div><?php endif; ?>
<div class="assignment-grid">
<?php foreach($rows as $x): ?><article class="card assignment-card"><div class="assignment-top"><span class="tag"><?=h(ucfirst($x['type']??'Assignment'))?></span><span class="pill <?=!empty($x['submission_id'])?'':'off'?>"><?=h($x['status']??'Not submitted')?></span></div><h3><?=h($x['title'])?></h3><p class="sub assignment-course-label"><strong>Subject:</strong> <?=h($x['course_code']??'')?><?=!empty($x['course_name'])?' · '.h($x['course_name']):''?></p><p class="sub assignment-professor-label"><strong>Professor:</strong> <?=h(trim($x['professor']??'' )?:'Not assigned')?></p><p class="sub">Due: <?=h($x['due_date']?:'No due date')?></p>
<?php if(!empty($x['submission_id'])): ?><p class="sub">Submitted <?=h($x['submitted_at'])?></p><?php endif; ?>
<form class="assignment-submit" method="post" action="submit_assignment.php" enctype="multipart/form-data">
	<input type="hidden" name="assignment_id" value="<?= (int)$x['assignment_id'] ?>">
	<div class="assignment-file-picker" data-file-picker>
		<input class="assignment-file-input" id="file-<?= (int)$x['assignment_id'] ?>" type="file" name="submission" accept=".pdf,.doc,.docx,.txt,.zip,.png,.jpg,.jpeg" required>
		<label class="assignment-file-dropzone" for="file-<?= (int)$x['assignment_id'] ?>" data-dropzone>
			<span class="assignment-file-mark" aria-hidden="true">↑</span>
			<span class="assignment-file-copy">
				<strong>Drop your file here or browse</strong>
				<small>PDF, DOC/DOCX, TXT, ZIP, PNG or JPG · Max 10 MB</small>
				<span class="assignment-file-name" data-file-name>No file selected</span>
			</span>
		</label>
		<p class="assignment-file-error" data-file-error role="alert" hidden></p>
	</div>
	<button class="btn" type="submit"><?=!empty($x['submission_id'])?'Resubmit assignment':'Submit assignment'?></button>
</form></article><?php endforeach; ?></div>
<script>
document.querySelectorAll('[data-file-picker]').forEach((picker) => {
	const input = picker.querySelector('.assignment-file-input');
	const dropzone = picker.querySelector('[data-dropzone]');
	const filename = picker.querySelector('[data-file-name]');
	const error = picker.querySelector('[data-file-error]');
	const allowedExtensions = new Set(['pdf', 'doc', 'docx', 'txt', 'zip', 'png', 'jpg', 'jpeg']);
	const maxBytes = 10 * 1024 * 1024;

	const setFile = (file) => {
		error.hidden = true;
		error.textContent = '';
		if (!file) {
			filename.textContent = 'No file selected';
			return false;
		}

		const extension = file.name.split('.').pop().toLowerCase();
		if (!allowedExtensions.has(extension)) {
			input.value = '';
			filename.textContent = 'No file selected';
			error.textContent = 'That file type is not supported. Choose PDF, DOC/DOCX, TXT, ZIP, PNG or JPG.';
			error.hidden = false;
			return false;
		}
		if (file.size > maxBytes) {
			input.value = '';
			filename.textContent = 'No file selected';
			error.textContent = 'The selected file is larger than 10 MB.';
			error.hidden = false;
			return false;
		}

		filename.textContent = file.name;
		return true;
	};

	input.addEventListener('change', () => setFile(input.files[0]));
	dropzone.addEventListener('dragover', (event) => {
		event.preventDefault();
		dropzone.classList.add('is-dragover');
	});
	dropzone.addEventListener('dragleave', (event) => {
		if (!dropzone.contains(event.relatedTarget)) dropzone.classList.remove('is-dragover');
	});
	dropzone.addEventListener('drop', (event) => {
		event.preventDefault();
		dropzone.classList.remove('is-dragover');
		const file = event.dataTransfer.files[0];
		if (!file) return;
		try {
			const transfer = new DataTransfer();
			transfer.items.add(file);
			input.files = transfer.files;
			setFile(input.files[0]);
		} catch (dropError) {
			error.textContent = 'Could not attach the dropped file. Please use the browse area.';
			error.hidden = false;
		}
	});
});
</script>
<?php bottom(); ?>

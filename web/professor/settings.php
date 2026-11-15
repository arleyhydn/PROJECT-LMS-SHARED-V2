<?php require '../inc.php';guard('professor');$u=$_SESSION['user'];top('Settings','settings');?>
<h1>Settings</h1><br><div class="card" style="max-width:700px"><h3>Account Information</h3>
<?php foreach(['Full Name'=>$u['first_name'].' '.$u['last_name'],'Email'=>$u['email'],'Role'=>ucfirst($_SESSION['role'])] as $k=>$v):?><p><b><?=$k?></b></p><div class="row" style="margin:6px 0 16px"><input value="<?=h($v)?>" readonly></div><?php endforeach;?></div>
<section class="card settings-help-card"><div><h3>Help Center</h3><p>Restart the interactive walkthrough to review your teaching tools.</p></div><a class="btn" href="<?=h(BASE)?>/web/help.php">Open Help Center</a></section>
<div class="card" style="max-width:700px"><label><b>Dark Mode</b> <input id="settingsDarkMode" type="checkbox" style="float:right"></label></div>
<script>const themeControl=document.getElementById("settingsDarkMode");if(themeControl){themeControl.checked=document.body.classList.contains("dark");themeControl.addEventListener("change",()=>{document.body.classList.toggle("dark",themeControl.checked);localStorage.setItem("lmsTheme",themeControl.checked?"dark":"light");if(themeControl.checked)localStorage.setItem("dark","1");else localStorage.removeItem("dark");});}</script>
<?php bottom();

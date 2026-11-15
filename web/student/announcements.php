<?php require '../inc.php';guard('student');[$enr,$ids]=ctx();$a=api('announcement','list',['ids'=>$ids]);$authors=names(array_column($a,'posted_by'));top('Announcements','announcements'); ?>
<div class="page-heading"><h1>Announcements</h1><p class="sub"><?=count($a)?> announcements</p></div>
<?php foreach($a as $x): $aid=(int)$x['announcement_id'];$state=api('announcement','interactions',['id'=>$aid]);$counts=api('announcement','counts',['id'=>$aid]);$read=!empty($state['read']); ?><article class="card announcement-card <?=$read?'is-read':''?>"><div class="announcement-meta"><span class="tag"><?=h(ucfirst($x['announcement_type']??'Announcement'))?></span><small><?=h(substr($x['created_at'],0,16))?></small></div><h3 class="announcement-title"><?=!empty($x['is_pinned'])?'<span aria-label="Pinned"><svg class="ui-icon" viewBox="0 0 24 24"><path d="m16 3 5 5-4 1-4 4-1 4-2-2-5 5-1-1 5-5-2-2 4-1 4-4z"/></svg></span> ':''?><?=h($x['title'])?></h3><p class="announcement-author"><svg class="ui-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg> Announced by <?=h($authors[$x['posted_by']]??'Professor')?></p><p><?=nl2br(h($x['content']))?></p><div class="announcement-actions"><form method="post" action="../announcement_action.php"><input type="hidden" name="id" value="<?=$aid?>"><input type="hidden" name="action" value="like"><button class="icon-action <?=($state['reaction']??'')==='like'?'selected':''?>" aria-label="Like announcement"><svg class="ui-icon" viewBox="0 0 24 24"><path d="M7 10v11H3V10zM7 21h10a2 2 0 0 0 2-1.6l2-8A2 2 0 0 0 19 9h-6l1-5-2-1-5 7"/></svg> Like <b><?= (int)($counts['likes']??0) ?></b></button></form><form method="post" action="../announcement_action.php"><input type="hidden" name="id" value="<?=$aid?>"><input type="hidden" name="action" value="dislike"><button class="icon-action <?=($state['reaction']??'')==='dislike'?'selected':''?>" aria-label="Dislike announcement"><svg class="ui-icon" viewBox="0 0 24 24"><path d="M7 14V3H3v11zM7 3h10a2 2 0 0 1 2 1.6l2 8A2 2 0 0 1 19 15h-6l1 5-2 1-5-7"/></svg> Dislike <b><?= (int)($counts['dislikes']??0) ?></b></button></form><form method="post" action="../announcement_action.php"><input type="hidden" name="id" value="<?=$aid?>"><input type="hidden" name="action" value="read"><button class="icon-action" type="submit"><svg class="ui-icon" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg><?=$read?'Read':'Mark as read'?></button></form></div></article><?php endforeach; ?><script>
document.addEventListener('submit',async e=>{
 const f=e.target.closest('.announcement-actions form');if(!f)return;e.preventDefault();
 const b=f.querySelector('button');if(!b)return;const oldText=b.textContent;b.disabled=true;
 try{
  const r=await fetch(f.action,{method:'POST',body:new FormData(f),headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin',cache:'no-store'});
  const d=await r.json().catch(()=>({}));
  if(!r.ok||!d.ok)throw new Error(d.error||('Request failed ('+r.status+'). Please check your login and LMS service/database.'));
  const card=f.closest('.announcement-card');
  if(d.action==='read'){
   card.classList.add('is-read');b.classList.add('selected');b.innerHTML='<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg> Read';
  }else{
   card.querySelectorAll('.announcement-actions form').forEach(x=>{
    const a=x.querySelector('[name="action"]')?.value;const button=x.querySelector('button');
    if(a==='like'||a==='dislike')button?.classList.toggle('selected',(d.state?.reaction||'')===a);
    const count=button?.querySelector('b');if(count&&d.counts)count.textContent=String(d.counts[a==='like'?'likes':'dislikes']??count.textContent);
   });
  }
  if(d.refresh_needed) console.info('Announcement action saved; latest counts will refresh when the page reloads.');
 }catch(err){alert(err.message||'Could not update announcement. Please try again.');}
 finally{b.disabled=false;}
});
</script>
<?php bottom(); ?>

<?php $title = 'Configurare'; ?>
<h2>Configurare aplicatie</h2>
<?php if (!empty($_GET['ok'])): ?>
  <div class="card">Operatie finalizata: <?php echo htmlspecialchars($_GET['ok']); ?></div>
<?php endif; ?>
<h3>Importa culori</h3>
<form method="post" action="/admin/config/seed_colors">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <button type="submit">Importa culori</button>
  <p>Culori standard din BrickLink.</p>
</form>
<h3>Importa piese</h3>
<form method="post" action="/admin/config/scrape_parts" id="form-parts">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <textarea name="codes" placeholder="Listeaza codurile BrickLink pe linii separate (ex: 3001)"></textarea>
  <button type="submit">Importa piese</button>
  <div id="parts-progress" class="card" style="display:none"></div>
  <div id="parts-summary" class="card" style="display:none"></div>
  <div id="parts-log" class="card" style="display:block; max-height:200px; overflow:auto"></div>
</form>
<h3>Importa seturi</h3>
<form method="post" action="/admin/config/scrape_sets" id="form-sets">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <textarea name="codes" placeholder="Listeaza codurile BrickLink pe linii separate (ex: 1234-1)"></textarea>
  <button type="submit">Importa seturi</button>
  <div id="sets-progress" class="card" style="display:none"></div>
  <div id="sets-summary" class="card" style="display:none"></div>
  <div id="sets-log" class="card" style="display:block; max-height:200px; overflow:auto"></div>
</form>
<script>
document.addEventListener('DOMContentLoaded',function(){
  function process(formId, endpoint, boxId){
    var form=document.getElementById(formId);
    if(!form) return;
    var box=document.getElementById(boxId);
    var sum=document.getElementById(boxId.replace('progress','summary'));
    var liveLogId=boxId.replace('progress','log');
    var liveLog=document.getElementById(liveLogId);
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var codes=(form.querySelector('textarea[name=codes]').value||'').split(/\r?\n/).map(function(s){return s.trim()}).filter(Boolean);
      if(!codes.length) return;
      box.style.display='block';
      sum.style.display='none';
      sum.innerHTML='';
      var results=[];
      var errors=[];
      var i=0;
      box.textContent='Pornit... (0/'+codes.length+')';
      function step(){
        if(i>=codes.length){
          box.textContent='Finalizat ('+codes.length+'/'+codes.length+')';
          var okCount=results.length, errCount=errors.length;
          var html='<strong>Rezumat import</strong><br>Succes: '+okCount+' • Eșecuri: '+errCount+'<br>';
          if(okCount){
            html+='<table class="data-table"><thead><tr><th>Cod</th><th>Nume</th><th>Detalii</th></tr></thead><tbody>';
            results.slice(-10).forEach(function(r){
              var det=[];
              if(r.type==='part'){
                det.push('Related: '+(r.related_count||0));
                det.push('Compozitie: '+(r.inv_count||0));
              }else{
                det.push('Instrucțiuni: '+(r.instructions_url?'Da':'Nu'));
                det.push('Piese: '+(r.inv_count||0));
              }
              html+='<tr><td>'+r.code+'</td><td>'+(r.name||'')+'</td><td>'+det.join(' • ')+'</td></tr>';
            });
            html+='</tbody></table>';
            if(results.length>10){html+='<em>Doar ultimele 10 afișate.</em>';}
          }
          if(errCount){
            html+='<div style="color:#c53030">Erori: '+errors.join(', ')+'</div>';
          }
          sum.innerHTML=html;
          sum.style.display='block';
          return;
        }
        var code=codes[i++];
        box.textContent='Procesez '+code+' ('+i+'/'+codes.length+')';
        var fd=new FormData();
        fd.append('csrf','<?php echo htmlspecialchars($csrf); ?>');
        fd.append('code',code);
        fetch(endpoint,{method:'POST',body:fd}).then(function(r){
          var ct=r.headers.get('content-type')||'';
          if(ct.indexOf('application/json')>-1){return r.json()}else{return r.text().then(function(t){return {status:t==='ok'?'ok':'err', code:code}})}
        }).then(function(data){
          if(data && data.status==='ok'){
            results.push(data);
            if(liveLog){
              var line=code+' • ';
              if(data.type==='part'){
                line+='related='+data.related_count+' • comp='+data.inv_count;
              }else{
                line+='instr='+(data.instructions_url?'1':'0')+' • parts='+data.inv_count;
              }
              if(Array.isArray(data.log)){
                line+=' • '+data.log.join(' | ');
              }
              var p=document.createElement('div'); p.textContent=line; liveLog.appendChild(p);
            }
          }else{
            errors.push(code);
          }
          var delay=3000+Math.floor(Math.random()*7000);
          setTimeout(step,delay);
        }).catch(function(){
          errors.push(code);
          var delay=3000+Math.floor(Math.random()*7000);
          setTimeout(step,delay);
        });
      }
      step();
    });
  }
  process('form-parts','/admin/config/scrape_parts_one','parts-progress');
  process('form-sets','/admin/config/scrape_sets_one','sets-progress');
});
</script>

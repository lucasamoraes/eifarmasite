<?php
// Included only by the authenticated manager dashboard.
if(empty($logged)){http_response_code(403);exit;}
$pendingIndex=array_column(json_decode(file_get_contents(ROOT.'/data/mips-pending.json'),true,512,JSON_THROW_ON_ERROR),null,'ean');
$medicineTotal=(int)$pdo->query("SELECT COUNT(*) FROM products WHERE category='Medicamentos sem receita'")->fetchColumn();
$medicinePerPage=10;$medicinePages=max(1,(int)ceil($medicineTotal/$medicinePerPage));
$medicinePage=max(1,min($medicinePages,(int)($_GET['med_page']??1)));$medicineOffset=($medicinePage-1)*$medicinePerPage;
$medicineQuery=$pdo->prepare("SELECT ean,name,active FROM products WHERE category='Medicamentos sem receita' ORDER BY active ASC,name LIMIT 10 OFFSET ?");
$medicineQuery->bindValue(1,$medicineOffset,PDO::PARAM_INT);$medicineQuery->execute();$medicineRows=$medicineQuery->fetchAll();
?>
<section class="panel" id="medicamentos"><h2>Medicamentos cadastrados</h2>
<p><?=$medicineTotal?> medicamentos no sistema, exibidos de 10 em 10. Os que ainda aguardam aprovação aparecem primeiro.</p>
<p class="notice">A aprovação publica imediatamente, mas não valida o registro nem completa informações e advertências pendentes. Confira os dados antes de aprovar.</p>
<details open><summary>Ver medicamentos e situação do cadastro</summary>
<form method="post" action="admin.php?med_page=<?=$medicinePage?>#medicamentos" id="medicine-bulk-form"><?=csrf()?><input type="hidden" name="action" value="medicine_bulk_visibility">
<div class="bulk-toolbar"><label><input type="checkbox" id="select-page-medicines"> Selecionar os disponíveis desta página</label><button class="button" type="submit">Aprovar selecionados</button></div>
<div class="table-scroll"><table><thead><tr><th>Selecionar</th><th>Produto</th><th>EAN</th><th>Situação</th><th>Dados e pendências</th><th>Ação</th></tr></thead><tbody>
<?php foreach($medicineRows as $p):$pending=$pendingIndex[$p['ean']]??null;?>
<tr><td><?php if(!$p['active']):?><input class="medicine-select" type="checkbox" name="eans[]" value="<?=e($p['ean'])?>" aria-label="Selecionar <?=e($p['name'])?>"><?php else:?>—<?php endif;?></td><td><?=e($p['name'])?></td><td><?=e($p['ean'])?></td><td><?=$p['active']?'Na vitrine':'Cadastrado, não publicado'?></td><td><?php if($pending):?><strong>Registro: <?=e($pending['registration'])?></strong><br>Situação na fonte: <?=e(implode(', ',$pending['registration_status'])?:'Não encontrada')?><br>Pendente: bula, indicação e advertências<?=($pending['registration_status']!==['Ativo']||$pending['ambiguous'])?'; conciliação do registro/apresentação':''?><?php else:?>Dados do cartão completos<?php endif;?></td><td><?php if($p['active']):?><button class="text-button medicine-single-action" type="submit" name="remove_ean" value="<?=e($p['ean'])?>">Retirar da vitrine</button><?php else:?>Use a seleção<?php endif;?></td></tr>
<?php endforeach;?></tbody></table></div></form>
<?php if($medicinePages>1):?><nav class="pagination" aria-label="Páginas de medicamentos"><?php for($page=1;$page<=$medicinePages;$page++):?><a class="<?=$page===$medicinePage?'active':''?>" href="admin.php?med_page=<?=$page?>#medicamentos" aria-current="<?=$page===$medicinePage?'page':'false'?>"><?=$page?></a><?php endfor;?></nav><?php endif;?>
</details>
<p><a href="mips-preview.php">Ver cartão do medicamento com dados completos</a></p></section>
<script>document.getElementById('select-page-medicines')?.addEventListener('change',event=>document.querySelectorAll('.medicine-select').forEach(box=>box.checked=event.target.checked));</script>

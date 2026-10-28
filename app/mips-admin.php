<?php
// Included only by the authenticated manager dashboard.
if(empty($logged)){http_response_code(403);exit;}
$pendingIndex=array_column(json_decode(file_get_contents(ROOT.'/data/mips-pending.json'),true,512,JSON_THROW_ON_ERROR),null,'ean');
$medicineRows=$pdo->query("SELECT ean,name,active FROM products WHERE category='Medicamentos sem receita' ORDER BY active DESC,name")->fetchAll();
?>
<section class="panel" id="medicamentos"><h2>Medicamentos cadastrados</h2>
<p><?=count($medicineRows)?> medicamentos no sistema. Use <strong>Aprovar e publicar</strong> para colocar um produto na vitrine ou <strong>Retirar da vitrine</strong> para ocultá-lo.</p>
<p class="notice">A aprovação é uma decisão manual do gerente: publica imediatamente, mas não valida o registro nem completa as informações e advertências pendentes. Confira os dados abaixo antes de aprovar. O CRF não é solicitado neste botão.</p>
<details open><summary>Ver medicamentos e situação do cadastro</summary><div class="table-scroll"><table><thead><tr><th>Produto</th><th>EAN</th><th>Situação</th><th>Dados e pendências</th><th>Ação</th></tr></thead><tbody>
<?php foreach($medicineRows as $p):$pending=$pendingIndex[$p['ean']]??null;?>
<tr><td><?=e($p['name'])?></td><td><?=e($p['ean'])?></td><td><?=$p['active']?'Na vitrine':'Cadastrado, não publicado'?></td><td><?php if($pending):?><strong>Registro: <?=e($pending['registration'])?></strong><br>Situação na fonte: <?=e(implode(', ',$pending['registration_status'])?:'Não encontrada')?><br>Pendente: bula, indicação e advertências<?=($pending['registration_status']!==['Ativo']||$pending['ambiguous'])?'; conciliação do registro/apresentação':''?><?php else:?>Dados do cartão completos<?php endif;?></td><td><form method="post" action="admin.php#medicamentos"><?=csrf()?><input type="hidden" name="action" value="medicine_visibility"><input type="hidden" name="ean" value="<?=e($p['ean'])?>"><input type="hidden" name="visible" value="<?=$p['active']?'0':'1'?>"><button class="button" type="submit"><?=$p['active']?'Retirar da vitrine':'Aprovar e publicar'?></button></form></td></tr>
<?php endforeach;?></tbody></table></div></details>
<p><a href="mips-preview.php">Ver cartão do medicamento com dados completos</a></p></section>

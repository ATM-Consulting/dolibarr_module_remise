<?php


	require '../config.php';
	dol_include_once('/remise/lib/remise.lib.php');
	dol_include_once('/remise/class/remise.class.php');
	
	$langs->load("admin");
    $langs->load("deliveries");
	
	$type = GETPOST('type');
	if(empty($type)) $type = 'AMOUNT';
	
	$action = GETPOST('action');
	$remise = new TRemise;
	$PDOdb=new TPDOdb;
	
	switch ($action) {
		case 'save':
			
			if(GETPOST('bt_cancel')!='') {
				header('location:'.dol_buildpath('/remise/admin/remise.php?type='.GETPOST('type'),1) );
			}
			else{
				$remise->load($PDOdb, GETPOST('id'));
				$remise->set_values($_POST);
				$remise->save($PDOdb);		
				
				setEventMessage($langs->trans('RemiseSaved'));
				header('location:'.dol_buildpath('/remise/admin/remise.php?type='.GETPOST('type').'&TListTBS[lPrice][orderBy][date_maj]=DESC',1) );
			}
		
		case 'edit':
			$remise->load($PDOdb, GETPOST('id'));
			fiche($remise, $type, 'edit');
			
			break;
		case 'new':
			
			fiche($remise, $type, 'edit');
			
			break;
		default:
			liste($type);
				
			break;
	}
	
function fiche(&$remise, $type, $mode) {
	global $conf, $langs, $db;
	
	$page_name = "RemiseSetup";
	llxHeader('', $langs->trans($page_name));	
	$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	    . $langs->trans("BackToModuleList") . '</a>';
	print_fiche_titre($langs->trans($page_name), $linkback);
	
	// Configuration header
	$head = remiseAdminPrepareHead();
	dol_fiche_head(  $head,  $type,  $page_name,   0,   "remise@remise" );	
	$form = new TFormCore('auto', 'form1','post');
	$form->Set_typeaff($mode);
	
	echo $form->hidden('type', $type);
	echo $form->hidden('id', $remise->getId());
	echo $form->hidden('action', 'save');
	
	$f=new Form($db);
	
	?>
	<table class="border" width="100%">
		<tr>
			<td  width="20%"><?php echo $langs->trans('Palier') ?></td><td><?php echo $form->texte('','palier', $remise->palier, 10,255) ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('Remise') ?></td><td><?php echo $form->texte('','remise', $remise->remise, 10,255) ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('Zip') ?></td><td><?php echo $form->texte('','zip', $remise->zip, 5,255) ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('ShipmentMode') ?></td><td>
				<?php
					if((float)DOL_VERSION >= 3.7) $f->selectShippingMethod($remise->fk_shipment_mode, 'fk_shipment_mode', '', 1);
					else {
						
						$query = "SELECT rowid, code, libelle";
						$query.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode";
						$query.= " WHERE active = 1";
						$query.= " ORDER BY libelle ASC";
						
						$resql = $db->query($query);
						
						print '<select id="fk_shipment_mode" class="flat selectshippingmethod" name="fk_shipment_mode"'.($moreattrib?' '.$moreattrib:'').'>';
						
						print '<option value="">';
							print '</option>';
						
						while($res = $db->fetch_object($resql)) {
							$selected = '';
							if($remise->fk_shipment_mode == $res->rowid) $selected = 'selected="selected"';
							print '<option value="'.$res->rowid.'" '.$selected.'>';
							print $langs->trans("SendingMethod".strtoupper($res->code));
							print '</option>';
						}
						
						print '</select>';
						
					} 
				?></td>
		</tr>
		
	</table>
	<div class="tabsAction">
		<?php
			echo $form->btsubmit($langs->trans('Save'), 'bt_save');
			echo $form->btsubmit($langs->trans('Cancel'), 'bt_cancel','','butAction butActionCancel');
		?>
	</div>
	<?
	
	
	
	$form->end();
	
	dol_fiche_end();
	llxFooter();
}
	
function liste($type) {
	global $conf, $langs;
	
	$page_name = "RemiseSetup";
	llxHeader('', $langs->trans($page_name));	
	
	$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	    . $langs->trans("BackToModuleList") . '</a>';
	print_fiche_titre($langs->trans($page_name), $linkback);
	
	// Configuration header
	$head = remiseAdminPrepareHead();

	dol_fiche_head(  $head,  $type,  $page_name,   0,   "remise@remise" );	
	

	$l=new TListviewTBS('lPrice');
	
	
	$sql="SELECT rowid as Id, palier,remise,zip,fk_shipment_mode,date_maj FROM ".MAIN_DB_PREFIX."remise 
			WHERE type=:type";
	
	$PDOdb=new TPDOdb;
	
	$form = new TFormCore('auto', 'form1','get');
	echo $form->hidden('type', $type);
	
	
	echo $l->render($PDOdb, $sql, array(
		'link'=>array(
			'Id'=>'<a href="'.dol_buildpath('/remise/admin/remise.php?action=edit&id=@val@&type='.$type,1).'">@val@</a>'
		)
		,'type'=>array(
			'remise'=>'money'
			,'palier'=>'number'
			
			
			,'date_maj'=>'date'
		)
		,'title'=>array(
			'palier'=>$langs->trans('Palier')
			,'zip'=>$langs->trans('Zip')
			,'fk_shipment_mode'=>$langs->trans('ShipmentMode')
			,'remise'=>$langs->trans('Remise')
			,'date_maj'=>$langs->trans('Update')
		)
		,'eval'=>array(
			'fk_shipment_mode'=>'showShipmentMode(@val@)'
		)
		,'search'=>array(
			'palier'=>true
			,'zip'=>true
			,'remise'=>true
		)
	),array(
		':type'=>$type
	));
	
	$form->end();
	
	echo '<div class="tabsAction">';
	echo $form->bt($langs->trans('New'), 'bt_new', ' onclick="document.location.href=\'?type='.$type.'&action=new\' "' );
	echo '</div>';
	
	dol_fiche_end();
	llxFooter();
		
}

function showShipmentMode($id) {
global $db, $langs;
	
	$sql = "SELECT rowid, code, libelle as label";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode";
    $sql.= " WHERE rowid=".$id;

	$res = $db->query($sql);
	if($obj = $db->fetch_object($res)) {
		return ($langs->trans("SendingMethod".strtoupper($obj->code)) != "SendingMethod".strtoupper($obj->code)) ? $langs->trans("SendingMethod".strtoupper($obj->code)) : $obj->label;
	}
	
	return '';
		
}

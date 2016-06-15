<?php
/* Module de gestion des remises
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/mymodule.php
 * 	\ingroup	mymodule
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment

require('../config.php');
dol_include_once('/remise/class/remise.class.php');

$PDOdb=new TPDOdb;

global $db;

// Libraries
dol_include_once("remise/core/lib/admin.lib.php");
dol_include_once('remise/lib/remise.lib.php');
dol_include_once('core/lib/admin.lib.php');
dol_include_once('core/lib/ajax.lib.php');
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('/core/class/extrafields.class.php');
//require_once "../class/myclass.class.php";
// Translations
$langs->load("remise@remise");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */


switch ($action) {
		
	case 'saveIDServiceToUse':
		if(_saveConst($db, 'REMISE_ID_SERVICE_TO_USE', $_REQUEST['idservice'])) {
			
			setEventMessage($langs->trans('IDServiceSaved'));
			
		} else {
			
			setEventMessage($langs->trans('IDServiceNotSaved'), 'errors');
			
		}
		
		break;
	
	case 'saveIDCategToExclude':
		if(_saveConst($db, 'REMISE_ID_CATEG_TO_EXCLUDE', $_REQUEST['idcategtoexclude'])) {
			
			setEventMessage($langs->trans('IDCategSaved'));
			
		} else {
			
			setEventMessage($langs->trans('IDCategNotSaved'), 'errors');
			
		}
	
	case 'save':
		$const_name = array_keys($_REQUEST['TDivers'])[0];
		$val = $conf->global->{$const_name};
		$val = !$val;
		if($const_name === 'REMISE_USE_THIRDPARTY_DISCOUNT' && !empty($val)){
			dolibarr_set_const($db, 'REMISE_USE_THIRDPARTY_DISCOUNT', $val);
			dolibarr_set_const($db, 'REMISE_USE_WEIGHT', !$val);
			$e = new ExtraFields($db);
			$e->addExtraField('remise_disponible', 'Remise disponible (en euros)', 'double', '', '24,8', 'societe');
		} elseif($const_name === 'REMISE_USE_WEIGHT' && !empty($val)){
			dolibarr_set_const($db, 'REMISE_USE_WEIGHT', $val);
			dolibarr_set_const($db, 'REMISE_USE_THIRDPARTY_DISCOUNT', !$val);
		} else {
			dolibarr_set_const($db, $const_name, $val);
		}
	
	default:
	
		break;
}
 
/*
 * View
 */ 

//print_r($TFraisDePort);
 
$page_name = "RemiseSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = remiseAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104921Name"),
    0,
    "remise@remise"
);


function _saveConst($db, $name, $val) {
	
	if(!empty($val)) {
		
		dolibarr_set_const($db, $name, $val);
		return true;
		
	}
	
	return false;
	
}

$form = new Form($db);

print '<form name="formIDServiceToUse" method="POST" action="" />';
$form->select_produits($conf->global->REMISE_ID_SERVICE_TO_USE,'idservice',1,$conf->product->limit_size,$buyer->price_level);
print '<input type="hidden" name="action" value="saveIDServiceToUse" />';
print '<input type="SUBMIT" name="subIDServiceToUse" value="Utiliser ce service" />';
print '</form>';

print '<form name="formIDCategToExclude" method="POST" action="" />';
print 'Sélection catégorie : ';
print $form->select_all_categories(0, $conf->global->REMISE_ID_CATEG_TO_EXCLUDE,'idcategtoexclude');
print '<input type="hidden" name="action" value="saveIDCategToExclude" />';
print '<input type="SUBMIT" name="subIDServiceToUse" value="Exclure cette catégorie" />';
print '</form>';

?>
<br />
<table width="100%" class="noborder" style="background-color: #fff;">
    <tr class="liste_titre">
        <td colspan="2"><?php echo $langs->trans('Parameters') ?></td>
    </tr>
<tr>
    <td><?php echo $langs->trans('UseWeight') ?></td><td><?php
    
        if($conf->global->REMISE_USE_WEIGHT==0) {
            
             ?><a href="?action=save&TDivers[REMISE_USE_WEIGHT]=1"><?=img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
            
        }
        else {
             ?><a href="?action=save&TDivers[REMISE_USE_WEIGHT]=0"><?=img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
            
        }
    
    ?></td>             
</tr>
<tr>
    <td><?php echo $langs->trans('UseThirdPartyDiscount') ?></td><td><?php
    
        if($conf->global->REMISE_USE_THIRDPARTY_DISCOUNT==0) {
            
             ?><a href="?action=save&TDivers[REMISE_USE_THIRDPARTY_DISCOUNT]=1"><?=img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
            
        }
        else {
             ?><a href="?action=save&TDivers[REMISE_USE_THIRDPARTY_DISCOUNT]=0"><?=img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
            
        }
    
    ?></td>             
</tr>
</table><?

llxFooter();

$db->close();
<?php
/* <one line to give the program's name and a brief idea of what it does.>
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
 * 	\file		core/triggers/interface_99_modMyodule_Mytrigger.class.php
 * 	\ingroup	mymodule
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class InterfaceRemise
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'mymodule@mymodule';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users
        if ($action == 'ORDER_VALIDATE' || $action == 'PROPAL_VALIDATE') {
        	
			global $db,$conf;

			$langs->load('remise@remise');
			
			$object->fetch_optionals($object->id);
			/*echo "<pre>";
			print_r($object);
			echo "</pre>";*/
				
			dol_include_once('core/lib/admin.lib.php');
			
			define('INC_FROM_DOLIBARR',true);
			dol_include_once('/remise/config.php');		
			dol_include_once('/remise/class/remise.class.php');
			$PDOdb=new TPDOdb;
			// On récupère les remises définis dans la configuration du module
			
            $remiseAlreadyInDoc = TRemise::alreadyAdded( $object );
			
			$fk_product = $conf->global->REMISE_ID_SERVICE_TO_USE;
			
			if(!$remiseAlreadyInDoc && !empty($fk_product) && $object->array_options['options_use_remise'] === 'Oui') {
			    dol_include_once('/product/class/product.class.php','Product');
                $remise_used_montant = TRemise::getRemise($PDOdb, 'AMOUNT', $object->total_ht);
				
                $remise_used_weight = 0;
                if($conf->global->REMISE_USE_WEIGHT) {
                	$total_weight = TRemise::getTotalWeight($object);
					$remise_used_weight = TRemise::getRemise($PDOdb, 'WEIGHT', $total_weight, $object->client->zip);
					
				}
               
                $remise_used = max($remise_used_weight, $remise_used_montant );
             	
				$p = new Product($db);
				$p->fetch($fk_product);
				
				$object->statut = 0;
				
				$used_tva = ($object->client->tva_assuj == 1) ? $p->tva_tx : 0;
				
				if($object->element == 'commande') {
					$object->addline("Remise", $remise_used, 1, $used_tva, 0, 0, $fk_product, 0, 0, 0, 'HT', 0, '', '', $p->type);
				} else if($object->element == 'propal') {
					$object->addline("Remise", $remise_used, 1, $used_tva, 0, 0, $fk_product, 0, 'HT', 0, 0, $p->type);
				}
                
                setEventMessage($langs->trans('PortTaxAdded').' : '.price($remise_used).$conf->currency.' '.$langs->trans('VAT').' '.$used_tva.'%' );
				
				$object->fetch($object->id);
				$object->statut = 1; // TODO AA à quoi ça sert... Puisqu'il n'ya pas de save... :-|
			
				
			}
			
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );
        }

        return 0;
    }
}

<?php


class TRemise extends TObjetStd {
    function __construct() {
        global $langs;
         
        parent::set_table(MAIN_DB_PREFIX.'remise');
        parent::add_champs('palier,remise',array('type'=>'float', 'index'=>true));
        parent::add_champs('zip,type',array('index'=>true));
        parent::add_champs('fk_shipment_mode',array('type'=>'int', 'index'=>true));
        
        parent::_init_vars();
        parent::start();    
         
    }
	
	static function getAll(&$PDOdb, $type='AMOUNT', $asArray=false, $with_zip = false, $with_fk_shipment_mode = false) {
		
		$TRemise = array();
		
		$sql = "SELECT rowid,palier,remise,zip,fk_shipment_mode
		FROM ".MAIN_DB_PREFIX."remise WHERE type='".$type."' 
		";
		
		if($type == 'AMOUNT') $sql.="ORDER BY palier DESC,zip DESC ,fk_shipment_mode DESC";
		else $sql.="ORDER BY palier DESC, zip DESC, fk_shipment_mode DESC";
		
		$Tab = $PDOdb->ExecuteAsArray($sql);
		
		$last_palier = 0;
		foreach($Tab as &$row) {
			
				
			if($asArray) {
				$row->last_palier = $last_palier;
				
				$o = (array)$row;
				
				if($last_palier < $row->palier) $last_palier = $row->palier;
			}
			else {
				$o=new TRemise;
				$o->load($PDOdb, $row->rowid );
				
			}		
			$TRemise[] = $o;			
			
		}
		
		return $TRemise;
	}
	static function getRemise(&$PDOdb, $type, $total, $zip='', $fk_shipment_mode = 0) {
		
		$TRemise = TRemise::getAll($PDOdb, $type, true, !empty($zip), !empty($fk_shipment_mode));
		$remise_used = 0; $find = false;
        if(!empty($TRemise)) {
        	
        	foreach ($TRemise as &$remise) {
            		
            	if($type === 'WEIGHT' && $total >= $remise['palier'] && ($remise['remise']>$remise_used || empty($remise_used) )) {
                	
					if( (!empty($zip) && !empty( $remise['zip'] ) && strpos($zip,$remise['zip']) === 0 ) ) {
						$remise_used = $remise['remise'];
						$find=true;
						break;
					}
					else if(empty($zip) && empty($remise['zip'])  ) {
						// pas de remise associée au code poste trouvé avant
						$find=true;
						$remise_used = $remise['remise'];
						break;
					}
					
                    
                }
				else if($type==='AMOUNT') {
					if($total >= $remise['palier'] && ($remise['remise'] > $remise_used || empty($remise_used) ) ) {
						$remise_used = $remise['remise'];
						$find = true;
					}
				}
				
            }
        }
		
		if(!$find && !empty($zip)) return TRemise::getRemise($PDOdb, $type, $total);
		else return $remise_used;
		
	}

	static function getTotal(&$object, $type='ht') {
		
		global $conf, $db;
		
		$id_categ = $conf->global->REMISE_ID_CATEG_TO_EXCLUDE;
		
		if(empty($id_categ)) return $object->{'total_'.$type};
		
		$total = 0;
		
		foreach ($object->lines as $line) {
			
			if($line->fk_product > 0){
			
				$sql = 'SELECT fk_product
						FROM '.MAIN_DB_PREFIX.'categorie_product
						WHERE fk_categorie = '.$id_categ.'
						AND fk_product = '.$line->fk_product;
				
				$resql = $db->query($sql);
				$res = $db->fetch_object($resql);
				if($res->fk_product > 0) continue;
			
			}

			$total+=$line->total_ht;
			
		}
		
		return $total;
		
	}

	static function alreadyAdded(&$object) {
		global $conf;
		
		$remiseAlreadyInDoc = false;
		$fk_product = $conf->global->REMISE_ID_SERVICE_TO_USE;
		
		
		foreach($object->lines as $line) {
			if(!empty($line->fk_product) && $line->fk_product == $fk_product) {
				$remiseAlreadyInDoc = true;
                break;
			}
		}
		
		return $remiseAlreadyInDoc;
	}
    
	static function getTotalWeight(&$object) {
		global $db;
		 dol_include_once('/product/class/product.class.php','Product');
		
		$total_weight = 0;
        foreach($object->lines as &$line) {
            if($line->fk_product_type ==0 && $line->fk_product>0 ) {
                $p=new Product($db);
                $p->fetch($line->fk_product);
                
                if($p->id>0) {
                    $weight_kg = $p->weight * $line->qty * pow(10, $p->weight_units);
                    $total_weight+=$weight_kg;
                }
            }
        }
        
		return $total_weight;
		
	}
}
    
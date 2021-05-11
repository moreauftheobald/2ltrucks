<?php
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class lltrucks extends CommonObject
{
    public function __construct($db)
    {
        $this->db = $db;
    }


 /*   public function getorlinkedHV($or) {

        $sql = 'SELECT rowid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'dolifleet_vehicule_link';
        $sql .= " WHERE ";
        $sql .= " (fk_source = ".$or->array_options['options_fk_dolifleet_vehicule']." OR fk_target = ". $or->array_options['options_fk_dolifleet_vehicule'].")";
        $sql .= " ORDER BY date_start DESC";


        $resql = $this->db->query($sql);
        if ($resql)
        {

            dol_include_once('/dolifleet/class/vehiculeLink.class.php');
            $obj = $this->db->fetch_object($resql);
            $Vlink = new doliFleetVehiculeLink($this->db);
            $ret = $Vlink->fetch($obj->rowid);

            if ($Vlink->fk_source !=$or->array_options['options_fk_dolifleet_vehicule']) $Vlink->fk_other_vehicule = $Vlink->fk_source;
            else if ($Vlink->fk_target != $or->array_options['options_fk_dolifleet_vehicule']) $Vlink->fk_other_vehicule = $Vlink->fk_target;

            dol_include_once('/dolifleet/class/vehicule.class.php');
            $vh = new doliFleetVehicule($this->db);
            $ret = $vh->fetch($Vlink->fk_other_vehicule);

            if($ret>0){
                $out = $vh->getLinkUrl(1,'','immatriculation');
            }else{
                $out = 'Pas de véhicule lié';
            }

        }else{
            $out = 'Pas de véhicule lié';
        }
        return $out;

    } */
}




?>


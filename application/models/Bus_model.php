<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bus_model extends CI_Model{
	public $name;
	public $description;
	const table = 'bus';
	const lastLocalizationsTable = 'last_localizations'; 

	public function __construct(){
		parent::__construct();
	}
	public function index($routeId){
		return $this->db->select("*")
						->from(self::table)
						->where(self::table.".id_routes = {$routeId}")
						->get()->result();
	}
	public function getLastLocalizations($idBus , $limit){
		return $this->db->select(self::lastLocalizationsTable.'.latitude, '.self::lastLocalizationsTable.'.longitude, '.self::lastLocalizationsTable.'.date')
						->from(self::lastLocalizationsTable)
						->where(self::lastLocalizationsTable.".id_bus = {$idBus}")
						->limit($limit,0)
						->get()->result();
	}
	public function getBus($idRoute, $idBus){
		$bus = $this->db->select('*')
						->from(self::table)
						->where(self::table.".id_routes = {$idRoute} AND ".self::table.".id_bus = {$idBus}")
						->get()->result();

		if(isset($bus[0]))
			return $bus[0];
		
		return null; 
	}
	public function insertLocalization($idRoute, $idBus, $input){
		$localization = $input;
		$localization->id_bus = $idBus;
		$localization->date = date('Y-m-d H:i:s');
		$this->db->insert(self::lastLocalizationsTable, $localization);

		return $localization->date;
	}
	public function insertBus($idRoute, $bus){
		$busData = new stdClass();
		$busData->id_routes = $idRoute;
		$this->db->insert(self::table, $busData);
		$idBus = $this->db->insert_id();

		return $idBus;
	}
	public function deleteLocalizations($idRoute, $idBus){
		$this->db->where("id_bus", $idBus);
		$this->db->delete(self::lastLocalizationsTable);
	}
}
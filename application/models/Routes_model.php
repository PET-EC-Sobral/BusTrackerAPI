<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Routes_model extends CI_Model{
	public $name;
	public $description;
	const table = 'routes';
	const pointsTable = 'points_route'; 
	const busTable = 'bus';

	public function __construct(){
		parent::__construct();
	}
	public function index(){
		return $this->db->get(self::table)->result();
	}
	public function getRoute($id){
		$route = $this->db->select('*')
						->from(self::table)
						->where(self::table.".id_routes = {$id}")
						->get()->result();

		if(isset($route[0]))
			return $route[0];
		
		return null; 
	}
	public function getPoints($id){
		return $this->db->select(self::pointsTable.'.latitude, '.self::pointsTable.'.longitude')
						->from(self::table)
						->join(self::pointsTable, self::table.'.id_routes = '.self::pointsTable.'.id_routes')
						->where(self::pointsTable.".id_routes = {$id}")
						->get()->result();
	}
	public function insertRoute($route){
		//insert route
		$this->name = $route->name;
		$this->description = $route->description;
		$this->db->insert(self::table, $this);
		$id = $this->db->insert_id();

		//insert points
		$points = $route->points;
		$length = count($points);
		for($i = 0; $i < $length; $i++){
			$points[$i]->id_routes = $id;
		}

		$this->db->insert_batch(self::pointsTable, $points);

		return $id;
	}
	public function getBuses($id){
		return $this->db->select("id_bus")
						->from(self::busTable)
						->where("id_routes = {$id}")
						->get()->result();
	}
	public function deleteRoute($id){
		$this->db->where('id_routes', $id);
		$this->db->delete(self::pointsTable);

		$this->db->where('id_routes', $id);
   		$this->db->delete(self::table);
	}
}
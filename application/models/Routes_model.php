<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Routes_model extends CI_Model{
	public $name;
	public $description;
	const table = 'routes';
	const pointsTable = 'points_route'; 
	const busTable = 'bus';
	public static $google_routes_path;

	public function __construct(){
		parent::__construct();
		self::$google_routes_path = APPPATH."google_routes/";
	}
	public function index(){
		return $this->db->get(self::table)->result();
	}
	public function getRoute($id){
		$route = $this->db->select('*')
						->from(self::table)
						->where(self::table.".id_routes = {$id}")
						->get()->result();

		if(isset($route[0])){
			//recover google route
			$googleRoute = file_get_contents(self::$google_routes_path.$id);
			$route[0]->googleRoute = json_decode($googleRoute, JSON_NUMERIC_CHECK);
			return $route[0];
		}
		
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

		//write google route information
		file_put_contents(self::$google_routes_path.$id, $route->googleRoute);

		return $id;
	}
	public function updateRoute($route){
		//update route
		$id = $route->id_routes;
		if(isset($route->name) || isset($route->description)){
			$this->db->select("*")->from(self::table)->where("id_routes = ".$id);

			if(isset($route->name))
				$this->db->set("name", $route->name);
			if(isset($route->description))
			$this->db->set('description', $route->description)->update();
		}

		if(isset($route->points)){
			//insert points
			$points = $route->points;
			$length = count($points);
			for($i = 0; $i < $length; $i++){
				$points[$i]->id_routes = $id;
			}

			$this->deletePoints($id);
			$this->db->insert_batch(self::pointsTable, $points, "id_routes");

			//write google route information
			file_put_contents(self::$google_routes_path.$id, $route->googleRoute);
		}

		return $id;
	}
	public function getBuses($id){
		return $this->db->select("id_bus")
						->from(self::busTable)
						->where("id_routes = {$id}")
						->get()->result();
	}
	public function deleteRoute($id){
		$this->deletePoints($id);

		$this->db->where('id_routes', $id);
   		$this->db->delete(self::table);
	}
	public function deletePoints($id){
		$this->db->where('id_routes', $id);
		$this->db->delete(self::pointsTable);
	}
}

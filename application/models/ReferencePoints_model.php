<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ReferencePoints_model extends CI_Model{
	public $id_routes;
	public $name;
	public $image;
	public $description;
	public $latitude;
	public $longitude;

	const table = 'reference_points';

	public function __construct(){
		parent::__construct();
	}
	public function index($id_routes){
		return $this->db->select('*')
						->from(self::table)
						->where(self::table.".id_routes = {$id_routes}")
						->get()->result();
	}
	public function insert($reference){
		//insert route
		$this->id_routes = $reference->id_routes;
		$this->name = $reference->name;
		$this->image = $reference->image;
		$this->description = $reference->description;
		$this->latitude = $reference->location->latitude;
		$this->longitude = $reference->location->longitude;

		$result = $this->db->insert(self::table, $this);
		return $result;
	}
}

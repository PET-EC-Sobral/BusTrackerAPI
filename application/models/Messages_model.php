<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Messages_model extends CI_Model{
	public $title;
	public $message;
	public $id_bus;
	public $id_routes;
	public $date;
	const table = 'messages';
	const pointsTable = 'points_route';
	const busTable = 'bus';
	const notificationPositionTable = 'notifications_update_position';
	public static $google_routes_path;

	public function __construct(){
		parent::__construct();
	}
	public function index($idRoute, $idBus){
		$busConditionSearch = !empty($idBus) ? "id_bus = ".$idBus : "id_bus IS NULL";
		$busConditionSearch = $idBus === 0 ? "TRUE" : $busConditionSearch;
		$selectedCollums = "id_messages, id_routes, title, message, date";
		$selectedCollums .= !empty($idBus) || $idBus === 0 ? ", id_bus" : "";

		return $this->db->select($selectedCollums)->from(self::table)
						->where("id_routes = ".$idRoute." AND ".$busConditionSearch)
						->order_by("date", "desc")->get()->result();
	}
	public function insert($notification){
		$this->title = $notification->title;
		$this->message = $notification->message;
		$this->id_bus = $notification->id_bus;
		$this->id_routes = $notification->id_routes;
		$this->date = date('Y-m-d H:i:s');

		$this->db->insert(self::table, $this);
		$id = $this->db->insert_id();

		return $id;
	}

}

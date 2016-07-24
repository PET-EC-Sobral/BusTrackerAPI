<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Messages_model extends CI_Model{
	public $title;
	public $message;
	public $id_bus;
	public $id_routes;
	public $date;
	const table = 'messages';
	const registeredNotificationsTable = 'registered_notifications_messages';

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
	public function insertNotificationRegistration($notificationRegistration){
		$registry = new stdClass();
		$registry->email = $notificationRegistration->email;
		$registry->id_routes = $notificationRegistration->id_routes;
		$registry->registration_token_firebase = $notificationRegistration->registration_token_firebase;

		$this->db->insert(self::registeredNotificationsTable, $registry);

		if($this->db->affected_rows() < 1)
			return false;

		return true;

	}
	public function getNotificationRegistration($idRoute){
		$result = $this->db->select("registration_token_firebase")
						   ->from(self::registeredNotificationsTable)
						   ->where("id_routes = ".$idRoute)->get()->result();
		$arrayIds = [];
		foreach ($result as $row ) {
			$arrayIds[] = $row->registration_token_firebase;
		}
		return $arrayIds;
	}
}

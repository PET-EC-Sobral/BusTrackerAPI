<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model{
	public $name;
	public $email;
	public $password;
	public $permission;

	const table = "users";

	public function insert($user){
		$this->email = $user->email;
		$this->name = $user->name;
		$this->password = $user->password;
		$this->permission = $user->permission;

		$this->db->insert(self::table, $this);

		if($this->db->affected_rows() < 1)
			return false;

		return true;
	}
	public function get($user){
		$result = $this->db->select("email, name, permission, password")
						    ->from(self::table)
						    ->where("email = '{$user->email}' AND `password` = '{$user->password}'")
						    ->get()->result();

		if(count($result) < 1)
			return null;

		return $result[0];
	}
}
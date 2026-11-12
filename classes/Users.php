<?php
require_once('../config.php');
Class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
		if (function_exists('mysqli_report')) {
			mysqli_report(MYSQLI_REPORT_OFF);
		}
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function save_users(){
		if(!$this->conn || !($this->conn instanceof mysqli) || @$this->conn->connect_errno){
			return 2;
		}
		if(!isset($_POST['status']) && $this->settings->userdata('login_type') == 1){
			$_POST['status'] = 1;
			$_POST['type'] = 2;
		}
		extract($_POST);
		$oid = $id ?? '';
		$data = '';
		if(isset($oldpassword)){
			if(md5($oldpassword) != $this->settings->userdata('password')){
				return 4;
			}
		}
		$chk_res = $this->conn->query("SELECT * FROM `users` where username ='{$username}' ".(!empty($id) && $id>0? " and id!= '{$id}' " : ""));
		$chk = ($chk_res && $chk_res instanceof mysqli_result) ? $chk_res->num_rows : 0;
		if($chk > 0){
			return 3;
			exit;
		}
		foreach($_POST as $k => $v){
			if(in_array($k,array('firstname','middlename','lastname','username','type'))){
				if(!empty($data)) $data .=" , ";
				$v = $this->conn->real_escape_string($v);
				$data .= " {$k} = '{$v}' ";
			}
		}
		if(!empty($password)){
			$password = md5($password);
			if(!empty($data)) $data .=" , ";
			$data .= " `password` = '{$password}' ";
		}

		if(empty($id)){
			$qry = $this->conn->query("INSERT INTO users set {$data}");
			if($qry){
				$id = $this->conn->insert_id;
				$this->settings->set_flashdata('success','User Details successfully saved.');
				$resp['status'] = 1;
			}else{
				$resp['status'] = 2;
			}

		}else{
			$qry = $this->conn->query("UPDATE users set $data where id = {$id}");
			if($qry){
				$this->settings->set_flashdata('success','User Details successfully updated.');
				if($id == $this->settings->userdata('id')){
					foreach($_POST as $k => $v){
						if($k != 'id'){
							if(!empty($data)) $data .=" , ";
							$this->settings->set_userdata($k,$v);
						}
					}
					
				}
				$resp['status'] = 1;
			}else{
				$resp['status'] = 2;
			}
			
		}
		
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = 'uploads/avatar-'.$id.'.png';
			$dir_path =base_app. $fname;
			$upload = $_FILES['img']['tmp_name'];
			$type = mime_content_type($upload);
			$allowed = array('image/png','image/jpeg');
			if(!in_array($type,$allowed)){
				$resp['msg'].=" But Image failed to upload due to invalid file type.";
			}else{
				$new_height = 200; 
				$new_width = 200; 
		
				list($width, $height) = getimagesize($upload);
				$t_image = imagecreatetruecolor($new_width, $new_height);
				imagealphablending( $t_image, false );
				imagesavealpha( $t_image, true );
				$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
				imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				if($gdImg){
						if(is_file($dir_path))
						unlink($dir_path);
						$uploaded_img = imagepng($t_image,$dir_path);
						imagedestroy($gdImg);
						imagedestroy($t_image);
				}else{
				$resp['msg'].=" But Image failed to upload due to unkown reason.";
				}
			}
			if(isset($uploaded_img)){
				$this->conn->query("UPDATE users set `avatar` = CONCAT('{$fname}','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$id}' ");
				if($id == $this->settings->userdata('id')){
						$this->settings->set_userdata('avatar',$fname);
				}
			}
		}
		if(isset($resp['msg']))
		$this->settings->set_flashdata('success',$resp['msg']);
		return  $resp['status'];
	}
	public function delete_users(){
		if(!$this->conn || !($this->conn instanceof mysqli) || @$this->conn->connect_errno){
			return json_encode(array('status'=>'failed'));
		}
		extract($_POST);
		$av_res = $this->conn->query("SELECT avatar FROM users where id = '{$id}'");
		$avatar = ($av_res && $av_res instanceof mysqli_result && $av_row = $av_res->fetch_array()) ? $av_row['avatar'] : '';
		$qry = $this->conn->query("DELETE FROM users where id = $id");
		if($qry){
			if(!empty($avatar)){
				$avatar = explode("?",$avatar)[0];
				if(is_file(base_app.$avatar))
					unlink(base_app.$avatar);
			}
			$this->settings->set_flashdata('success','User Details successfully deleted.');
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	public function verify_user(){
		if(!$this->conn || !($this->conn instanceof mysqli) || @$this->conn->connect_errno){
			return json_encode(array('status'=>'failed'));
		}
		extract($_POST);
		$update = $this->conn->query("UPDATE `users` set `status` = 1 where id = $id");
		if($update){
			$this->settings->set_flashdata('success','User has successfully verified.');
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	
}

$users = new users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
	case 'save':
		echo $users->save_users();
	break;
	case 'delete':
		echo $users->delete_users();
	break;
	case 'verify_user':
		echo $users->verify_user();
	break;
	default:
		// echo $sysset->index();
		break;
}

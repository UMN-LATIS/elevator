<?header('HTTP/1.0 401 Unauthorized');?>
<div class="panel panel-info">
	<div class="panel-heading">
		<h3 class="panel-title">Invalid Permissions</h3>
	</div>
	<div class="panel-body">
		You do not have permission to access this resource.  If you'd like assistance, please get in touch, or use the login form below.
	</div>
</div>
<?=$this->load->view("login/login.php", ["redirectURL"=>current_url()]);?>

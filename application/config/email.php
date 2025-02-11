<?

$config["smtp_host"] = getenv('SMTP_HOST');
$config["protocol"] = "smtp";
$config["smtp_port"] = 465;
$config["smtp_user"] = getenv('SMTP_USER');
$config["smtp_pass"] = getenv('SMTP_PASSWORD');
$config["mailtype"] = "html";
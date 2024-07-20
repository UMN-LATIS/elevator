<?

$config["smtp_host"] = $_ENV['SMTP_HOST'];
$config["protocol"] = "smtp";
$config["smtp_port"] = 465;
$config["smtp_user"] = $_ENV['SMTP_USER'];
$config["smtp_pass"] = $_ENV['SMTP_PASSWORD'];
$config["mailtype"] = "html";
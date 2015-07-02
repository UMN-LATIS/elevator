<?php
if (empty($return)) {
  $msg = "Local logout completed: You must close your browser to complete the logout process.";
  $msg .= "<br/> (You only logged out of the local Service Provider, not the Identity Provider)";
}
if (!empty($return)) {
  $msg = "Central logout completed: You logged out of the Identity Provider and provided a URL to return somewhere else.";
  $msg .= "<br/>In a real Shibboleth SP, you would not see a screen like this, and instead return directly to the URL linked below.";
}
?>
<!DOCTYPE html>
<head>
  <title>ShibPoseur Fake Logout</title>
</head>
<body>
  <h1 id="shibposeur-title">ShibPoseur Fake Logout Screen</h1>
  <h2> You are now logged out. </h2>
  <div>
    If you weren't using a fake Shibboleth SP stand-in, this would be a logout confirmation screen.
  </div>
  <div>
    <?php echo $msg; ?>
  </div>
  <div>
    <?php if (!empty($return)): ?>
    <a href='<?php echo $return; ?>'>Click here to proceed to your destination...</a>
    <?php endif; ?>
  </div>
</body>


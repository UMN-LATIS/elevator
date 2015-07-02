<!DOCTYPE html>
<head>
  <title>ShibPoseur Fake Login</title>
</head>
<body>
  <h1 id="shibposeur-title">ShibPoseur Fake Login Screen</h1>
  <div>
    If you weren't using a fake Shibboleth SP stand-in, this would be a login screen.
  </div>
  <div>
    Click "Login" to proceed...
    <form action='' method='post'>
      <input type='hidden' name='shibposeur-login' value='1' />
      <input type='hidden' name='target' value='<?php echo htmlspecialchars($target, ENT_QUOTES); ?>' />
      <label for='fakeuid'>Fake Username</label>
      <select id='fakeuid' name='shibposeur-uid'>
        <option>Choose a user</option>
        <?php foreach ($this->users as $user => $attrs): ?>
          <option value='<?php echo $user; ?>'><?php echo $user; ?></option>
        <?php endforeach; ?>
      </select><br />
      <label for='fakeuid-other'>Or enter a username:</label>
      <input id='fakeuid-other' name='shibposeur-uid-other' /><br/>
      <input type='submit' name='shibposeur-submit' value='Login' />
    </form>
  </div>
</body>

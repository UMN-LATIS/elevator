<!DOCTYPE html>
<head>
  <title>ShibPoseur Fake Session</title>
</head>
<body>
  <h1 id="shibposeur-title">ShibPoseur Fake Session Info</h1>
  <div>
    <table>
      <thead>
        <tr><th>Attribute</th><th>Value</th></tr>
      </thead>
      <tbody>
      <?php
        foreach ($this->valid_attributes as $attr) {
          echo "<tr><td>" . htmlspecialchars($attr) . "</td><td> " . (isset($_SERVER[$attr]) ? $_SERVER[$attr] : '') . "</td></tr>\n";
        }
      ?>
      </tbody>
    </table>
  </div>
</body>


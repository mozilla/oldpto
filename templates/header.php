<!DOCTYPE html>
<html lang="en-US" dir="ltr">
  <head>
    <title>Mozilla PTO</title>
    <meta charset="utf-8" />
    <script src="./js/jquery-1.3.2.min.js"></script>
    <script src="./js/jquery-ui-1.7.2.custom.min.js"></script>
	<script src="./js/jquery.json-2.2.min.js"></script>
    <script src="./js/jquery.cookie.js"></script>
	<script src="./js/jquery.dom.js"></script>
    <link rel="stylesheet" href="./css/style.css" />
    <link rel="stylesheet" href="./css/redmond/jquery-ui-1.7.2.custom.css" />
    <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />
  </head>

  <body>
  <div id="page">

  <header>
    <h1>PTO Notification</h1>
    <nav>
      <ul id="menu">
        <li><a href="./">Notify</a></li>
        <li><a href="./mypto.php">My PTO</a></li>
        <?php
          if (in_array($GLOBAL_AUTH_USERNAME, $export_users)) {
            ?><li><a href="./export.php">List</a></li>
       	    <li><a href="./report.php">Report</a></li><?php
	  }
        ?>
      </ul>
    </nav>
  </header>

  <section>

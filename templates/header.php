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

  <header>
    <div class="alert">
      <p>Starting January 1, 2023, we are switching over to use <a href="https://www.myworkday.com/vhr_mozilla/">Workday</a> as our time off tracking system.<br>For any time off taken in 2022, please continue to submit those requests into this system.<br>For future time off requests in 2023, please refrain from submitting those requests here and submit them into Workday on January 1 or after.<br>If you have questions, reach out to peopleops@mozilla.com</p>
    </div>

    <div class="topnav">
      <div class="topnav-brand"></div>

      <div class="topnav-right">
        <a href="./">Notify</a>
        <a href="./mypto.php">My PTO</a>
        <?php
            if (in_array($GLOBAL_AUTH_USERNAME, $export_users)) {
              ?><a href="./export.php">List</a>
              <a href="./report.php">Report</a><?php
            }
        ?>
      </div>
    </div>
  </header>

  <div id="page">

  <section>

<?php
//
// message.php
//
// used as a "filler" page. When given this page argument, index.php will not display any pages, but will instaid display error and/or notification messages only.
//
//      USAGE (FOR ERROR MESSAGE):
//      $_SESSION["error"]["message"];
//      header("Location: ../index.php?page=message.php");
//      exit(0);
//
//      USAGE (FOR NOTIFICAITON):
//      $_SESSION["notification"]["message"];
//      header("Location: ../index.php?page=message.php");
//      exit(0);
//

// end the page
$_SESSION["error"]["pagestate"] = 0;

?>

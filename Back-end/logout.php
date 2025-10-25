<?php
session_start();
session_destroy();
header("Location: ../Front-end/InicioDeSesion.htm");
exit();
?>
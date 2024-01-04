<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?php echo $diagram->getName(); ?></title>
    </head>
    <body>
<?php
    $diagram->draw();
?>
    </body>
</html>

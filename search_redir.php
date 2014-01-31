<?php
if(isset($_GET['md5']))
    header("Location: /advsearch.php?srchtype=posts&board={$_GET['b']}&threadtype=all&flashtype=all&comment=&filter%5B0%5D=md5&operator%5B0%5D=%3D&value%5B0%5D=".str_replace("+","%2B",urlencode(str_replace("/","-",$_GET['md5'])))."&sort%5B0%5D=no&order%5B0%5D=asc&perpage=100");
if(isset($_GET['filename']))
    header("Location: /advsearch.php?srchtype=posts&board={$_GET['b']}&threadtype=all&flashtype=all&comment=&filter%5B0%5D=filename&operator%5B0%5D=%3D&value%5B0%5D=".str_replace("+","%2B",urlencode($_GET['filename']))."&sort%5B0%5D=no&order%5B0%5D=asc&perpage=100");
if(isset($_GET['id']))
    header("Location: /advsearch.php?srchtype=posts&board={$_GET['b']}&threadtype=all&flashtype=all&comment=&filter%5B0%5D=id&operator%5B0%5D=%3D&value%5B0%5D=".str_replace("+","%2B",urlencode($_GET['id']))."&sort%5B0%5D=no&order%5B0%5D=asc&perpage=100");
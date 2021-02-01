<?php
require "./lib/constantes.inc.php";

function ConnectDb()
{
    static $db = null;

    // Première visite de la fonction
    if ($db == null) {
        // Essaie le code ci-dessous
        try {
            $db = new PDO('mysql:host=' . HOST . ';dbname=' . DBNAME, DBUSER, DBPWD, array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_PERSISTENT => true
            ));
        }
        // Si une exception est arrivée
        catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage() . '<br />';
            echo 'N° : ' . $e->getCode();
            // Quitte le script et meurt
            die('Could not connect to MySQL');
        }
    }
    // Pas d'erreur, retourne un connecteur
    return $db;
}

function getAllPost()
{
    static $ps = null;
    $sql = "SELECT * FROM `post`";
    if ($ps == null) {
        $ps = ConnectDb()->prepare($sql);
    }
    $answer = false;
    try {
        if ($ps->execute()) {
            $answer = $ps->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        echo "Error : " . $e;
    }
    return $answer;
}

function addNewPost($commentaire, $images)
{
    $date = date('d.m.y');
    $sql = "INSERT INTO `post` (`commentaire`,`datePost`) VALUES (:com,:date); ";
    $ps = ConnectDb()->prepare($sql);
    try {
        $ps->bindParam(":com",$commentaire,PDO::PARAM_STR);
        $ps->bindParam(":date",$date);

        $ps->execute();
    } catch (Exception $e) {
        echo $e;
    }


    $sql = "INSERT INTO `Media` (`nomFichierMedia`,`typeMedia`,idPost) VALUES (:name,:type,(SELECT idpost FROM post WHERE commentaire=:com AND datePost=:date))";
    $ps=ConnectDb()->prepare($sql);
    try {
        $ps->bindParam(":com",$commentaire,PDO::PARAM_STR);
        $ps->bindParam(":date",$date);

       for($i=0; $i < Count($images["name"]);$i++){
            $ps->bindParam(":name",$images["name"][$i],PDO::PARAM_STR);
            $ps->bindParam(":type",$images["type"][$i],PDO::PARAM_STR);
            $ps->execute();
       }
    } catch (Exception $e) {
        echo $e;
    }
}

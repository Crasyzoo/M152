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
    $date = date('Y-m-d');
    $sql = "INSERT INTO `post` (`commentaire`,`creationDate`) VALUES (:com,:date);";
    $ps = ConnectDb()->prepare($sql);
    try {
        $ps->bindParam(":com", $commentaire, PDO::PARAM_STR);
        $ps->bindParam(":date", $date);

        $ps->execute();
    } catch (Exception $e) {
        echo $e;
    }


    $sql = "INSERT INTO `Media` (`nomFichierMedia`,`typeMedia`,`creationDate`,`idPost`) VALUES (:name,:type,:date,(SELECT idpost FROM post WHERE commentaire=:com AND creationDate=:date))";
    $ps = ConnectDb()->prepare($sql);
    try {
        $ps->bindParam(":com", $commentaire, PDO::PARAM_STR);
        $ps->bindParam(":date", $date);
        foreach ($images as $value) {
            $ps->bindParam(":name", $value["name"], PDO::PARAM_STR);
            $ps->bindParam(":type", $value["type"], PDO::PARAM_STR);
            $ps->execute();
        }
    } catch (Exception $e) {
        echo $e;
    }
}

function getAllImagesFromAPost($idPost)
{
    static $ps = null;
    $sql = "SELECT * FROM Media WHERE idPost=:idPost";

    if ($ps == null) {
        $ps = ConnectDb()->prepare($sql);
    }
    $answer = null;
    try {
        $ps->bindParam(":idPost", $idPost, PDO::PARAM_INT);
        if ($ps->execute()) {
            $answer = $ps->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        echo $e;
    }
    return $answer;
}

function DisplayPosts()
{
    $posts = getAllPost();
    foreach ($posts as $post) {
        $images = getAllImagesFromAPost($post["idpost"]);
        echo " <div class=\"container-fluid mx-auto my-4\" style=\"width: 35rem;\">";
        echo "\n\t<div class=\"card float-start mx-1\" style=\"width: 30rem;\">";
        //------------------------------------------------------------------------------------------------------
        if (count($images) > 1) {
            echo  "<div id=\"carouselExampleIndicators\" class=\"carousel slide\" data-ride=\"carousel\">";
            echo "<ol class=\"carousel-indicators\">";
            $compteur = 0;
            foreach ($images as $value) {
                echo sprintf("<li data-target=\"#carouselExampleIndicators\" data-slide-to=\"%s\" class=\"%s\"></li>", $compteur, $compteur == 0 ? "active" : "");
                $compteur++;
            }
            echo "</ol>";
            echo "<div class=\"carousel-inner\">";

            $compteur = 0;
            foreach ($images as $value) {

                echo sprintf("<div class=\"carousel-item %s\">", $compteur == 0 ? "active" : "");
                echo "<img class=\"d-block w-100\" src=\"img/" . $value["nomFichierMedia"] . "\" alt=\"First slide\">";
                echo "</div>";
                $compteur++;
            }
            echo "</div>";
            echo "<a class=\"carousel-control-prev\" href=\"#carouselExampleIndicators\" role=\"button\" data-slide=\"prev\">";
            echo "<span class=\"carousel-control-prev-icon\" aria-hidden=\"true\"></span>";
            echo "<span class=\"sr-only\">Previous</span>";
            echo "</a>";
            echo "<a class=\"carousel-control-next\" href=\"#carouselExampleIndicators\" role=\"button\" data-slide=\"next\">";
            echo "<span class=\"carousel-control-next-icon\" aria-hidden=\"true\"></span>";
            echo  "<span class=\"sr-only\">Next</span>";
            echo "</a>";
            echo "</div>";
        } else {
            echo "\n\t\t<img src=\"img/" . $images[0]["nomFichierMedia"] . "\" class=\"card-img-top\" alt=\"...\">";
        }
        //------------------------------------------------------------------------------------------------------
        echo "\n\t\t\t<div class=\"card-body\">";
        echo "\n\t\t\t\t<p class=\"card-text\">" . $post["commentaire"] . "</p>";
        echo "\n\t\t\t</div>";
        echo "\n\t</div>";
        echo "\n\t<form action=\"\" method=\"post\">";
        echo "\n\t\t<button class=\"btn btn-primary float-end\" type=\"submit\" name=\"action\" value=\"edit/" . $post["idpost"] . "\">";
        echo "\n\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-pen\" viewBox=\"0 0 16 16\">";
        echo "\n\t\t\t\t<path d=\"M13.498.795l.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001zm-.644.766a.5.5 0 0 0-.707 0L1.95 11.756l-.764 3.057 3.057-.764L14.44 3.854a.5.5 0 0 0 0-.708l-1.585-1.585z\" />";
        echo "\n\t\t\t</svg>";
        echo "\n\t\t</button>";
        echo "\n\t\t<button class=\"btn btn-primary float-end mt-1\" type=\"submit\" name=\"action\" value=\"delete/" . $post["idpost"] . "\">";
        echo "\n\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-trash\" viewBox=\"0 0 16 16\">";
        echo "\n\t\t\t\t<path d=\"M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z\" />";
        echo "\n\t\t\t\t<path fill-rule=\"evenodd\" d=\"M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z\" />";
        echo "\n\t\t\t</svg>";
        echo "\n\t\t</button>";
        echo "\n\t</form>";
        echo "</div>";
    }
}

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
    static $ps = null;
    static $ps2 = null;
    $date = date('Y-m-d H:i:s');
    $sql = "INSERT INTO `post` (`commentaire`,`creationDate`) VALUES (:com,:date);";
    $sql2 = "INSERT INTO `Media` (`nomFichierMedia`,`typeMedia`,`creationDate`,`idPost`) VALUES (:name,:type,:date,(SELECT idPost FROM post WHERE commentaire=:com AND creationDate=:date))";
    if ($ps == null) {
        $ps2 = ConnectDb()->prepare($sql2);
        $ps = ConnectDb()->prepare($sql);
    }
    $answer = false;
    try {
        ConnectDb()->beginTransaction();
        $ps->bindParam(":com", $commentaire, PDO::PARAM_STR);
        $ps->bindParam(":date", $date);
        if ($ps->execute() && $images != []) {
            $ps2->bindParam(":com", $commentaire, PDO::PARAM_STR);
            $ps2->bindParam(":date", $date);
            foreach ($images as $value) {
                $ps2->bindParam(":name", $value["name"], PDO::PARAM_STR);
                $ps2->bindParam(":type", $value["type"], PDO::PARAM_STR);
                $ps2->execute();
            }
        }
        ConnectDb()->commit();
        $answer = true;
    } catch (Exception $e) {
        ConnectDb()->rollBack();
        foreach ($images as $value) {
            if (file_exists("./img/" .  $value["name"])) {
                unlink("./img/" . $value["name"]);
            }
        }

        echo $e;
    }
    return $answer;
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
    echo "<table class=\"mx-auto\">";
    foreach ($posts as $post) {
        $images = getAllImagesFromAPost($post["idPost"]);
        echo "<tr><td>";
        echo " <div class=\"container-fluid  my-4\" style=\"width: 35rem;\">";
        echo "\n\t<div class=\"card float-start mx-1\" style=\"width: 30rem; height: 28rem;\">";
        //------------------------------------------------Affiche les images en carousel si il y en a plusieurs------------------------------------------------------
        if (count($images) > 1) {
            echo  sprintf("<div id=\"carouselExampleIndicators%s\" class=\"carousel slide\" data-ride=\"carousel\">", $post["idPost"]);
            echo "<ol class=\"carousel-indicators\">";
            $compteur = 0;
            foreach ($images as $value) {
                echo sprintf("<li data-target=\"#carouselExampleIndicators%s\" data-slide-to=\"%s\" class=\"%s\"></li>", $post["idPost"], $compteur, $compteur == 0 ? "active" : "");
                $compteur++;
            }
            echo "</ol>";
            echo "<div class=\"carousel-inner\">";

            $compteur = 0;
            foreach ($images as $value) {
                echo sprintf("<div class=\"carousel-item %s\">", $compteur == 0 ? "active" : "");
                echo "<div style=\"height: 25rem;\" class=\"position-relative\">";
                if (strstr($value["typeMedia"], "image/")) {
                    echo "<img class=\"d-block w-100 img-fluid\" style=\"height: 100%; width: 100%;\" src=\"img/" . $value["nomFichierMedia"] . "\" alt=\"First slide\">";
                } else if (strstr($value["typeMedia"], "audio/")) {
                    echo "<audio class=\"d-block position-absolute\" style=\"top: 25%; left: 12.5%; width: 75%;\" controls src=\"img/" . $value["nomFichierMedia"] . "\" alt=\"First slide\">";
                } else if (strstr($value["typeMedia"], "video/")) {
                    echo "<video class=\"embed-responsive-item position-absolute\" controls style=\"height: 75%; width: 75%; left: 14%\" src=\"img/" . $value["nomFichierMedia"] . "\" preload=\"auto\" autoplay=\"true\" loop muted allowfullscreen></video>";
                }
                echo "</div>";
                echo "</div>";
                $compteur++;
            }
            echo "</div>";
            echo sprintf("<a class=\"carousel-control-prev text-dark\" href=\"#carouselExampleIndicators%s\" role=\"button\" data-slide=\"prev\">", $post["idPost"]);
            echo "<span class=\"carousel-control-prev-icon\" aria-hidden=\"true\"></span>";
            echo "<span class=\"sr-only\">Previous</span>";
            echo "</a>";
            echo sprintf("<a class=\"carousel-control-next text-dark\" href=\"#carouselExampleIndicators%s\" role=\"button\" data-slide=\"next\">", $post["idPost"]);
            echo "<span class=\"carousel-control-next-icon\" aria-hidden=\"true\"></span>";
            echo "<span class=\"sr-only\">Next</span>";
            echo "</a>";
            echo "</div>";
        } else {
            if (strstr($images[0]["typeMedia"], "image/")) {
                echo "<img class=\"d-block w-100 img-fluid\" style=\"height: 25rem;\" src=\"./img/" . $images[0]["nomFichierMedia"] . "\" alt=\"First slide\">";
            } else if (strstr($images[0]["typeMedia"], "audio/")) {
                echo "<div style=\"height: 25rem;\" class=\"position-relative\"><audio class=\"d-block position-absolute\" style=\"top: 25%; left: 12.5%; width: 75%;\" controls src=\"img/" . $images[0]["nomFichierMedia"]  . "\" alt=\"First slide\"></div>";
            } else if (strstr($images[0]["typeMedia"], "video/")) {
                echo "<video class=\"embed-responsive-item\" controls style=\"height: 25rem;\" src=\"img/" . $images[0]["nomFichierMedia"] . "\" preload=\"auto\" autoplay=\"true\" loop muted allowfullscreen></video>";
            }
        }
        //------------------------------------------------------------------------------------------------------
        echo "\n\t\t\t<div class=\"card-body\">";
        echo "\n\t\t\t\t<p class=\"card-text\">" . $post["commentaire"] . "</p>";
        echo "\n\t\t\t</div>";
        echo "\n\t</div>";
        echo "\n\t<form action=\"\" method=\"post\">";
        echo "\n\t\t<button class=\"btn btn-primary float-end\" type=\"submit\" name=\"action\" value=\"edit/" . $post["idPost"] . "\">";
        echo "\n\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-pen\" viewBox=\"0 0 16 16\">";
        echo "\n\t\t\t\t<path d=\"M13.498.795l.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001zm-.644.766a.5.5 0 0 0-.707 0L1.95 11.756l-.764 3.057 3.057-.764L14.44 3.854a.5.5 0 0 0 0-.708l-1.585-1.585z\" />";
        echo "\n\t\t\t</svg>";
        echo "\n\t\t</button>";
        echo "\n\t\t<button class=\"btn btn-danger float-end mt-1\" type=\"submit\" name=\"action\" value=\"delete/" . $post["idPost"] . "\">";
        echo "\n\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-trash\" viewBox=\"0 0 16 16\">";
        echo "\n\t\t\t\t<path d=\"M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z\" />";
        echo "\n\t\t\t\t<path fill-rule=\"evenodd\" d=\"M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z\" />";
        echo "\n\t\t\t</svg>";
        echo "\n\t\t</button>";
        echo "\n\t</form>";
        echo "</div>";
        echo "</td></tr>";
    }
}

function DeletePost($idPost){
    static $ps=null;
    
}

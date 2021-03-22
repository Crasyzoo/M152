<?php
session_start();
include "./lib/functions.inc.php";


$modPost = $_SESSION["modPost"];
$com = "";
$idPost = -1;
$imagesDuPost = [];
if ($modPost == "update") {
    $idPost = $_SESSION["idPost"];
    $post = GetAPost($idPost);
    $com = $post[0]["commentaire"];
    $imagesDuPost = getAllImagesFromAPost($idPost);
  
}



$imgs = $_FILES["imgs"];
var_dump($imgs);
$description = filter_input(INPUT_POST, "description");

$action = filter_input(INPUT_POST, "action");

$uploadFileState = true;
$imagesValide = [];
$fullSize = 0;
if ($imgs["name"][0] != "") {
    foreach ($imgs["size"] as $value) {
        $fullSize += $value;
    }
}

// test que la taille de l'ensemble des images est de 70 mega maximum
if ($fullSize <= 70 * pow(10, 6) && $fullSize != 0) {
    for ($i = 0; $i < Count($imgs["name"]); $i++) {
        // test que le fichier recu est bien une image et a une taille de 3 mega
        if (strstr($imgs["type"][$i], "image/") && $imgs["size"][$i] <= 3 * pow(10, 6) || strstr($imgs["type"][$i], "audio/mpeg") || strstr($imgs["type"][$i], "video/mp4")) {
            $newNom = uniqid($imgs["name"][$i]);
            array_push($imagesValide, ["name" => $newNom, "type" => $imgs["type"][$i]]);
            // verifie si le fichier actuel existe deja sur le serveur, si non alors il l'enregistre
            if (!file_exists("./img/" . $imgs["name"][$i])) {
                if (!move_uploaded_file($imgs["tmp_name"][$i], "./img/" . $newNom)) {
                    $uploadFileState = false;
                }
            }
        } else {
            $imagesValide = null;
            echo "l'image n'est pas valide";
            break;
        }
    }
}

if ($description && $description != "") {
    switch ($action) {
        case "new":
            // si l'upload des fichiers c'est bien passer alors on ajoute a la db sinon on supprime tout les fichiers qui ont ete upload par ce post 
            if ($uploadFileState) {
                if (addNewPost($description, $imagesValide)) {
                    header('Location: home.php');
                }
            } else {
                foreach ($imagesValide as $value) {
                    if (file_exists("./img/" .  $value["name"])) {
                        unlink("./img/" . $value["name"]);
                    }
                }
            }
            break;
        case "update":
            if($fullSize != 0){
                foreach($imagesDuPost as $value){
                    if (file_exists("./img/" .  $value["name"])) {
                        unlink("./img/" . $value["name"]);
                    }
                }
            }
            UpdatePost($idPost, $description, $imagesValide);
            $_SESSION["modPost"] = "new";
          //  header('Location: home.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Facebook Page Tab App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>

    <div class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="navbar-toggler-icon"></span>
            </a>
            <a class="nav-link" href="#">M152</a>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="home.php"><i class="bi bi-house-door-fill"></i>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="post.php">POST</a></li>
                </ul>
            </div>
            <!--/.nav-collapse -->
        </div>
    </div>

    <div class="mt-4">
        <div class="mx-auto">
            <div class="container-sm">
                <div class="text-center">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="col-auto">
                            <label class="col-form-label">Image a poster :</label>
                        </div>
                        <div class="col-auto">
                            <input class="form-control" type="file" name="imgs[]" id="imgs" accept="image/*, audio/*, video/*" multiple><br>
                        </div>
                        <div class="container-fluid">
                            <?php
                            echo "<label class=\"float-start\">Images Actuelles</label>";
                            if ($modPost == "update") {
                                foreach ($imagesDuPost as $value) {

                                    $type = explode("/", $value["typeMedia"]);
                                    echo "<div style=\"height: 15rem; width: 15rem; float: left; margin: 5px;\" class=\"position-relative\">";
                                    switch ($type[0]) {
                                        case "image":
                                            echo "<img class=\"d-block w-100 img-fluid\" style=\"height: 100%; width: 100%%;\" src=\"img/" . $value["nomFichierMedia"] . "\" alt=\"First slide\">";
                                            break;
                                        case "video":
                                            echo "<video class=\"embed-responsive-item position-absolute\" controls style=\"height: 75%; width: 75%; left: 14%\" src=\"img/" . $value["nomFichierMedia"] . "\" preload=\"auto\" autoplay=\"true\" loop muted allowfullscreen></video>";
                                            break;
                                        case "audio":
                                            echo "<audio class=\"d-block position-absolute\" style=\"top: 25%; left: 12.5%; width: 75%;\" controls src=\"img/" . $value["nomFichierMedia"] . "\" alt=\"First slide\">";
                                            break;
                                    }
                                    echo "</div>";
                                }
                            }
                            ?>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">Description :</label>
                        </div>
                        <div class="col-auto">
                            <textarea class="col-form-control" name="description" required><?= $com ?></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit" name="action" value="<?= $modPost ?>">valider</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="fb-root">
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
</body>

</html>
<?php
include "./lib/functions.inc.php";

$imgs = $_FILES["imgs"];
var_dump($imgs);
$description = filter_input(INPUT_POST, "description");

$action = filter_input(INPUT_POST, "action");

switch ($action) {
    case "post":
        $imagesValide = [];
        $fullSize = 0;
        if ($description && $description != "") {
            foreach ($imgs["size"] as $value) {
                $fullSize += $value;
            }
            // test que la taille de l'ensemble des images est de 70 mega maximum
            if ($fullSize <= 70 * pow(10, 6)) {
                for ($i = 0; $i < Count($imgs["name"]); $i++) {
                    // test que le fichier recu est bien une image et a une taille de 3 mega
                    if (strstr($imgs["type"][$i], "image/") && $imgs["size"][$i] <= 3 * pow(10, 6) || strstr($imgs["type"][$i], "audio/mpeg") || strstr($imgs["type"][$i], "video/mp4")) {
                        $newNom = uniqid($imgs["name"][$i]);
                        array_push($imagesValide, ["name" => $newNom, "type" => $imgs["type"][$i]]);
                        // verifie si le fichier actuel existe deja sur le serveur, si non alors il l'enregistre
                        if (!file_exists("./img/" . $imgs["name"][$i])) {
                            move_uploaded_file($imgs["tmp_name"][$i], "./img/" . $newNom);
                        }
                    } else {
                        $imagesValide = [];
                        echo "l'image n'est pas valide";
                        break;
                    }
                }
                if ($imagesValide != []) {
                    // verifie si chaque fichier a bien été upload avant d'enregistrer dans la base
                    $uploadFileExist = true;
                    foreach ($imagesValide as $value) {
                        if (!file_exists("./img/" . $value["name"])) {
                            $uploadFileExist = false;
                        }
                    }
                    if ($uploadFileExist) {
                        addNewPost($description, $imagesValide);
                        header('Location: home.php');
                    }
                }
            }
        }
        break;
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
                            <input class="form-control" type="file" name="imgs[]" id="imgs" accept="image/*, audio/mp3, video/mp4" multiple><br>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">Description :</label>
                        </div>
                        <div class="col-auto">
                            <textarea class="col-form-control" name="description" required></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit" name="action" value="post">valider</button>
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
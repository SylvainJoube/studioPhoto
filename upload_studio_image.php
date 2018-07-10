
<?php

session_start();
include_once("UsefulFunctions.php");
include_once("functions.php");

// Script copié depuis  " https://www.w3schools.com/Php/php_file_upload.asp "
// et ensuite légèrement modifié


//basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;

// $_SESSION["ImgUpload_pagePrecedente"]
// $_SESSION["ImgUpload_codeErreur"]
// $_SESSION["ImgUpload_messageErreur"]
// $_SESSION["ImgUpload_tailleMaximaleOctets"]

$_SESSION["ImgUpload_messageErreur"]="";
$_SESSION["ImgUpload_codeErreurUpload"]=0;
$_SESSION["ImgUpload_codeErreurPerso"]=0;

// Check if image file is an actual image or fake image
if(isset($_POST["submit"])) {
	
	$fError=$_FILES["fileToUpload"]["error"];
	$errorMessage="";
	
	$_SESSION["ImgUpload_codeErreurUpload"]=$fError;
	
	// UPLOAD_ERR_OK tout va bien
	if ($fError==UPLOAD_ERR_OK) {
		$fTempName=$_FILES["fileToUpload"]["tmp_name"];
		$fSize=$_FILES["fileToUpload"]["size"];
		
		if ( ($fTempName!="") and ($fSize>0) ) {	
			$check = getimagesize($fTempName);
			if($check !== false) {
				// OK rien à afficher $errorMessage = "Le fichier est une image. (".$check["mime"].")";
				$uploadOk = 1;
			} else {
				$errorMessage = "Le fichier n'est pas une image.";
				$uploadOk = 0;
				$_SESSION["ImgUpload_codeErreurPerso"]=5;
			}
		} else {
			$uploadOk = 0; // nom invalide (vide)
		}
	} else switch ($fError) {
		case UPLOAD_ERR_INI_SIZE:
			$errorMessage = "La taille du fichier excède upload_max_filesize configuré dans le fichier php.ini.";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$errorMessage = "La taille du fichier excède MAX_FILE_SIZE spécifié dans le formulaire HTML.";
			break;
		case UPLOAD_ERR_PARTIAL:
			$errorMessage = "Upload partiel, un bout de fichier est manquant sur le serveur.";
			break;
		case UPLOAD_ERR_NO_FILE:
			$errorMessage = "Aucun fichier n'a été téléchargé.";
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$errorMessage = "Répertoire temporaire inaccesible.";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$errorMessage = "Impossible d'écrire sur le disque du serveur.";
			break;
		case UPLOAD_ERR_EXTENSION:
			$errorMessage = "Une extension php a bloqué l'upload.";
			break;

		default:
			$errorMessage = "Erreur inconnue.";
			break;
	}
	if ($errorMessage!="") {
		echo htmlspecialchars("ERREUR lors de l'upload : $errorMessage")."<br/>";
		$_SESSION["ImgUpload_messageErreur"]=$errorMessage;
	}
} else {
	$uploadOk=0;
}

$imgToUpload_baseName=basename($_FILES["fileToUpload"]["name"]);
$imageFileType=strtolower(pathinfo($imgToUpload_baseName, PATHINFO_EXTENSION));

// Allow certain file formats
if ($uploadOk==1)
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
	$uploadOk = 0;
	$localError="Seuls les formats JPG, JPEG, PNG et GIF sont acceptés.";
	$_SESSION["ImgUpload_codeErreurPerso"]=3;
	$_SESSION["ImgUpload_messageErreur"]+="<br/>".$localError;
	echo $localError;
}

// Check file size
if ($uploadOk==1)
if ($_FILES["fileToUpload"]["size"] > 1048576*4) { // 4 MO max
	$uploadOk = 0;
	$localError=htmlspecialchars("ERREUR : taille limitée à 4mo.  -> Le fichier est trop grand.");
	$_SESSION["ImgUpload_codeErreurPerso"]=2;
	$_SESSION["ImgUpload_messageErreur"]+="<br/>".$localError;
	echo $localError;
}

$imageId_original=-1;
$imageExtension="";
$target_baseFileName=""; // nom de l'image de taille originale
$target_originalFileName="";
$target_filePath="";
$currentStudioId=$_SESSION["ImgUpload_idStudio"];


$target_dir=$GLOBALS["StudioImage_dirPath"];
if ($uploadOk==1) {
	// je demande à la base de données le dernier identifiant des images des studios
	$DBHandler=DBClick_connect();
	// Je vérifie que la table existe (que la BD est correctement configurée)
	if ($DBHandler->TableExists($GLOBALS["TableName_studioImage"])==false) {
		$localError=htmlspecialchars("ERREUR : table ".$GLOBALS["TableName_studioImage"]." inexistance dans la base de données.");
		$_SESSION["ImgUpload_messageErreur"]+="<br/>" . $localError;
		$_SESSION["ImgUpload_codeErreurPerso"]=10;
		echo $localError;
	} else {
		$maxId=$DBHandler->GetMaxValue("id", "cns_studioimage");
		$DBHandler=NULL;
		$newId=$maxId+1;
		$imageId_original=$newId;
		
		$target_baseFileName = "studio".$currentStudioId."_img"."$newId"."_"."$imageFileType";
		$imageExtension = "$imageFileType";
		$target_originalFileName = $target_baseFileName."_original."."$imageExtension";
		
		$target_filePath = $target_dir.$target_originalFileName;
	}
	$DBHandler=NULL;
}

// Check if file already exists
if ($uploadOk==1)
if (file_exists($target_filePath)) {
	$uploadOk = 0;
	$localError="Le fichier existe déjà. !!";
	$_SESSION["ImgUpload_codeErreurPerso"]=1;
	$_SESSION["ImgUpload_messageErreur"]+="<br/>".$localError;
	echo $localError;
}

if ( ($imageId_original<0) or ($imageExtension=="") or ($target_baseFileName=="") or ($target_filePath=="") or ($currentStudioId<0) or ($target_originalFileName=="") ) {
	$uploadOk=0;
	$localError="ERREUR dans l'affectation d'une variable.";
	$_SESSION["ImgUpload_codeErreurPerso"]=11;
	$_SESSION["ImgUpload_messageErreur"]+="<br/>".$localError;
	echo $localError;
}




// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    //echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_filePath)) {
        
		echo htmlspecialchars("Le fichier " .basename($_FILES["fileToUpload"]["name"]). " a été correctement uploadé.");
		
		// Ajout du fichier dans la base de données, table images de studio
		$DBHandler=DBClick_connect();
		// Je vérifie que la table existe (que la BD est correctement configurée)
		if ($DBHandler->TableExists($GLOBALS["TableName_studioImage"])==false) {
			$localError=htmlspecialchars("ERREUR : table ".$GLOBALS["TableName_studioImage"]." inexistante dans la base de données.");
			$_SESSION["ImgUpload_messageErreur"]+="<br/>".$localError;
			$_SESSION["ImgUpload_codeErreurPerso"]=10;
			echo $localError;
		} else {
			
			// Initialisation des ID des images, pour pouvoir ajouter image_id_taille_1 même à l'image de taille originale
			// Problème : si le serveur a beaucoup de requête et que le php est exécuté sur des fils séparés, il peut y avoir deux requêtes "get max id" simultanées,
			///           et donc deux images ayant le même ID, ce qui poserait problème.
			/// (non encore résolu)
			// (le multithread du php et les accès à la base de données entraînent d'autres problèmes également)
			
			// -> 1) Ajout à la base de données (ensuite selement, redimensionnement)
			//$image_indexApparition=0; // changé plus tard : regarder la liste des images du studio et mettre celle là à la fin
			
			// Je recherche le prochain index d'apparition possible pour les images
			// 
			
			$image_indexApparition=8;
			$query = "SELECT MAX(index_apparition) FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=?";
			// SI le studio n'existe pas encore dans la base de données images, le résultat sera une chaîne de caractères vide. (je mettrai alors index_apparition à 0)
			$qResult=false;
			$dbLink=$DBHandler->dbLink;
			if ($dbLink) {
				$stmt=$dbLink->prepare($query);
				//$tailleImage=$imageSize; // 0 : taille orgiginale (cf doc dans functions.php)
				$stmt->bind_param('i', $currentStudioId);
				$stmt->execute();
				$qResult=$stmt->get_result(); //$DBHandler->MakeQuery($query);
				$stmt->close();
			}
			
			if ($qResult) {
				$A1MaxValue = $qResult->fetch_array(MYSQLI_BOTH); // La notation A1 signifie (pour moi) que c'est un tableau à 1 dimension
				
				if ( ($A1MaxValue!=NULL) and ($A1MaxValue!=false) ) {
					$tabLen=count($A1MaxValue);
					
					foreach ($A1MaxValue as $key => $value) {
						echo "Element de A1MaxValue : key=$key  value=$value -- currentStudioId=$currentStudioId <br/>";
					}
					
					if ($tabLen>0) {
						$image_indexApparition=$A1MaxValue[0];
						if ( ($image_indexApparition==="") or ($image_indexApparition===NULL) or ($image_indexApparition===false) )
							$image_indexApparition=0;
						else
							$image_indexApparition++;
						echo "image_indexApparition=$image_indexApparition <br/>";
					}
				}
			}
			
			
			
			
			
			
			
			
			
			
			
			$image_id_taille_1=0; // initialisation
			
			for ($imageSize=0; $imageSize<=2; $imageSize++) {
				$imageId=1+$DBHandler->GetMaxValue("id", $GLOBALS["TableName_studioImage"]);
				if ($imageSize==1)
					$image_id_taille_1=$imageId;
				//$tailleImage=$imageSize; // 0 : taille originale (cf doc dans functions.php)
				if ($imageSize==0)
					$currentFileName=$target_originalFileName;
				else {
					$currentFileName=$target_baseFileName."_size"."$imageSize"."."."$imageExtension";
				}
				//$t_A1ImageId[$imageSize]=$imageId;
				//$t_A1ImageFileName[$imageSize]=$currentFileName;
				
				$query="INSERT INTO `".$GLOBALS["TableName_studioImage"]."` (`id`, `studio_id`, `nom_de_base`, `taille_affichage`, `index_apparition`, `extension`, `nom_complet`, `image_id_taille_1`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
				
				$qResult=false;
				$dbLink=$DBHandler->dbLink;
				if ($dbLink) {
					$stmt=$dbLink->prepare($query);
					//$tailleImage=$imageSize; // 0 : taille orgiginale (cf doc dans functions.php)
					$stmt->bind_param('iisiissi', $imageId, $currentStudioId, $target_baseFileName, $imageSize, $image_indexApparition, $imageExtension, $currentFileName, $image_id_taille_1);
					$qResult=$stmt->execute();
					$stmt->get_result(); //$DBHandler->MakeQuery($query);
					$stmt->close();
				}
				if ($qResult) {
					echo htmlspecialchars("-------------!! -> Image ajoutée dans la base de données !!")."<br/>";
				} else
					echo htmlspecialchars("ERREUR ERREUR ERREUR -> Image non ajoutée à la base de données.")."<br/>";
				
				
				// 2) Redimensionnement
				
				if ($imageSize!=0) {
					$destinationFilePath=$target_dir.$currentFileName;//$target_baseFileName."_size"."$imageSize"."."."$imageExtension";
					$wantedWidth=$GLOBALS["StudioImage_sizeWidth"][$imageSize];
					$wantedHeight=$GLOBALS["StudioImage_sizeHeight"][$imageSize];
					$success=ResizeImage($target_filePath, $destinationFilePath, $imageExtension, $wantedWidth, $wantedHeight);
					if ($success)
						echo "RESIZE OK ! taille=$imageSize";
					else
						echo "ERREUR ERREUR ERREUR - RESIZE, taille=$imageSize";
				}
			}
			
			
		}
		
		
		$DBHandler=NULL;
		
		
    } else {
		$localError="Echec lors du déplacement du fichier à son emplacement définitif.";
		$_SESSION["ImgUpload_codeErreurPerso"]=4;
		$_SESSION["ImgUpload_messageErreur"]+="<br/>".$localError;
		echo $localError;
    }
}

echo '<meta http-equiv="refresh" content="1; url=\''.$_SESSION["ImgUpload_pagePrecedente"].'\'"/>';

?>



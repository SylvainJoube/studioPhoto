
<?php
	session_start();
	include_once("UsefulFunctions.php");
	include_once("functions.php");
	
	$GLOBALS["PageName"]="gestion_compte.php"; //index.php
	$GLOBALS["PageGestionComptePath"]="gestion_compte.php"; //index.php
		
?>





<html>
	<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="style.css" />
	<title>Document sans nom</title>
</head>

<body>

<div class="container">
  <div class="header"><a href="#"><img src="" alt="Insérer le logo ici" name="Insert_logo" width="20%" height="90" id="Insert_logo" style="background-color: #8090AB; display:block;" /></a> 
    <!-- end .header --></div>
  <div class="sidebar1">
    <ul class="nav">
    	<?php
			//include("menu.php");
		?>
    	<li>
            <span class="sidebarTitle">Gestion de compte</span><br/>
      
   	  	</li>
		<?php
            // Affichage du menu de gauche
            include("menu_gauche_connecte_retour_accueil.php");
			//include("menu_gauche_connexion_inscription.php"); // déclare $GLOBALS["menuGauche_userConnected"]
        ?>
    </ul>
    <!-- end .sidebar1 --></div>
  
  
  <!-- CONTENU -->
  <div class="content">
  
  
    <h2><a href="ajout_studio.php">-&gt; Ajouter un studio</a></h2>
    <!-- <h1>Modifier ce studio</h1> -->
    <p>
    
   	<?php
		$fromAfficher_studioId=filter_input(INPUT_GET, "fromAfStdId");
		//$_SESSION["IdStudioSelectionne"]=-1;
		if ($fromAfficher_studioId) {
			$_SESSION["IdStudioSelectionne"]=$fromAfficher_studioId;
			unset($_GET["fromAfStdId"]); // ne marche certainement pas
		}
		
		// fait plys bas $afficher_imageSelectionneeId=filter_input(INPUT_GET, "SelectImageId");
		$supprimerImageStudio_imageId=filter_input(INPUT_GET, "DeleteImageId");
		
		
		
		//$userLogged=false;
		$userLogged=ReconnectUser(true);
		/*$userLogged=$GLOBALS["menuGauche_userConnected"]; // inutile de faire ReconnectUser(true); c'est déjà fait via include("menu_gauche_connexion_inscription.php");
		if ($userLogged==false)	
			DBClick_goToHomepage();*/
		
		
		/// -------- Affichage et modification du studio photo sélectionné (s'il y en a un) --------
		
		$validStudio=false;
		$A1StudioValues=false;
		$DBHandler=NULL;
		
		/*if (isset($_SESSION["UserEmail"]))
		if (isset($_SESSION["UserPass"]))
		if ($_SESSION["UserEmail"]!="")
		if ($_SESSION["UserPass"]!="")*/
		if ($userLogged) {
			// Requête : vérification que l'utilisateur est bien connecté (et que ses identifiants sont valides)
			$userIsValid=false;
			$userId=-1;
			$DBHandler=DBClick_checkUser_andReturnDBHandler($_SESSION["UserEmail"], $_SESSION["UserPass"], $userIsValid, $userId, true);
			
			if ($userIsValid==false) {
				$DBHandler=NULL;
				exit();
			}
			
			//$userLogged=true;
			
			// Actualisation du studio sélectionné
			DBClick_getStudiosOfUser($DBHandler, $A1StudioId, $userId, false);
			
			// --- Recherche du studio sélectionné, s'il y en a un ---
			if (isset($_SESSION["IdStudioSelectionne"]))
			if ($_SESSION["IdStudioSelectionne"]!=-1) {
				
				$temp_A1StudioValues=DBClick_getA1Studio($userId, $_SESSION["IdStudioSelectionne"], $DBHandler);
				if ($temp_A1StudioValues==false) {
					$_SESSION["IdStudioSelectionne"]=-1;
				} else {
					$validStudio=true;
					$A1StudioValues=$temp_A1StudioValues;
				}
			}
			
		}
		
		function fLocal_writeLog($text) {
			echo $text."<br/>";
		}
		
		if ($userIsValid)
		if ( ($supprimerImageStudio_imageId) and (isset($_SESSION["IdStudioSelectionne"])) )
		if ($_SESSION["IdStudioSelectionne"]!=-1) {
			$studioId=$_SESSION["IdStudioSelectionne"];
			// Suppression de toules les images ayant cette priorité
			// 1) je récupère les variables de cette image
			if ($DBHandler==NULL)
				$DBHandler=DBClick_connect();
			
			$qResult=false;
			if ($DBHandler->TableExists_andEcho($GLOBALS["TableName_studioImage"])) {
				$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND id=?"; //  AND taille_affichage=1  image de taille originale
				//$queryVal="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE id= ? ";
				$dbLink=$DBHandler->dbLink;
				if ($dbLink) {
					$stmt=$dbLink->prepare($query);
					$stmt->bind_param('ii', $studioId, $supprimerImageStudio_imageId);
					$stmt->execute();
					$qResult=$stmt->get_result();//$DBHandler->MakeQuery($query);
					$stmt->close();
					fLocal_writeLog("Requete ok... supprimerImageStudio_imageId=$supprimerImageStudio_imageId  studioId=$studioId");
				}
				
				if ($qResult!=false) {
					fLocal_writeLog("qResult!=false; num_rows=$qResult->num_rows");
					if ($qResult->num_rows==1) {
						$A1ImageVars=$qResult->fetch_array(MYSQLI_BOTH);
						$indexApparition=$A1ImageVars["index_apparition"];
						fLocal_writeLog("------ indexApparition=$indexApparition");
						
						
						if ( ($indexApparition!=="") and ($indexApparition!==NULL) and ($indexApparition!==false) ) {
							// Je recherche toutes les images ayant cet index_apparition, je les supprime du disque et de la base de données (en 1er de la DB)
							
							$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND index_apparition=?"; //  AND taille_affichage=1  image de taille originale
							//$queryVal="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE id= ? ";
							$dbLink=$DBHandler->dbLink;
							if ($dbLink) {
								$stmt=$dbLink->prepare($query);
								$stmt->bind_param('ii', $studioId, $indexApparition);
								$stmt->execute();
								$qResult=$stmt->get_result();//$DBHandler->MakeQuery($query);
								$stmt->close();
								fLocal_writeLog("Requete ok... supprimerImageStudio_imageId=$supprimerImageStudio_imageId  studioId=$studioId");
							}
							if ($qResult!=false) {
								// suppression des images de la DB, inutile de MAJ l'index apparition (inutile de décrémenter les index supérieurs)
								// je récupère les noms de fichier
								$A1DeleteFilePath_len=0;
								while ($A1ImageVars=$qResult->fetch_array(MYSQLI_BOTH)) {
									$nomCompletImg=$A1ImageVars["nom_complet"];
									$fullImgPath=$GLOBALS["StudioImage_dirPath"].$nomCompletImg;
									$A1DeleteFilePath[$A1DeleteFilePath_len]=$fullImgPath;
									$A1DeleteFilePath_len++;
								}
								fLocal_writeLog("A1DeleteFilePath_len=$A1DeleteFilePath_len");
								
								// Suppression de entrées de la base de donnée
								$qResult=false;
								$query="DELETE FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND index_apparition=?";
								$dbLink=$DBHandler->dbLink;
								if ($dbLink) {
									$stmt=$dbLink->prepare($query);
									$stmt->bind_param('ii', $studioId, $indexApparition);
									$qResult=$stmt->execute();
									$stmt->get_result();//$DBHandler->MakeQuery($query);
									$stmt->close();
									fLocal_writeLog("Requete ok...<br/>");
								}
								if ($qResult) {
									fLocal_writeLog("SUppression des images de la DB OK");
								} else 
									fLocal_writeLog("ERREUR ERREUR ERREUR : echec de la suppression des images de la DB ---");
								
								
								for ($iDelete=0; $iDelete<$A1DeleteFilePath_len; $iDelete++) {
									$fullImagePath=$A1DeleteFilePath[$iDelete];
									if (file_exists($fullImagePath)) {
										fLocal_writeLog("Le fichier existe : fullImagePath = $fullImagePath");
										$deleteSuccess=unlink($fullImagePath);
										if ($deleteSuccess)
											fLocal_writeLog("Succès de la suppression du fichier fullImgPath=$fullImgPath !");
										else
											fLocal_writeLog("ERREUR ECHEC CASSE BOUDIN PROUT : echec de la suppression du fichier fullImgPath=$fullImgPath");
									} else {
										fLocal_writeLog("NON SUPPRESSION, n'existe pas sur le disque : fullImgPath=$fullImgPath !");
									}
								}
							}
						}
					}
				}
			}
			
		}
		
		
		// Mise à jour des informations du studio
		if ($validStudio) {
			//echo "Studio valide !";
			
			$studioWasUpdated=false; // actualiser les variables du studio si mise à jour des informations via le formulaire
			
			// prise en compte de la modification des changements
			if (filter_input(INPUT_POST, "FES_valider")) {
				if ($_POST["FES_nomStudio"]!="")
				if ($_POST["FES_prixDemiJournee"]!="") {
					if ($DBHandler==NULL)
						$DBHandler=DBClick_connect();
					$studioId=$A1StudioValues["id"];
					$nomStudio=$_POST["FES_nomStudio"];
					$prixDemiJournee=$_POST["FES_prixDemiJournee"];
					
					if ($DBHandler->TableExists($GLOBALS["TableName_studio"])==false) {
						echo htmlspecialchars("Table des studio manquante dans la base de données.");
					} else {
						$queryVal="UPDATE `".$GLOBALS["TableName_studio"]."` SET `nom` = ?, `prix_demi_journee` = ? WHERE `cns_studio`.`id` = ? ";
						$dbLink=$DBHandler->dbLink;
						if ($dbLink) {
							$stmt=$dbLink->prepare($queryVal);//$nomStudio, $prixDemiJournee, $studioId
							$stmt->bind_param('sii', $nomStudio, $prixDemiJournee, $studioId);
							$stmt->execute();
							$qSuccess=$stmt->get_result();//$DBHandler->MakeQuery($query);
							$stmt->close();
							$studioWasUpdated=true;
						}
					}
				}
			}
			
			// Si besoin, je récupère les informations du studio, modifiées via le précédent formulaire
			if ($studioWasUpdated) {
				if ($DBHandler==NULL)
					$DBHandler=DBClick_connect();
				$A1StudioValues=DBClick_getA1Studio($userId, $_SESSION["IdStudioSelectionne"], $DBHandler);
			}
			
			
			// Edition des variables du studio
			$_POST["FES_prixDemiJournee"]=$A1StudioValues["prix_demi_journee"];
			$_POST["FES_nomStudio"]=$A1StudioValues["nom"];
			
			echo '
			<h3>Modifier un studio</h3>
			<form name="FEditStudio_connexion" action="" method="POST" class="StudioModificaton">
				';
					form_echoRedTextIfNeeded("Nom du studio", "FES_nomStudio", "FES_valider");
					echo "<br/>";
					form_echoInputText("text", "FES_nomStudio", "required");
					echo "<br/>";
					form_echoRedTextIfNeeded("Prix à la demi journée", "FES_prixDemiJournee", "FES_valider");
					echo "<br/>";
					form_echoInputText("number", "FES_prixDemiJournee", "required");
					echo "<br/>";
				echo '
				<input type="submit" name="FES_valider" class="RS_valider" value="Valider ces changements" />
		  	</form>
			<br/>';
			
			if ( isset($_SESSION["ImgUpload_messageErreur"]) )
			if  ($_SESSION["ImgUpload_messageErreur"]!="" ) {
				echo "Erreur lors de l'upload : ".$_SESSION["ImgUpload_messageErreur"]."<br/>";
				echo "Code erreur : ".$_SESSION["ImgUpload_codeErreurUpload"]." et ".$_SESSION["ImgUpload_codeErreurPerso"]." (perso).";
				
			}
			unset($_SESSION["ImgUpload_messageErreur"]);
			unset($_SESSION["ImgUpload_codeErreurUpload"]);
			unset($_SESSION["ImgUpload_codeErreurPerso"]);
			$_SESSION["ImgUpload_pagePrecedente"]=$GLOBALS["PageName"];
			
			$studioId=$_SESSION["IdStudioSelectionne"];
			
			
			// --- Affichage de toutes les photos (miniatures) du studio ---
			//   avec un bouton pour les voir en grand,
			//   un bouton pour les faire avancer et reculer
			//   un bouton pour les supprimer
			// Requête pour connaître toutes les images du studio
			// -Connexion à la base de données et envoi de la requête
			
			echo "</p><h3>Images du studio</h3><p>";
			
			
			AfficherImagesStudio($studioId, true); // déclarée dans functions.php
			
			
			
			
			
			
			$_SESSION["ImgUpload_idStudio"]=$studioId;
			
			echo "<br/><br/><br/></p><h3>Ajouter une image au studio</h3><p>";
			
			echo '
			<form action="upload_studio_image.php" method="post" enctype="multipart/form-data" class="StudioModificaton">
				<label for="fileToUpload">Ajouter une image au studio -> </label>
				<input type="file" name="fileToUpload" id="fileToUpload"> <br/>
				<input type="submit" value="Envoyer !" name="submit" class="UploadPhotoButton">
			</form>';

			$DBHandler==NULL; // dans tous les cas.
			
		} else {
			//echo "Studio invalide.";
		}
		
    	echo "</p>
		<p>&nbsp;</p>
		<h3>Studios possédés :</h3>
		<p>";
		
		
		/// -------- Affichage des studios photo possédés par l'utilisateur --------
		$userLogged=ReconnectUser(true);
		/*$userLogged=$GLOBALS["menuGauche_userConnected"]; // inutile de faire ReconnectUser(true); c'est déjà fait via include("menu_gauche_connexion_inscription.php");
		if ($userLogged==false)	
			DBClick_goToHomepage();*/
		$validStudio=false;
		$A1StudioValues=false;
		$DBHandler=NULL;
		
		/*if (isset($_SESSION["UserEmail"]))
		if (isset($_SESSION["UserPass"]))
		if ($_SESSION["UserEmail"]!="")
		if ($_SESSION["UserPass"]!="")*/
		if ($userLogged) {
			// Requête : vérification que l'utilisateur est bien connecté (et que ses identifiants sont valides)
			$userIsValid=false;
			$userId=-1;
			$DBHandler=DBClick_checkUser_andReturnDBHandler($_SESSION["UserEmail"], $_SESSION["UserPass"], $userIsValid, $userId, true);
			
			if ($userIsValid==false) {
				exit(); // déconnexion et redirection faites par la fonction DBClick_checkUser_andReturnDBHandler();
			} else {
				// Affichage de la liste des studios et bouton pour les modifier
				DBClick_getStudiosOfUser($DBHandler, $A1StudioId, $userId, true);
			}
		}
		
	?>
    </p>
    
    <!-- end .content -->
    </div>

  
	<?php
		include ("footer_common.php");
	?>
  <!-- end .container --></div>
</body>
</html>

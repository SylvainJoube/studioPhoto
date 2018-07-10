<?php
	
	// Page affichant un studio photo dont l'ID est passé via la méthode GET (variable "id")
	// Bientôt : bien plus de champs et possibilité d'uploader des images (plusieurs par studio)
	
	/*
	En cours de construction
	*/
	
	session_start();
	include_once("UsefulFunctions.php");
	include_once("functions.php");
	
	$GLOBALS["PageAccueil"]="index.php";
	$GLOBALS["PageName"]="afficher_studio.php"; //index.php
	$GLOBALS["PageGestionComptePath"]="gestion_compte.php"; //index.php
	

	
?>


<html>
	<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="style.css" />
	<title>Afficher un studio</title>
</head>

<body>

<div class="container">
  <div class="header"><a href="#"><img src="" alt="Insérer le logo ici" name="Insert_logo" width="20%" height="90" id="Insert_logo" style="background-color: #8090AB; display:block;" /></a> 
    <!-- end .header --></div>
  <div class="sidebar1">
    <ul class="nav">
    	<?php
			// Menus de recherche et de déconnexion/gestion de compte/retour accueil
        	include("menu_gauche_recherche.php");
			$GLOBALS["HasToBeLogged"]=false; // pour l'include suivant "menu_gauche_connecte_retour_accueil.php"
			// Pas besoin d'être connecté pour afficher les studios photo
            include("menu_gauche_connecte_retour_accueil.php");
            //include("menu_gauche_connexion_inscription.php");
			
        ?>
        
    </ul>
    <!-- end .sidebar1 --></div>
	<!-- CONTENU -->
  <div class="content">
  
  
    <h2Blablabla !</h2>
    <!-- <h1>Modifier ce studio</h1> -->
    <p>
    
	<?php
		
		// FAUX : inutile de faire ReconnectUser(false); c'est déjà fait via include("menu_gauche_connexion_inscription.php");
		ReconnectUser(false);
		
		$studioId=form_getGetInput("id");
		$imageIndex=form_getGetInput("image_index");
		
		if ($studioId==false) {
			echo htmlspecialchars("Studio demandé d'ID invalide. Redirection en page d'accueil.")."<br/>";
    		echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageAccueil"].'\'"/>';
			exit();
		}
		if ($imageIndex==false)
			$imageIndex=0;
		
    	$DBHandler=DBClick_connect();
		
		$qResult=false;
		if ($DBHandler->TableExists($GLOBALS["TableName_studio"])==false) {
			// Je ne fais pas de requête si la table des studios n'existe pas
			echo htmlspecialchars("ERREUR : Table des studios inexistante dans la base de données. ".$GLOBALS["TableName_studio"]." manquante");
		} else {
			// Table des studios existe dans la base de données, je peux faire la requête
			$queryVal="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE id= ? ";
			$dbLink=$DBHandler->dbLink;
			if ($dbLink) {
				$stmt=$dbLink->prepare($queryVal);
				$stmt->bind_param('i', $studioId);
				$stmt->execute();
				$qResult=$stmt->get_result();//$DBHandler->MakeQuery($query);
				$stmt->close();
			}
		}
		$DBHandler=NULL;
		
		if ( ($qResult==false) or ($qResult==NULL) ) {
			echo htmlspecialchars("Studio demandé d'ID invalide : introuvable dans la base de données. Redirection en page d'accueil.")."<br/>";
    		echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageAccueil"].'\'"/>';
			exit();
		}
		
		$nbRows=$qResult->num_rows;
		if ($nbRows>1) {
			echo htmlspecialchars("ERREUR : ($nbRows > 1) studios trouvés pour cet ID (=$studioId). Merci de signaler cette erreur au dévelppeur du site.")."<br/>";
    		echo "<a href='".$GLOBALS["PageAccueil"]."'>Revenir en page d'accueil</a>";
			//echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageAccueil"].'\'"/>';
			exit();
		}
		
		if ( $nbRows<=0 ) {
			echo htmlspecialchars("Studio demandé d'ID invalide : introuvable dans la base de données. Redirection en page d'accueil. (nbRows = $nbRows)")."<br/>";
    		echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageAccueil"].'\'"/>';
			exit();
		}
		
		$A1StudioValues=$qResult->fetch_array(MYSQLI_BOTH);
		
        $nomStudio=$A1StudioValues["nom"];
        $prixDemiJournee=$A1StudioValues["prix_demi_journee"];
        $ownerId=$A1StudioValues["owner_id"];
		
		if (isset($_SESSION["UserId"])) //{ echo "UserId is set.<br/>";
		if ($_SESSION["UserId"]>0) //{ echo "UserId>0 : ".$_SESSION["UserId"].", ownerId=$ownerId<br/>";
		if ($_SESSION["UserId"]==$ownerId) {
			echo htmlspecialchars("Ce studio vous appartient : ")."<a href='".$GLOBALS["PageCompte"]."?fromAfStdId=$studioId'>-> le modifier <-</a><br/>";
			// Passage via la méthode GET
			//$_SESSION["IdStudioSelectionne"]=$studioId;
		}
		
		
		echo htmlspecialchars("Studio : $nomStudio - prix 1/2 journée : $prixDemiJournee €")."<br/>";
		echo "<button onClick='jumpToHome()'>Retour</button> <br/>"; //Page(".$GLOBALS["PageAccueil"].")
		// -> /!\ perd les résultats de la précédente recherche (quand elle sera implémentée)
		
		
		echo "</p><h3>Images du studio</h3><p>";
		AfficherImagesStudio($studioId, false); // déclarée dans functions.php
		
		
		/*
		
		// affichage des images du studio
		
		// COnnexion à la base de données et envoi de la requête
		$DBHandler=DBClick_connect();
		$qResult=false;
		if ($DBHandler->TableExists($GLOBALS["TableName_studio"])==false) {
			// Je ne fais pas de requête si la table des studios n'existe pas
			echo htmlspecialchars("ERREUR : Table des studios inexistante dans la base de données. ".$GLOBALS["TableName_studio"]." manquante");
		} else {
			// Table des studios existe dans la base de données, je peux faire la requête
			$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? ORDER BY index_apparition"; // AND taille_affichage=0  image de taille originale
			//$queryVal="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE id= ? ";
			$dbLink=$DBHandler->dbLink;
			if ($dbLink) {
				$stmt=$dbLink->prepare($query);
				$stmt->bind_param('i', $studioId);
				$stmt->execute();
				$qResult=$stmt->get_result();//$DBHandler->MakeQuery($query);
				$stmt->close();
			}
		}
		$DBHandler=NULL;
		
		if ($qResult) {
			if ($qResult->num_rows==0) {
				echo "Aucune image à afficher.<br/>";
			} else {
				$imageDirectory=$GLOBALS["StudioImage_dirPath"];
				
				
				class TStudioImage {
					// (`id`, `studio_id`, `nom_de_base`, `taille_affichage`, `index_apparition`, `extension`, nom_complet)
					public $id, $taille_affichage, $inbdex_apparition, $extension, $nom_complet;
					public $A1Variables;
					
					public function InitializeFromMysqliResult(&$queryResult) {
						
						$this->A1Variables=$queryResult->fetch_array(MYSQLI_BOTH);
						
						if ($this->A1Variables) {
							echo "OK InitializeFromMysqliResult <br/>";
							//$this->A1Variables=$A1StudioImage;
							//$id=$A1Studio
							return true;
						} else {
							$this->A1Variables=NULL;
							return false;
						}
						
					}
					
				}
				// Afficher l'image principale vue en premier (taille 1)
				//  et les autres images ($index_image!=$imageIndex) en liste et en plus petit
				
				
				$currentStudioImageNumber=0;
				$minImagePriority=-1;
				$mainImage=NULL;
				$neededImageSize=1;
				//$A1ObjStudioImage=array;
				do {
					$studioImage=new TStudioImage;
					
					/ *$fetchArray=$qResult->fetch_array(MYSQLI_BOTH);
					$continueLoop=(* /
					
					$continueLoop=$studioImage->InitializeFromMysqliResult($qResult);
					
					if ($continueLoop==false) {
						$studioImage=NULL;
						//break;
					} else {
						$A1ObjStudioImage[$currentStudioImageNumber]=$studioImage;
						$currentStudioImageNumber++;
						echo "TailleAffichage : ".$studioImage->A1Variables["taille_affichage"]."; <br/>";
						echo "id : ".$studioImage->A1Variables["id"]."; <br/>";
						if ($studioImage->A1Variables["taille_affichage"]==$neededImageSize) {
							// Je recherche la première image à afficher
							if ($minImagePriority==-1) {
								$minImagePriority=$studioImage->A1Variables["index_apparition"];
								$mainImage=$studioImage;
							} else {
								$indexApparition=$studioImage->A1Variables["index_apparition"];
								if ($indexApparition<$minImagePriority) {
									$minImagePriority=$indexApparition;
									$mainImage=$studioImage;
								}
							}
						}
					}
				} while ($continueLoop);
				
				if ($mainImage!=NULL) {
					$fullImagePath=$GLOBALS["StudioImage_dirPath"].$mainImage->A1Variables["nom_complet"];
					echo htmlspecialchars("Chemin complet de l'image : ".$fullImagePath)."<br/>";
					echo "<img src='$fullImagePath' alt='studio_image'/><br/>";
				}
				
			}
		}
		*/
		
		
        
    ?>
    
    
    
	<script>
		<!--  NE PAS UTILISER goBack() : souci avec l'expiration des formulaires de recherche quand ça fait "précédent" -->
        function goBack() {
			window.history.back();
		}
		function jumpToPage(pageURL) {
			window.location=pageURL;
		}
		function jumpToHome() {
			window.location="index.php";
		}
    </script>
    
    </p>
    
    <!-- end .content -->
    </div>

  
	<?php
		include ("footer_common.php");
	?>
  <!-- end .container --></div>
</body>
</html>
<?php

// Fonctions globales, utiles à toutes les pages du site

include_once("UsefulFunctions.php");

$GLOBALS["PageAccueil"]="index.php";
$GLOBALS["PageCompte"]="gestion_compte.php";

// Constantes de la base de données : nom des tables.

// cns_studio : id (int); owner_id (int); nom (text); prix_demi_journee (int)
$GLOBALS["TableName_studio"]="cns_studio";
// cns_studioimage : id (int); studio_id (int); nom_de_base (text); taille_affichage (int); index_apparition (int); extension (text); nom_complet (int);
//   avec taille_affichage : 0 original, 1 moyen, 2 miniature
$GLOBALS["TableName_studioImage"]="cns_studioimage";
// cns_utilisateur : id (int); email (text); pass (text); admin_rank (int)
$GLOBALS["TableName_utilisateurs"]="cns_utilisateur";

$GLOBALS["StudioImage_dirPath"]="studio_images/";
$GLOBALS["StudioImage_sizeWidth"][1]=550;
$GLOBALS["StudioImage_sizeHeight"][1]=550;
$GLOBALS["StudioImage_sizeWidth"][2]=200;
$GLOBALS["StudioImage_sizeHeight"][2]=200;

//$GLOBALS["TableName_"]


// Informations de connexion à la base de données.

$GLOBALS["DataBase_host"]="sjoubenehydb.mysql.db";//"localhost";//
$GLOBALS["DataBase_user"]="sjoubenehydb";
$GLOBALS["DataBase_name"]="sjoubenehydb";
$GLOBALS["DataBase_pass"]="Bilbilus42";//"-masqué-";

// Ancienne config locale
/*$GLOBALS["DataBase_host"]="localhost";
$GLOBALS["DataBase_user"]="root";
$GLOBALS["DataBase_pass"]="";
$GLOBALS["DataBase_name"]="dbclick";*/



// Fonction pour déconnecter un utilisateur (réinitialiser certaines variables de session)
// et rediriger vers la page d'accueil
function DisconnectUser() {
    global $GS_deconnexionEnCours; // variable existant hors de cette fonction
    global $GLOBALS;
    
    $_SESSION["UserEmail"]="";
    $_SESSION["UserPass"]="";
	$_SESSION["IdStudioSelectionne"]=-1;
    echo "Déconnexion...<br/>";
    echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageAccueil"].'\'"/>';
    if (isset($GS_deconnexionEnCours))
		$GS_deconnexionEnCours=true;
}

// Fonction pour se connecter à la base de données,
// Retourne un objet regroupant des fonctions utiles pour la gestion de la base (voir "UsefulFunctions.php" pour plus d'infos)
function DBClick_connect() {
	$DBHandler = new TDBHandler();
	$DBHandler->ConnectTo($GLOBALS["DataBase_host"], $GLOBALS["DataBase_user"], $GLOBALS["DataBase_pass"], $GLOBALS["DataBase_name"]);
	return $DBHandler;
}


// Fonction pour vérifier si l'utilisateur est valide,
// Retourne un objet DBHandler (gestion de la DB) et (via les alias) l'id de l'utilisateur et s'il est valide ou non (si les identifiants passés en paramètre correspondent à un utlisateur dans la DB)
function DBClick_checkUser_andReturnDBHandler($userEmail, $userPass, &$arg_UserIsValid, &$arg_UserId, $disconnectUserIfInvalid) {
	$DBHandler = DBClick_connect(); // connexion à la base
	$arg_UserId=-1;
	$arg_UserIsValid=false;
	$query="SELECT * FROM ".$GLOBALS["TableName_utilisateurs"]." WHERE email=? AND pass=?";
	// Recherche de l'utilisateur (protection contre les injections SQL)
	$qResult=false;
	$dbLink=$DBHandler->dbLink;
	if ($dbLink) {
		$stmt=$dbLink->prepare($query);
		$stmt->bind_param('ss', $userEmail, $userPass);
		$stmt->execute();
		$qResult=$stmt->get_result(); //$DBHandler->MakeQuery($query);
		$stmt->close();
	}
	// Si utilisateur valide (présent dans la DB), je mets à jour les variables passées par référence à cette fonction (alias)
	if ($qResult!=false)
	if ($qResult->num_rows==1) { // 0 aucun utilisateur, 2 ou + : ERREUR (une seule paire "email, pass" autorisée !)
		$A1UserVariables=$qResult->fetch_array(MYSQLI_BOTH); // tableau des variables de cet utilisateur
		$arg_UserId=$A1UserVariables["id"]; // id de l'utilisateur d'email $userEmail et de pass $userPass
		$arg_UserIsValid=true;
	}
	// Déconnexion de l'utlisateur et redirection vers l'accueil si demandé
	if ($arg_UserIsValid==false) {
		echo "DBClick_checkUser_andReturnDBHandler : NON CONNECTE<br/>";
		if ($disconnectUserIfInvalid) {
			echo htmlspecialchars("Vous n'êtes pas connecté.");
			echo "<br/>";
			echo htmlspecialchars("Accès à la page d'accueil...");
			echo "<br/>";
			//$DBHandler=NULL;
			DisconnectUser();
		}
	}
	
	return $DBHandler;
}

// Fonction pour regarder si un utilisateur existe, retourner son Id si c'est le cas, et se déconnecter de la base de données
function DBClick_checkUser_andFreeDBHandler($userEmail, $userPass, &$userId, $disconnectUserIfInvalid = false) {
	$userIsValid=false;
	$DBHandler=DBClick_checkUser_andReturnDBHandler($userEmail, $userPass, $userIsValid, $userId, $disconnectUserIfInvalid);
	$DBHandler=NULL; // destruction de l'objet
	return $userIsValid;
}

// Fonction servant à récupérer les variables d'un studio
// retourne $A1StudioVariables : les variables du studio ayant en id $studioId
function DBClick_getA1Studio($userId, $studioId, $arg_DBHandler = false) {
	if ($arg_DBHandler==false) {
		$DBHandler = DBClick_connect();
	} else {
		$DBHandler=$arg_DBHandler;
	}
	
	$arg_UserId=-1;
	$arg_UserIsValid=false;
	$query="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE id=? AND owner_id=?"; // "AND owner_id" vient en vérification
	$qResult=false;
	$dbLink=$DBHandler->dbLink;
	if ($dbLink) { // requête et protection contre les injections SQL
		$stmt=$dbLink->prepare($query);
		$stmt->bind_param('ii', $studioId, $userId);
		$stmt->execute();
		$qResult=$stmt->get_result();
		$stmt->close();
	}
	
	if ($qResult!=false)
	if ($qResult->num_rows==1) { // 0 aucun studio, 2 ou + : ERREUR (id est unique)
		$A1StudioVariables=$qResult->fetch_array(MYSQLI_BOTH); // tableau des variables du studio
		return $A1StudioVariables;
	}
	
	$DBHandler=NULL; // déconnexion de la DB
	
	return false; // echec de connexin à la DB ou aucune correspondance (aucun studio de ces $id et $owner_id)
}

// Réinitialiser les variables d'un studio (pour l'affichage du formulaire d'édition)
function StudioVariables_reset() {
	$_SESSION["IdStudioSelectionne"]=-1;
	$_POST["FES_nomStudio"]="";
	$_POST["FES_prixDemiJournee"]="";
	
}

// Fonction affichant (via "echo") tous les studios photo de l'utilisateur d'Id $userId
function DBClick_getStudiosOfUser(&$DBHandler, &$A1StudioId, $userId, $afficherForm = false) {
	// Je ne fais pas de requête si la table des studios n'existe pas
	if ($DBHandler->TableExists($GLOBALS["TableName_studio"])==false) {
		echo htmlspecialchars("ERREUR : Table des studios inexistante dans la base de données. ".$GLOBALS["TableName_studio"]." manquante");
		return false;
	}
	$query="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE owner_id=?";
	// Requête et protection contre les injections SQL
	$qResult=false;
	$dbLink=$DBHandler->dbLink;
	
	if ($dbLink) {
		$stmt=$dbLink->prepare($query);
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$qResult=$stmt->get_result(); //$DBHandler->MakeQuery($query);
		$stmt->close();
	}
	
	//echo $query; débug uniquement
	//echo "<br/>";
	if ($qResult!=false) {
		$tabLength=$qResult->num_rows;
		$A2Studio=NULL;
		// Je stocke les valeurs pour effectuer une nouvelle requête avec la même variable
		for ($i=0; $i<$tabLength; $i++)
			$A2Studio[$i]=$qResult->fetch_array(MYSQLI_BOTH); // (MYSQLI_ASSOC suffirait)
		
		
		
		
		// $A1StudioId
		//echo "qResult OK - tabLength=$tabLength";
		// Affichage de la liste des studios
		for ($i=0; $i<$tabLength; $i++) {
			// Je récupère et je stocke (dans $A1StudioId) les variables du studio
			$A1Studio=$A2Studio[$i];//$qResult->fetch_array(MYSQLI_BOTH); // (MYSQLI_ASSOC suffirait)
			$A1StudioId[$i]=$A1Studio["id"];
			if ($afficherForm) {
				//echo $A1Studio["nom"]."  ";
				$nomStudio=$A1Studio["nom"];
				if ($A1Studio["id"]==$_SESSION["IdStudioSelectionne"]) {
					$nomStudio='->'.$A1Studio["nom"].htmlspecialchars(' [en cours d\'édition]');
				}
				// Je charge l'image miniature du studio
				
				$imagePath=DBClick_getStudioImgMiniature($DBHandler, $A1Studio["id"]);
				/*
				$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND taille_affichage=2 ORDER BY index_apparition LIMIT 1"; // 1 seule image nécessaire
				$qResult=false;
				$dbLink=$DBHandler->dbLink;
				if ($dbLink) {
					$stmt=$dbLink->prepare($query);
					$stmt->bind_param('i', $A1Studio["id"]);
					$stmt->execute();
					$qResult=$stmt->get_result(); //$DBHandler->MakeQuery($query);
					$stmt->close();
				}
				
				$imagePath="";
				if ($qResult)  {
					//echo "qResult->num_rows = $qResult->num_rows <br/>";
					if ($qResult->num_rows>=1) {
						$A1ImageVars=$qResult->fetch_array(MYSQLI_ASSOC);
						$imageName=$A1ImageVars["nom_complet"];
						$imagePath=$GLOBALS["StudioImage_dirPath"].$imageName;
					}
				}*/
				
				// Ne correspond pas vraiment au modèle MVC (modèle-vue-contrôleur), je reprendrai ce code plus tard
				echo '<form name="Fm_affichageStudios" action="'.$GLOBALS["PageName"].'" method="POST" class="StudioSearch">';
				
				if ($imagePath!="") {
					echo "<img src='$imagePath' alt='image_studio' /><br/>";
					//echo "ImagePath=$imagePath<br/>";
				} else {
					//echo "Aucune image <br/>";
				}
				
				echo '<span class="StudioSearchTitle">'.$nomStudio.'</span><br/>'.
					 '<input type="submit" class="RS_valider" name="fm_affichageStudio_valider'."$i".'" value="Modifier ce studio" />'.
					 '</form><br/>';
			}
			//echo "<br/>Possede : id=".$A1Studio["id"]." ownerId=".$A1Studio["owner_id"]." nom=".$A1Studio["nom"]." prixDeJou=".$A1Studio["prix_demie_journee"];
		}
		
		
		// Je regarde si un studio a été sélectionné via un formulaire
		// Si oui, j'affecte une variable de session avec l'id du studio sélectonné
		for ($i=0; $i<$tabLength; $i++) {
			$studioId=$A1StudioId[$i];
			$postVariableName="fm_affichageStudio_valider"."$i";
			if (isset($_POST[$postVariableName])) {
				$_SESSION["IdStudioSelectionne"]=$studioId;
			}
			unset($_POST[$postVariableName]);
		}
		
	} else 
		echo "ERREUR : DBClick_getStudiosOfUser() : qResult==false.<br/>";
}

function DBClick_getStudioImgMiniature($DBHandler, $studioId) {
	$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND taille_affichage=2 ORDER BY index_apparition LIMIT 1"; // 1 seule image nécessaire
	$qResult=false;
	$dbLink=$DBHandler->dbLink;
	if ($dbLink) {
		$stmt=$dbLink->prepare($query);
		$stmt->bind_param('i', $studioId);
		$stmt->execute();
		$qResult=$stmt->get_result(); //$DBHandler->MakeQuery($query);
		$stmt->close();
	}
	
	$imagePath="";
	if ($qResult)  {
		//echo "qResult->num_rows = $qResult->num_rows <br/>";
		if ($qResult->num_rows>=1) {
			$A1ImageVars=$qResult->fetch_array(MYSQLI_ASSOC);
			$imageName=$A1ImageVars["nom_complet"];
			$imagePath=$GLOBALS["StudioImage_dirPath"].$imageName;
		}
	}
	return $imagePath;
}

function DBClick_goToHomepage() {
	echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageAccueil"].'\'"/>';
}

// Retourne true si l'utilisateur est valide, false si invalide ou échec de la requête
function CheckUserCredentialsAndConnect($userEmail, $userPass, $goToHomepageIfInvalidUser, $goToHomepageIfRequestFails, &$isUserValid, &$querySuccess) {
	
	// réinitialisation de la session de connexion du client
	$_SESSION["UserEmail"]=""; 
	$_SESSION["UserPass"]="";
	$_SESSION["UserId"]=-1;
	$_SESSION["UserAdminRank"]=0;
	
	// Connexion à la base de donnée (fonction déclarée dans functions.php)
	$DBHandler=DBClick_connect();
	$isUserValid=false;
	$querySuccess=false;
	$GlobalResult=false;
	
	if ($DBHandler->TableExists($GLOBALS["TableName_utilisateurs"])==false) {
		echo "<span class='redText'>ERREUR : Table des utilisateurs introuvable.</span><br/>";
		$DBHandler=NULL;
		if ($goToHomepageIfRequestFails)
			DBClick_goToHomepage();
		return false;
	}
	
	$query="SELECT * FROM ".$GLOBALS["TableName_utilisateurs"]." WHERE email=? AND pass=?";
	
	// Je regarde si l'ulisateur est valide (correspondance email et mot de passe
	// Requête avec protection contre les injections SQL
	$qResult=false;
	$dbLink=$DBHandler->dbLink;
	if ($dbLink) {
		$stmt=$dbLink->prepare($query);
		if ($stmt) {
			$stmt->bind_param('ss', $userEmail, $userPass);
			$stmt->execute();
			$qResult=$stmt->get_result(); //$DBHandler->MakeQuery($query); (unsafe)
			$stmt->close();
		}
	}
	if ($qResult==false) { // requête échouée, peut-être échec de connexion à la base de données
		$DBHandler=NULL;
		if ($goToHomepageIfRequestFails)
			DBClick_goToHomepage();
		return false;
	} else {
		$querySuccess=true;
		if (mysqli_num_rows($qResult)==0) { // aucune correspondance
			$DBHandler=NULL;
			if ($goToHomepageIfInvalidUser)
				local_goToHomepage();
			return false;
		} else { // (au moins) une correspondance
			$A1UserValues=$qResult->fetch_array(MYSQLI_BOTH);
			$_SESSION["UserEmail"]=$userEmail;
			$_SESSION["UserPass"]=$userPass;
			$_SESSION["UserId"]=$A1UserValues["id"];
			$_SESSION["UserAdminRank"]=$A1UserValues["admin_rank"];
			$isUserValid=true;
			$DBHandler=NULL;
			return true;
		}
	}
	
}

// Fonction pour revérifier les identifiants de l'utilisateur connecté
function ReconnectUser($retourAccueilSurEchec) {
	$email="";
	$pass="";
	if (isset($_SESSION["UserEmail"])) $email=$_SESSION["UserEmail"];
	if (isset($_SESSION["UserPass"]))  $pass=$_SESSION["UserPass"];
	$userIsValid=false; // initialisation des variables passées par référence
	$querySuccess=false;
	return CheckUserCredentialsAndConnect($email, $pass, $retourAccueilSurEchec, $retourAccueilSurEchec, $userIsValid, $querySuccess);
}


function AfficherImagesStudio($studioId, $depuisGestionDeCompte) {
	
	$afficher_imageSelectionneeId=filter_input(INPUT_GET, "SelectImageId");
	
	$DBHandler=DBClick_connect();
	
	// 1) affichage en grand de l'image selectionnée
	if ($afficher_imageSelectionneeId) {
		$qResult=false;
		//echo "afficher_imageSelectionneeId=$afficher_imageSelectionneeId <br/>";
		if ($DBHandler->TableExists($GLOBALS["TableName_studio"])==false) {
			// Je ne fais pas de requête si la table des studios n'existe pas
			echo htmlspecialchars("ERREUR : Table des studios inexistante dans la base de données. ".$GLOBALS["TableName_studio"]." manquante");
		} else {
			// Table des studios existe dans la base de données, je peux faire la requête
			$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND id=?"; //  AND taille_affichage=1  image de taille originale
			//$queryVal="SELECT * FROM ".$GLOBALS["TableName_studio"]." WHERE id= ? ";
			$dbLink=$DBHandler->dbLink;
			if ($dbLink) {
				$stmt=$dbLink->prepare($query);
				$stmt->bind_param('ii', $studioId, $afficher_imageSelectionneeId);
				$stmt->execute();
				$qResult=$stmt->get_result();//$DBHandler->MakeQuery($query);
				$stmt->close();
			}
		}
		if ($qResult) {
			//echo "qResult!=false !!!  numRows=".$qResult->num_rows."<br/>";
			
			$afficherStudioId_varStr="";
			if (isset($_SESSION["IdStudioSelectionne"]))
			if ($_SESSION["IdStudioSelectionne"]!=-1) {
				if ($GLOBALS["PageName"]=="gestion_compte.php")
					$afficherStudioId_varStr="fromAfStdId=".$_SESSION["IdStudioSelectionne"]."&";
				if ($GLOBALS["PageName"]=="afficher_studio.php")
					$afficherStudioId_varStr="id=".$_SESSION["IdStudioSelectionne"]."&";
			}
			
			while ($A1StudioImageVars=$qResult->fetch_array(MYSQLI_ASSOC)) {
				$imgName=$A1StudioImageVars["nom_complet"];
				$imgPath=$GLOBALS["StudioImage_dirPath"].$imgName;
				$imgId=$A1StudioImageVars["id"];
				
				echo "<img src='$imgPath' alt='image $imgId, studio $studioId' />";
				if ($depuisGestionDeCompte) {
					echo "<br/>
					<a href='".$GLOBALS["PageName"]."?".$afficherStudioId_varStr."DeleteImageId=$imgId'>X Supprimer cette image X</a>";
				}
				echo "<br/><br/><br/>";//.htmlspecialchars(" ");
			}
		}
	}
	
	//2)  Affichage de toutes les miniatures
	
	$qResult=false;
	if ($DBHandler->TableExists($GLOBALS["TableName_studio"])==false) {
		// Je ne fais pas de requête si la table des studios n'existe pas
		echo htmlspecialchars("ERREUR : Table des studios inexistante dans la base de données. ".$GLOBALS["TableName_studio"]." manquante");
	} else {
		// Table des studios existe dans la base de données, je peux faire la requête
		$query="SELECT * FROM ".$GLOBALS["TableName_studioImage"]." WHERE studio_id=? AND taille_affichage=2 ORDER BY index_apparition"; // AND taille_affichage=0  image de taille originale
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
	if ($qResult) {
		$afficherStudioId_varStr="";
		
		
		
		
		if ($depuisGestionDeCompte) {
			if (isset($_SESSION["IdStudioSelectionne"]))
			if ($_SESSION["IdStudioSelectionne"]!=-1) {
				//if ($GLOBALS["PageName"]=="gestion_compte.php")
				$afficherStudioId_varStr="fromAfStdId=".$_SESSION["IdStudioSelectionne"]."&";
			}
		} else { // depuis l'affichage utilisateur (et non gestion de compte)
			//if ($GLOBALS["PageName"]=="afficher_studio.php")
			$afficherStudioId_varStr="id=".$studioId."&";
		}
		
		while ($A1StudioImageVars=$qResult->fetch_array(MYSQLI_ASSOC)) {
			$imgName=$A1StudioImageVars["nom_complet"];
			$imgPath=$GLOBALS["StudioImage_dirPath"].$imgName;
			$imgIdTaille1=$A1StudioImageVars["image_id_taille_1"];
			
			echo "<a href='".$GLOBALS["PageName"]."?".$afficherStudioId_varStr."SelectImageId=$imgIdTaille1'><img src='$imgPath' alt='image $imgIdTaille1, studio $studioId' /></a>".htmlspecialchars(" ");
		}
		
	}
	
	
	$DBHandler=NULL;
}

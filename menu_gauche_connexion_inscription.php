
<?php
	
	//session_start(); déjà déclaré dans le script qui appelle ce script.
	include_once("UsefulFunctions.php");
	include_once("functions.php");
	include_once("gestion_connexion_deconnexion.php");
	
	$GS_deconnexionEnCours=false;
	$GS_connexionEnCours=false;
	
	
	// connexion - déconnexion ici
	// gestion et affectation des variables
	//     $GLOBALS["AfficherTexteDessousInscription"]
	// et  $GLOBALS["AfficherTexteDessousConnexion"]
	
	HandleClientConnection(); // via gestion_connexion_deconnexion.php
	HandleClientRegistration(); // via gestion_connexion_deconnexion.php
	
	
	$userLogged=ReconnectUser(false);
	$GLOBALS["menuGauche_userConnected"]=$userLogged;
	
	/*if (isset($_SESSION["UserEmail"]))
	if ($_SESSION["UserEmail"]!="")
		$userLogged=true; // utilisateur connecté
	*/
	
	$neRienAfficherEnDessous=false;
	if ($GS_deconnexionEnCours) {
		$neRienAfficherEnDessous=true;
	}
	
	// Affichage du bouton "se déconnecter" si l'utilisateur est connecté
	// et affichage de la fenêtre de connexion sinon
	
	if ($userLogged) {
		
		$disconnect=form_getPostInput("FC_deconnecter"); // via formulaire
		if ($disconnect==false) {
			$disconnectStr=form_getGetInput("disconnect"); // via lien (méthode GET du coup)
			if ($disconnectStr!=false)
			if ($disconnectStr=="true")
				$disconnect=true;
		}
		
		if ($disconnect) {
			DisconnectUser();
		}
		
		//FG_gestionCompte
		// Aller à l'interface de gestion de compte
		$goGestionDeCompte=form_getPostInput("FG_gestionCompte");
		if ($goGestionDeCompte) {
			// J'aurais aussi très bien pu faire un simple "<a href=... >Aller à la page de gestion de compte</a>))
			echo "Accès à la page de gestion de compte...<br/>";
			echo '<meta http-equiv="refresh" content="1; url=\''.$GLOBALS["PageCompte"].'\'"/>';
			$GS_deconnexionEnCours=true;
			//exit();
		}
		
		if ($GS_deconnexionEnCours==false)
		if ($GS_connexionEnCours==false) { // affichage uniquement si la page a été actualisée (moche sinon, ça vient après "connexion...")
			
			echo '<span class="messageBienvenue">'; //</li><li> <a href="pok.php">
			echo htmlspecialchars("Connecté en tant que : ")."<br/>->".htmlspecialchars($_SESSION["UserEmail"]).'<br/>';
			echo '</span>';
			// Lien vers la page de gestion de compte (ajout/suppression de studios)
			echo '</li><li><a href="'.$GLOBALS["PageCompte"].'">-> Gérer votre compte</a></li>';
			// Lien vers sa déconnexion
			echo '<li><a href="'.$GLOBALS["PageAccueil"].'?disconnect=true">'.htmlspecialchars("-> Vous déconnecter").'</a></li><li>'; //'.$GLOBALS["PageName"].'
			
			
			//echo "<br/><br/>";
			
			// Quand-même fonctionnel :
			// Bouton (méthode POST) pour se déconnecter, remplacé par un simple lien (méthode GET), plus esthétique.
			/* bouton (pas beau) vers la déconnexion, remplacé par un lien avec la méthode GET
			echo '
				<form name="Fm_connexion" action="" method="POST" class="SearchBox">
					<input type="submit" name="FC_deconnecter" class="RS_valider" value="'.htmlspecialchars("Se déconnecter").'" />
				</form>
				</li>
			';*/
			$neRienAfficherEnDessous=true;
			
		}
	} else {
		// Utilisateur non connecté
		// Affichage du formulaire de connexion
		// J'aurais, pour plus de lisibilité, pu mettre ça dans un fichier php à part.
		
		echo '
		<form name="Fm_connexion" action="" method="POST" class="SearchBox">
			<span class="darkBlueTextColor">
			<span class="sidebarTitle">Se connecter</span><br/>
			';
			
				form_echoRedTextIfNeeded("Adresse mail", "FC_email", "FC_valider");
				echo "<br/>";
				form_echoInputText("email", "FC_email", "required");
				form_echoRedTextIfNeeded("Mot de passe", "FC_pass", "FC_valider");
				echo "<br/>";
				form_echoInputText("password", "FC_pass", "requdired");
			
			echo '
			<input type="submit" name="FC_valider" class="RS_valider" value="Se connecter" />
			
			<!-- équivaut à (sans php dynamique) :
			Nom de compte (mail)
			<input type="text" name="FC_nom" value="" class="form-control">
			Mot de passe
			<input type="password" name="FC_pass" value="" class="form-control">
			<input type="submit" name="FC_valider" class="RS_valider" value="Se connecter" />
			-->
		  </span>
	  </form>';
		if ($GLOBALS["AfficherTexteDessousConnexion"]!="") {
			echo $GLOBALS["AfficherTexteDessousConnexion"];
		}
	}
	
	if ($GS_deconnexionEnCours==false)
	if ($neRienAfficherEnDessous==false) {
		echo '	  
		</li>
		<li>';
		
		$userLogged=false;
		if (isset($_SESSION["UserEmail"]))
		if ($_SESSION["UserEmail"]!="")
			$userLogged=true; // utilisateur connecté
		
		// Affichage de la fenêtre d'inscription si l'utilisateur est déconnecté et pas en cours de connexion
		// (si l'utilisateur a fait une demande de connexion juste au-dessus, je n'affiche pas la fenêtre d'inscription)
		
		if ( $GS_deconnexionEnCours==false ) // ne rien afficher si la déconnexion est en cours
		if ( $userLogged==false ) {
		echo '
			<form name="Fm_inscription" action="" method="POST" class="SearchBox">
				<span class="darkBlueTextColor">
				<span class="sidebarTitle">'.htmlspecialchars('S\'inscrire').'</span><br/>
				';
				
				form_echoRedTextIfNeeded("Adresse mail", "FI_nom", "FI_valider");
				form_echoInputText("email", "FI_nom", "required");
				//echo "<br/>";
				form_echoRedTextIfNeeded("Mot de passe", "FI_pass", "FI_valider");
				form_echoInputText("password", "FI_pass", "required");
				//echo "<br/>";
				form_echoRedTextIfNeeded("Confirmer le mot de passe", "FI_passDouble", "FI_valider");
				form_echoInputText("password", "FI_passDouble", "required");
				// si validé + mot de passe 1 & 2 remplis ET que pot de passe 1 & 2 ne correspondent pas :
				// afficher "les mdp ne correnpondent pas"
				
			echo'
				<input type="submit" name="FI_valider" class="RS_valider" value="'.htmlspecialchars('Créer un compte').'" />
				</span>
			</form>
			';
			if ($GLOBALS["AfficherTexteDessousInscription"]!="") {
				echo $GLOBALS["AfficherTexteDessousInscription"];
			}
		}
	}
	echo "</li>";
	
?>
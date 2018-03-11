<?php
/*******************************************************************************
* Nikya eedomus Script uCam
********************************************************************************
* Plugin version : 1.1
* Author : Nikya
* Origine : https://github.com/Nikya/eedomusScript_ucam
*******************************************************************************/

/** Utile en cours de dev uniquement */
//$eedomusScriptsEmulatorDatasetPath = "eedomusScriptsEmulator_dataset.json";
//require_once ("eedomusScriptsEmulator.php");

/** Initialisation de la réponse */
$result = array();

/*******************************************************************************
* Lecture des variables
*/

/** [Optionnel] Nombre de photo à prendre */
$snapCount = getArg('snapcount', false, 1);

/** [Optionnel] Temps d'attente en seconde entre les photos */
$snapInterval = getArg('snapinterval', false, 1);

/** [VAR1] Url pour obtenir un snap (URL à enconder)*/
$urlSnap = getArg('urlsnap');

/** [VAR2] Informations de FTP : User : Password @ server FTP */
$ftpTarget = getArg('ftptarget');

/*******************************************************************************
* Procéder
*/
sdk_main();
function sdk_main() {
	global $snapCount;
	global $snapInterval;
	global $urlSnap;
	global $ftpTarget;
	$r = null;


	$aFtpTarget = sdk_readFtpTarget($ftpTarget);
	if ($aFtpTarget == null) {
		sdk_finish(array('success'=>false, 'msg' => 'Incorrecte ftpTarget '.$ftpTarget));
	}
	else {
		$i=0;
		for ( ; $i<$snapCount; $i++) {
			$content = httpQuery($urlSnap);
			$res['snapshot'][$i]['snap']['success'] = $content != null;
			$res['snapshot'][$i]['snap']['content'] = substr($content, 0, 50) . '...';
			$r = sdk_ftpUpload($aFtpTarget, $content, $i);
			$res['snapshot'][$i]['ftpupload']['success'] = $r != null;
			$res['snapshot'][$i]['ftpupload']['content'] = $r;
			usleep($snapInterval*1000*1000);
		}

		$res['success'] = true;
		$res['msg'] = "A total of $i snaps processed (Interval={$snapInterval}s)";
		sdk_finish($res);
	}
}

/*******************************************************************************
* Lecture des informations du FTP
*/
function sdk_readFtpTarget($ftpTarget) {
	$patern = '#(.+):(.+)@(.+)#'; // user:mdp@www.site.fr

	$matches = null;
	if (preg_match_all($patern, $ftpTarget, $matches)===false or count($matches[0])==0)
		return null;
	else
		return array(
			'user' => $matches[1][0],
			'pwd' => $matches[2][0],
			'server' => $matches[3][0]
		);
}

/*******************************************************************************
* Appeler le preset
*/
function sdk_preset($urlPreset, $presetId) {
	$patern = '#presetid#';
	$urlPreseted = str_replace ($patern, $presetId, $urlPreset);
	$return = httpQuery($urlPreseted);
}

/*******************************************************************************
* Envoie du contenue par FTP
*/
function sdk_ftpUpload($aFtpTarget, $content, $i) {
	return ftpUpload(
			$aFtpTarget['server'],
			$aFtpTarget['user'],
			$aFtpTarget['pwd'],
			$content,
			'time_fetch_box_'.$i.'.jpg'
		);
}

/*******************************************************************************
* Fin du script affichage du résultat
*/
function sdk_finish($res) {
	echo '<pre>';
	var_dump($res);
	echo '</pre>';
	exit;
}

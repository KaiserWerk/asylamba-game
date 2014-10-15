<?php
include_once HERMES;
# write message action

# int id 			id du destinataire
# int thread 		id du thread
# int name 			nom du destinataire
# string message 	message à envoyer

$id = Utils::getHTTPData('playerid');
$thread = Utils::getHTTPData('thread');
$name = FALSE;
$message = Utils::getHTTPData('message');


// protection des inputs
$p = new Parser();
$message = $p->parse($message);

if (($id OR $thread OR $name) AND $message !== '') {
	if (strlen($message) < 25000) {
		$m = new Message();
		$m->setRPlayerWriter(CTR::$data->get('playerId'));
		$m->setDSending(Utils::now());
		$m->setContent($message);

		$S_MSM1 = ASM::$msm->getCurrentSession();
		ASM::$msm->newSession(ASM_UMODE);

		if ($thread) {
			ASM::$msm->load(array('thread' => $thread), array(), array(0, 1));
			if (ASM::$msm->get()) {
				if ($id) {
					$m->setRPlayerReader($id);
				} else {
					if (ASM::$msm->get()->getRPlayerReader() == CTR::$data->get('playerId')) {
						$m->setRPlayerReader(ASM::$msm->get()->getRPlayerWriter());
					} else {
						$m->setRPlayerReader(ASM::$msm->get()->getRPlayerReader());
					}
				}
				$m->setThread($thread);
				ASM::$msm->add($m);
			} else {
				CTR::$alert->add('Création de message impossible', ALERT_STD_ERROR);
				CTR::$alert->add('Unknow thread', ALERT_BUG_ERROR);
			}
		} else {
			$cancel = FALSE;
			if (!$id AND $name) {
				include_once ZEUS;
				$S_PAM1 = ASM::$pam->getCurrentSession();
				ASM::$pam->newSession(ASM_UMODE);
				ASM::$pam->load(array('name' => $name));
				if (ASM::$pam->get()) {
					$id = ASM::$pam->get()->getId();
					if ($id == CTR::$data->get('playerId')) {
						$cancel = TRUE;
						CTR::$alert->add('Vous ne pouvez pas envoyer un message à vous-même', ALERT_STD_ERROR);
					}
				} else {
					$cancel = TRUE;
					CTR::$alert->add('Création de message impossible, destinataire inconnu', ALERT_STD_ERROR);
				}
				ASM::$pam->changeSession($S_PAM1);
			}
			if (!$cancel) {
				ASM::$msm->load(array('rPlayerReader' => $id, 'rPlayerWriter' => CTR::$data->get('playerId')));
				if (ASM::$msm->get()) {
					$m->setThread(ASM::$msm->get()->getThread());
					$m->setRPlayerReader($id);
					ASM::$msm->add($m);
					CTR::$alert->add('Message envoyé', ALERT_STD_SUCCESS);
				} else {
					ASM::$msm->load(array('rPlayerWriter' => $id, 'rPlayerReader' => CTR::$data->get('playerId')));
					if (ASM::$msm->get()) {
						$m->setThread(ASM::$msm->get()->getThread());
						$m->setRPlayerReader($id);
						ASM::$msm->add($m);
						CTR::$alert->add('Message envoyé', ALERT_STD_SUCCESS);
					} else {
						//création d'n nouveau thread
						$db = DataBase::getInstance();
						$qr = $db->prepare('SELECT MAX(thread) AS maxThread FROM message');
						$qr->execute();
						if ($aw = $qr->fetch()) {
							$m->setThread($aw['maxThread'] + 1);
							$m->setRPlayerReader($id);
							ASM::$msm->add($m);

							CTR::redirect('message/thread-' . $m->getThread());
						} else {
							CTR::$alert->add('Création de message impossible', ALERT_STD_ERROR);
							CTR::$alert->add('MAX(thread) error', ALERT_BUG_ERROR);
						}
					}
				}
			}
		}
		ASM::$msm->changeSession($S_MSM1);
	} else {
		CTR::$alert->add('Le message est trop long pour être envoyé', ALERT_STD_FILLFORM);
	}
} else {
	CTR::$alert->add('Pas assez d\'informations pour écrire un message', ALERT_STD_FILLFORM);
}
?>
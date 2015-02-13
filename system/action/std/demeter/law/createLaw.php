<?php
include_once DEMETER;
include_once ZEUS;
include_once GAIA;

#type
#duration pour les lois à durée en seconde
#taxes taux de taxe
#rColor autre faction concernée
#rSector secteur concernée
#name pour nommer des trucs

$type = Utils::getHTTPData('type');
$duration = Utils::getHTTPData('duration');

if ($type !== FALSE) {
	if (LawResources::size() >= $type) {
		if (CTR::$data->get('playerInfo')->get('status') == LawResources::getInfo($type, 'department')) {
			$_CLM = ASM::$clm->getCurrentsession();
			ASM::$clm->load(array('id' => CTR::$data->get('playerInfo')->get('color')));
			$law = new Law();

			$law->rColor = CTR::$data->get('playerInfo')->get('color');
			$law->type = $type;
			if (LawResources::getInfo($type, 'department') == PAM_CHIEF) {
				$law->statement = Law::EFFECTIVE;

				$law->dCreation = Utils::now();
				$law->dEndVotation = Utils::now();

				if (LawResources::getInfo($type, 'undeterminedDuration')) {
					$date = new DateTime(Utils::now());
					$date->modify('+' . 5 . ' years');
					$law->dEnd = $date->format('Y-m-d H:i:s');
				} else if ($duration) {
					$duration = ($duration > 2400) ? 2400 : $duration;
					$date = new DateTime(Utils::now());
					$date->modify('+' . $duration . ' hours');
					$law->dEnd = $date->format('Y-m-d H:i:s');
				} else {
					$law->dEnd = Utils::now();
				}
			} else {
				$law->statement = Law::VOTATION;

				$date = new DateTime(Utils::now());
				$law->dCreation = $date->format('Y-m-d H:i:s');
				$date->modify('+' . Law::VOTEDURATION . ' second');
				$law->dEndVotation = $date->format('Y-m-d H:i:s');

				if (LawResources::getInfo($type, 'undeterminedDuration')) {
					$date = new DateTime(Utils::now());
					$date->modify('+' . 5 . ' years');
					$law->dEnd = $date->format('Y-m-d H:i:s');
				} else if ($duration) {
					$duration = ($duration > 2400) ? 2400 : $duration;
					$date = new DateTime(Utils::now());
					$date->modify('+' . $duration . ' hours');
					$law->dEnd = $date->format('Y-m-d H:i:s');
				} else {
					$law->dEnd = Utils::now();
				}
			}
			if (LawResources::getInfo($type, 'bonusLaw')) {
				if (ASM::$clm->get()->credits >= LawResources::getInfo($type, 'price') * $duration * ASM::$clm->get()->activePlayers) {
					$law->options = serialize(array());
					$_LAM = ASM::$lam->getCurrentsession();
					ASM::$lam->newSession();
					ASM::$lam->load(array('type' => $type, 'rColor' => CTR::$data->get('playerInfo')->get('color'), 'statement' => array(Law::EFFECTIVE, Law::VOTATION)));

					if (ASM::$lam->size() == 0) {
						ASM::$lam->add($law);
						ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price') * $duration * ASM::$clm->get()->activePlayers;
						CTR::redirect('faction/view-senate');	
					} else {
						CTR::$alert->add('Cette loi est déjà proposée ou en vigueur.', ALERT_STD_ERROR);
					}
				} else {
					CTR::$alert->add('Il n\'y a pas assez de crédits dans les caisses de l\'Etat.', ALERT_STD_ERROR);
				}
			} else {
				if (ASM::$clm->get()->credits >= LawResources::getInfo($type, 'price')) {
					switch ($type) {
						case SECTORTAX:
							$taxes = round(Utils::getHTTPData('taxes'));
							$rSector = Utils::getHTTPData('rsector');
							if ($taxes !== FALSE && $rSector !== FALSE) {
								if ($taxes >= 2 && $taxes <= 15) {
									$_SEM = ASM::$sem->getCurrentsession();
									ASM::$sem->load(array('id' => $rSector)); 
									if (ASM::$sem->size() > 0) {
										if (ASM::$sem->get()->rColor == CTR::$data->get('playerInfo')->get('color')) {
											$law->options = serialize(array('taxes' => $taxes, 'rSector' => $rSector, 'display' => array('Secteur' => ASM::$sem->get()->name, 'Taxe actuelle' => ASM::$sem->get()->tax . ' %', 'Taxe proposée' => $taxes . ' %')));
											ASM::$lam->add($law);
											ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
											CTR::redirect('faction/view-senate');
										} else {
											CTR::$alert->add('Ce secteur n\'est pas sous votre contrôle.', ALERT_STD_ERROR);
										}
									} else {
										CTR::$alert->add('Ce secteur n\'existe pas.', ALERT_STD_ERROR);
									}
									ASM::$sem->changeSession($_SEM);
								} else {
									CTR::$alert->add('La taxe doit être entre 2 et 15 %.', ALERT_STD_ERROR);
								}
							} else {
								CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
							}
							break;
						case SECTORNAME:
							$rSector = Utils::getHTTPData('rsector');
							$name = Utils::getHTTPData('name');
							if ($rSector !== FALSE && $name !== FALSE) {
								$name = Parser::protect($name);
								$_SEM = ASM::$sem->getCurrentsession();
								ASM::$sem->load(array('id' => $rSector)); 
								if (ASM::$sem->size() > 0) {
									if (ASM::$sem->get()->rColor == CTR::$data->get('playerInfo')->get('color')) {
										$law->options = serialize(array('name' => $name, 'rSector' => $rSector));
										ASM::$lam->add($law);
										ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
										CTR::redirect('faction/view-senate');
									} else {
										CTR::$alert->add('Ce secteur n\'est pas sous votre contrôle.', ALERT_STD_ERROR);
									}
								} else {
									CTR::$alert->add('Ce secteur n\'existe pas.', ALERT_STD_ERROR);
								}
								ASM::$sem->changeSession($_SEM);
							} else {
								CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
							}
							break;
						case COMTAXEXPORT:
							$taxes = round(Utils::getHTTPData('taxes'));
							$rColor = Utils::getHTTPData('rcolor');
							if ($taxes !== FALSE && $rColor !== FALSE) {
								$_CTM = ASM::$ctm->getCurrentsession();
								ASM::$ctm->load(array('faction' => CTR::$data->get('playerInfo')->get('color'), 'relatedFaction' => $rColor)); 
								if (ASM::$ctm->size() > 0) {
									if (ASM::$ctm->get()->relatedFaction == CTR::$data->get('playerInfo')->get('color')) {
										if ($taxes <= 15) {
											$law->options = serialize(array('taxes' => $taxes, 'rColor' => $rColor, 'display' => array('Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => ASM::$ctm->get()->exportTax . ' %', 'Taxe proposée' => $taxes . ' %')));
											ASM::$lam->add($law);
											ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
											CTR::redirect('faction/view-senate');
										} else {
											CTR::$alert->add('Pas plus que 15.', ALERT_STD_ERROR);
										}
									} else {
										if ($taxes <= 15 && $taxes >= 2) {
											$law->options = serialize(array('taxes' => $taxes, 'rColor' => $rColor, 'display' => array('Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => ASM::$ctm->get()->exportTax . ' %', 'Taxe proposée' => $taxes . ' %')));
											ASM::$lam->add($law);
											ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
											CTR::redirect('faction/view-senate');
										} else {
											CTR::$alert->add('Entre 2 et 15.', ALERT_STD_ERROR);
										}
									}
								} else {
									CTR::$alert->add('Cette faction n\'existe pas.', ALERT_STD_ERROR);
								}
								ASM::$sem->changeSession($_CTM);
							} else {
								CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
							}
							break;
						case COMTAXIMPORT:
							$taxes = round(Utils::getHTTPData('taxes'));
							$rColor = Utils::getHTTPData('rcolor');
							if ($taxes !== FALSE && $rColor !== FALSE) {
								$_CTM = ASM::$ctm->getCurrentsession();
								ASM::$ctm->load(array('faction' => CTR::$data->get('playerInfo')->get('color'), 'relatedFaction' => $rColor)); 
								if (ASM::$ctm->size() > 0) {
									if (ASM::$ctm->get()->relatedFaction == CTR::$data->get('playerInfo')->get('color')) {
										if ($taxes <= 15) {
											$law->options = serialize(array('taxes' => $taxes, 'rColor' => $rColor, 'display' => array('Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => ASM::$ctm->get()->importTax . ' %', 'Taxe proposée' => $taxes . ' %')));
											ASM::$lam->add($law);
											ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
											CTR::redirect('faction/view-senate');
										} else {
											CTR::$alert->add('Pas plus que 15.', ALERT_STD_ERROR);
										}
									} else {
										if ($taxes <= 15 && $taxes >= 2) {
											$law->options = serialize(array('taxes' => $taxes, 'rColor' => $rColor, 'display' => array('Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => ASM::$ctm->get()->importTax . ' %', 'Taxe proposée' => $taxes . ' %')));
											ASM::$lam->add($law);
											ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
											CTR::redirect('faction/view-senate');
										} else {
											CTR::$alert->add('Entre 2 et 15.', ALERT_STD_ERROR);
										}
									}
								} else {
									CTR::$alert->add('Cette faction n\'existe pas.', ALERT_STD_ERROR);
								}
								ASM::$sem->changeSession($_CTM);
							} else {
								CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
							}
							break;
						case PEACEPACT:
							$rColor = Utils::getHTTPData('rcolor');
							if ($rColor !== FALSE) {
								if ($rColor >= 1 && $rColor <= 7 && $rColor != ASM::$clm->get()->id) {

									if (ASM::$clm->get()->colorLink[$rColor] != Color::ALLY) {
										$law->options = serialize(array('rColor' => $rColor, 'display' => array('Faction' => ColorResource::getInfo($rColor, 'officialName'))));
										ASM::$lam->add($law);
										ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
										CTR::redirect('faction/view-senate');
									} else {
										CTR::$alert->add('Vous considérez déjà cette faction comme votre alliée.', ALERT_STD_ERROR);
									}
								} else {
									CTR::$alert->add('Cette faction n\'existe pas ou il s\'agit de la votre.', ALERT_STD_ERROR);
								}
							} else {
								CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
							}
							break;
						case WARDECLARATION:
							$rColor = Utils::getHTTPData('rcolor');
							if ($rColor !== FALSE) {
								if ($rColor >= 1 && $rColor <= 7 && $rColor != ASM::$clm->get()->id) {

									if (ASM::$clm->get()->colorLink[$rColor] != Color::ALLY) {
										$law->options = serialize(array('rColor' => $rColor, 'display' => array('Faction' => ColorResource::getInfo($rColor, 'officialName'))));
										ASM::$lam->add($law);
										ASM::$clm->get()->credits -= LawResources::getInfo($type, 'price');
										CTR::redirect('faction/view-senate');
									} else {
										CTR::$alert->add('Vous considérez déjà cette faction comme votre ennemmi.', ALERT_STD_ERROR);
									}
								} else {
									CTR::$alert->add('Cette faction n\'existe pas ou il s\'agit de la votre.', ALERT_STD_ERROR);
								}
							} else {
								CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
							}
							break;
						default:
							CTR::$alert->add('Cette loi n\'existe pas.', ALERT_STD_ERROR);
							break;
					}
				} else {
					CTR::$alert->add('Il n\'y assez pas a de crédits dans les caisses de l\'Etat.', ALERT_STD_ERROR);
				}
			}
			ASM::$clm->changeSession($_CLM);
		} else {
			CTR::$alert->add('Vous n\' avez pas le droit de proposer cette loi.', ALERT_STD_ERROR);
		}
	} else {
		CTR::$alert->add('Cette loi n\'existe pas.', ALERT_STD_ERROR);
	}
} else {
	CTR::$alert->add('Informations manquantes.', ALERT_STD_ERROR);
}
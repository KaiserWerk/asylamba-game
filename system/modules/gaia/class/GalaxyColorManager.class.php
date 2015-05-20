<?php
class GalaxyColorManager {
	public static function apply() {
		CTR::$applyGalaxy = TRUE;
	}

	public static function applyAndSave() {
		$gcm = new GalaxyColorManager();
		$gcm->loadSystem();
		$gcm->loadSector();
		$gcm->changeColorSystem();
		$gcm->changeColorSector();
		$gcm->saveSystem();
		$gcm->saveSector();
	}

	protected $system = array();
	protected $sector = array();

	public function loadSystem() {
		$db = DataBase::getInstance();
		$qr = $db->query('SELECT
			se.id AS id,
			se.rSector AS sector,
			se.rColor AS color,
			(SELECT COUNT(pl.id) FROM place AS pl WHERE pl.rSystem = se.id) AS nbPlace,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 1) AS color1,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 2) AS color2,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 3) AS color3,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 4) AS color4,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 5) AS color5,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 6) AS color6,
			(SELECT COUNT(pa.rColor) FROM place AS pl LEFT JOIN player AS pa ON pl.rPlayer = pa.id WHERE pl.rSystem = se.id AND pa.rColor = 7) AS color7
		FROM system AS se
		ORDER BY se.id');
		
		while ($aw = $qr->fetch()) {
			$this->system[$aw['id']] = array(
				'sector' => $aw['sector'],
				'systemColor' => $aw['color'],
				'nbPlace' => $aw['nbPlace'],
				'color' => array(
					'1' => $aw['color1'],
					'2' => $aw['color2'],
					'3' => $aw['color3'],
					'4' => $aw['color4'],
					'5' => $aw['color5'],
					'6' => $aw['color6'],
					'7' => $aw['color7']),
				'hasChanged' => FALSE
			);
		}
	}

	public function saveSystem() {
		$db = DataBase::getInstance();
		foreach ($this->system as $k => $v) {
			if ($v['hasChanged'] == TRUE) {
				$qr = $db->prepare('UPDATE system SET rColor = ? WHERE id = ?');
				$qr->execute(array($v['systemColor'], $k));
			}
		}
	}

	public function loadSector() {
		$db = DataBase::getInstance();
		$qr = $db->query('SELECT id, rColor, prime FROM sector ORDER BY id');
		while ($aw = $qr->fetch()) {
			$this->sector[$aw['id']] = array(
				'color' => $aw['rColor'],
				'prime' => $aw['prime'],
				'hasChanged' => FALSE
			);
		}
	}

	public function saveSector() {
		$db = DataBase::getInstance();
		foreach ($this->sector as $k => $v) {
			if ($v['hasChanged'] == TRUE) {
				$qr = $db->prepare('UPDATE sector SET rColor = ?, prime = ? WHERE id = ?');
				$qr->execute(array($v['color'], $v['prime'], $k));
			}
		}
	}

	public function changeColorSystem() {
		foreach ($this->system as $k => $v) {

			if ($v['systemColor'] + array_sum($v['color']) == 0) {
				# system blanc qui ne change pas
			} elseif ($v['systemColor'] != 0 && array_sum($v['color']) == 0) {
				# system pas blanc devient blanc

				$this->system[$k]['systemColor'] = 0;
				$this->system[$k]['hasChanged'] = TRUE;
			} else {
				# autre cas

				$currColor = $v['systemColor'];

				$usedArray = $v['color'];

				$frsNumber = max($usedArray);
				$temp = array_keys($usedArray, max($usedArray));
				$frsColor  = $temp[0];

				unset($usedArray[$frsColor]);

				$secNumber = max($usedArray);
				$temp = array_keys($usedArray, max($usedArray));
				$secColor  = $temp[0];

				if ($secNumber == 0) {
					if ($v['systemColor'] != $frsColor) {
						$this->system[$k]['systemColor'] = $frsColor;
						$this->system[$k]['hasChanged'] = TRUE;
					}
				} else {
					if ($frsNumber > $secNumber AND $frsColor != $v['systemColor']) {
						$this->system[$k]['systemColor'] = $frsColor;
						$this->system[$k]['hasChanged'] = TRUE;
					}
				}
			}
		}
	}

	public function changeColorSector() {
		$sectorUpdatedColor = [];

		foreach ($this->sector as $k => $v) {
			$colorRepartition = array(0, 0, 0, 0, 0, 0, 0);

			foreach ($this->system as $m => $n) {
				if ($n['sector'] == $k) {
					if ($n['systemColor'] != 0) {
						$colorRepartition[$n['systemColor'] - 1]++;
					}
				}
			}

			$nbrColor = max($colorRepartition);

			if ($v['color'] == 0) {
				$nbrColorSector = NULL;
			} else {
				$nbrColorSector = $colorRepartition[$v['color'] - 1];
			}

			if ($nbrColor >= LIMIT_CONQUEST_SECTOR) {
				$maxColor = array_keys($colorRepartition, max($colorRepartition));
				$this->sector[$k]['prime'] = FALSE;
				
				if ($nbrColorSector == NULL) {
					$sectorUpdatedColor[] = $this->sector[$k]['color'];
					$this->sector[$k]['color'] = $maxColor[0] + 1;
					$this->sector[$k]['hasChanged'] = TRUE;
				} elseif ($nbrColor > $nbrColorSector AND ($maxColor[0] + 1) != $v['color']) {
					$sectorUpdatedColor[] = $this->sector[$k]['color'];
					$this->sector[$k]['color'] = $maxColor[0] + 1;
					$this->sector[$k]['hasChanged'] = TRUE;
				}
			} else {
				# ne modifie pas un secteur prime s'il n'y a pas assez de joueur dedans
				if ($this->sector[$k]['prime'] == 0) {
					$sectorUpdatedColor[] = $this->sector[$k]['color'];
					$this->sector[$k]['color'] = 0;
					$this->sector[$k]['hasChanged'] = TRUE;
				}
			}
		}

		# enlève les doublons
		$sectorUpdatedColor = array_unique($sectorUpdatedColor);

		# compteur de faction
		$udpatedFaction = 0;

		# charge les factions concernées
		include_once DEMETER;
		$S_COL = ASM::$clm->getCurrentSession();
		ASM::$clm->newSession(FALSE);
		ASM::$clm->load([
			'id' => $sectorUpdatedColor,
			'isWinner' => Color::WIN_TARGET
		]);

		for ($l = 0; $l < ASM::$clm->size(); $l++) {
			$faction = ASM::$clm->get($l);

			# vérification des objectifs
			$isTargetsValid = FALSE;

			for ($i = 1; $i <= VictoryResources::size(); $i++) { 
				$targets = VictoryResources::getInfo($i, 'targets');
				$isTargetValid = TRUE;

				foreach ($targets as $key => $target) {
					$sectors = 0;

					foreach ($this->sector as $n => $s) {
						if ($this->sector[$n]['color'] == $faction->id && in_array($this->sector[$n]['color'], $target['sectors'])) {
							$sectors++;
						}
					}

					$isTargetValid = $sectors >= $target['nb']
						? $isTargetValid && TRUE
						: $isTargetValid && FALSE;
				}

				$isTargetsValid = $isTargetsValid || $isTargetValid;
			}
			
			if (!$isTargetsValid) {
				$faction->isWinner = Color::WIN_NO_TARGET;
				$faction->dClaimVictory = NULL;

				$udpatedFaction++;
			}
		}

		ASM::$clm->changeSession($S_COL);

		if ($udpatedFaction > 0) {
#			ASM::$clm->save();
		}
	}
}
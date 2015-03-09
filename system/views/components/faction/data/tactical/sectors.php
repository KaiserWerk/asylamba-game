<?php
$db = DataBase::getInstance();
$qr = $db->prepare('SELECT
		se.id AS id,
		se.rColor AS color,
		se.name AS name,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rSector = se.id) AS nbc0,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 1 AND sy.rSector = se.id) AS nbc1,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 2 AND sy.rSector = se.id) AS nbc2,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 3 AND sy.rSector = se.id) AS nbc3,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 4 AND sy.rSector = se.id) AS nbc4,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 5 AND sy.rSector = se.id) AS nbc5,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 6 AND sy.rSector = se.id) AS nbc6,
		(SELECT COUNT(sy.id) FROM system AS sy WHERE sy.rColor = 7 AND sy.rSector = se.id) AS nbc7
	FROM sector AS se
	ORDER BY (nbc' . $faction->id . ' / nbc0) DESC
');
$qr->execute();
$aw = $qr->fetchAll(); $qr->closeCursor();

$sectort = array(
	'Secteurs conquis' => array(),
	'Secteurs en balance' => array()
);

for ($i = 0; $i < count($aw); $i++) {
	if ($aw[$i]['color'] == $faction->id) {
		$sectort['Secteurs conquis'][] = $aw[$i];
	} else {
		$sectort['Secteurs en balance'][] = $aw[$i];
	}
}

echo '<div class="component">';
	echo '<div class="head skin-2">';
		echo '<h2>Territoires</h2>';
	echo '</div>';
	echo '<div class="fix-body">';
		echo '<div class="body">';
			foreach ($sectort as $type => $sectors) {
				$displayed = 0;

				echo '<h4>' . $type . '</h4>';
				echo '<ul class="list-type-1">';

				foreach ($sectors as $sector) {
					$percents = array();
					
					for ($j = 1; $j < 8; $j++) {
						$percents['color' . $j] = Format::percent($sector['nbc' . $j], $sector['nbc0']);
					}

					arsort($percents);

					if ($sector['color'] == $faction->id || ($sector['nbc' . $faction->id] > 0)) {
						echo '<li>';
							echo '<a href="#" class="picto color' . $sector['color'] . '">' . $sector['id'] . '</a>';
							echo '<span class="label">' . $sector['name'] . '</span>';
							echo '<span class="value">' . Format::percent($sector['nbc' . $faction->id], $sector['nbc0']) . ' %</span>';
							echo '<span class="progress-bar hb bl" title="partage des systèmes entre les factions">';
							foreach ($percents as $color => $percent) {
								echo '<span style="width:' . $percent . '%;" class="content ' . $color . '"></span>';
							}
							echo '</span>';
						echo '</li>';

						$displayed++;
					}
				}

				echo '</ul>';
				
				if ($displayed == 0) {
					echo '<p>Aucun secteur</p>';
				}
			}
		echo '</div>';
	echo '</div>';
echo '</div>';
?>
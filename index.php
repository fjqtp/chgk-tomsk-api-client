<?
$c = curl_init('http://rating.chgk.info/api/teams.json/search?name=&town=%D0%A2%D0%BE%D0%BC%D1%81%D0%BA');
curl_setopt_array($c, [
    CURLOPT_RETURNTRANSFER => true
]);
$result = curl_exec($c);
curl_close($c);
$result   = json_decode($result, true);
$items    = $result['items'];
$team_ids = [];
foreach ($items as $item) {
    $team_ids[$item['idteam']] = $item['name'];
}

$tournament_ids = [
    3773 => [
        'name'   => 'Кубок Квасира',
        'length' => 36
    ],
    3766 => [
        'name'   => 'Северовенецианский дебют',
        'length' => 36
    ],
    3850 => [
        'name'   => 'Юрьев день',
        'length' => 39
    ],
    3721 => [
        'name'   => 'Летний синхронный Умлаут',
        'length' => 36
    ],
];

foreach ($tournament_ids as $tournament_id => $tournament_data) {
    $c = curl_init("http://rating.chgk.info/api/tournaments/{$tournament_id}/list.json");
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true
    ]);
    $tournament_results = json_decode(curl_exec($c), true);
    curl_close($c);

    $city_results = [];
    foreach ($tournament_results as $tournament_result) {
        if (array_key_exists($tournament_result['idteam'], $team_ids)) {
            $city_results[$tournament_result['questions_total'] . '_' . $tournament_result['idteam']] = $tournament_result;
        }
    }

    if (!$city_results) continue;

    krsort($city_results, SORT_NATURAL);

    echo '<h2>';
    echo $tournament_data['name'];
    echo '</h2>';
    echo '<table>';
    echo '<tbody>';
    echo '<tr>';
    echo '<th>';
    echo 'Команда';
    echo '</th>';
    echo '<th>';
    echo 'Взято';
    echo '</th>';
    for ($i = 1; $i <= $tournament_data['length']; $i++) {
        echo '<th>';
        echo $i;
        echo '</th>';
    }
    foreach ($city_results as $city_result) {
        echo '<tr>';
        echo '<td>';
        echo $team_ids[$city_result['idteam']];
        echo '</td>';
        echo '<td>';
        echo $city_result['questions_total'];
        echo '</td>';

        $c = curl_init("http://rating.chgk.info/api/tournaments/{$tournament_id}/results/{$city_result['idteam']}");
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true
        ]);
        $team_results = json_decode(curl_exec($c), true);
        curl_close($c);
        foreach ($team_results as $tour) {
            foreach ($tour['mask'] as $plus) {
                echo '<td>';
                echo $plus;
                echo '</td>';
            }
        }
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}
?>
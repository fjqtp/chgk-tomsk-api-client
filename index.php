<?
require 'vendor/autoload.php';
use MemCachier\MemcacheSASL;

// Create client
$m = new MemcacheSASL();
$servers = explode(",", getenv('memcachier_server'));
foreach ($servers as $s) {
    $parts = explode(":", $s);
    $m->addServer($parts[0], $parts[1]);
}

// Setup authentication
$m->setSaslAuthData(getenv('memcachier_userid'), getenv('memcachier_password'));

if ($result = $m->get('result_table')){
    echo $result;
    exit;
}


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

$result_array = [];

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

    $result_array[] = '<h2>';
    $result_array[] = $tournament_data['name'];
    $result_array[] = '</h2>';
    $result_array[] = '<table>';
    $result_array[] = '<tbody>';
    $result_array[] = '<tr>';
    $result_array[] = '<th>';
    $result_array[] = 'Команда';
    $result_array[] = '</th>';
    $result_array[] = '<th>';
    $result_array[] = 'Взято';
    $result_array[] = '</th>';
    for ($i = 1; $i <= $tournament_data['length']; $i++) {
        $result_array[] = '<th>';
        $result_array[] = $i;
        $result_array[] = '</th>';
    }
    foreach ($city_results as $city_result) {
        $result_array[] = '<tr>';
        $result_array[] = '<td>';
        $result_array[] = $team_ids[$city_result['idteam']];
        $result_array[] = '</td>';
        $result_array[] = '<td>';
        $result_array[] = $city_result['questions_total'];
        $result_array[] = '</td>';

        $c = curl_init("http://rating.chgk.info/api/tournaments/{$tournament_id}/results/{$city_result['idteam']}");
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true
        ]);
        $team_results = json_decode(curl_exec($c), true);
        curl_close($c);
        foreach ($team_results as $tour) {
            foreach ($tour['mask'] as $plus) {
                $result_array[] = '<td>';
                $result_array[] = $plus;
                $result_array[] = '</td>';
            }
        }
        $result_array[] = '</tr>';
    }
    $result_array[] = '</tbody>';
    $result_array[] = '</table>';
}

$result = join('', $result_array);
$m->set('result_table', $result, 7200);
echo $result;
?>
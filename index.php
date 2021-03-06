<?
require 'vendor/autoload.php';
use MemCachier\MemcacheSASL;

$memcachier_server = getenv('memcachier_server');
$memcachier_userid = getenv('memcachier_userid');
$memcachier_password = getenv('memcachier_password');

// Create client
$m = new MemcacheSASL();
$servers = explode(",", $memcachier_server);
foreach ($servers as $s) {
    $parts = explode(":", $s);
    $m->addServer($parts[0], $parts[1]);
}

// Setup authentication
$m->setSaslAuthData($memcachier_userid, $memcachier_password);

if ($result = $m->get('result_table')){
    echo $result;
    exit;
}

if (!$team_ids = $m->get('team_ids')) {
    $c = curl_init('http://rating.chgk.info/api/teams.json/search?name=&town=%D0%A2%D0%BE%D0%BC%D1%81%D0%BA');
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true
    ]);
    $result = curl_exec($c);
    curl_close($c);
    $result   = json_decode($result, true);
    error_log($result);
    $items    = $result['items'];
    $team_ids = [];
    foreach ($items as $item) {
        $team_ids[$item['idteam']] = $item['name'];
    }
    $m->set('team_ids', $team_ids, 3600 * 24);
}

$tournament_ids = [
    3915,
    3816,
    3844,
    3773,
    3766,
    3850,
    3721,
];

$result_array = [];
$tournaments_data = $m->get('tournaments_data') ?: [];

foreach ($tournament_ids as $tournament_id) {
    $tournament_result_key = 'tournament_result' . $tournament_id;
    if ($results = $m->get($tournament_result_key)){
        $result_array[$tournament_id] = $results;
        continue;
    }

    $results = [];

    if (!$tournament_data = $tournaments_data[$tournament_id]) {
        $c = curl_init("http://rating.chgk.info/api/tournaments/{$tournament_id}");
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true
        ]);
        $tournament_data = json_decode(curl_exec($c), true)[0];
        $tournament_data['length'] = $tournament_data['tour_count'] * $tournament_data['tour_questions'];
        curl_close($c);
        $tournaments_data[$tournament_id] = $tournament_data;
        $m->set('tournaments_data', $tournaments_data, 3600 * 24 * 7);
    }

    $date_end = new DateTime($tournament_data['date_end']);
    $days_diff = date_diff($date_end, new DateTime())->d;
    $cache_duration = max($days_diff, 1) * 3600;//cache for hours = number of days from the end

    $c = curl_init("http://rating.chgk.info/api/tournaments/{$tournament_id}/list.json");
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true
    ]);
    $result = curl_exec($c);
    error_log($result);
    $tournament_results = json_decode($result, true);
    curl_close($c);

    $city_results = [];
    foreach ($tournament_results as $tournament_result) {
        if (array_key_exists($tournament_result['idteam'], $team_ids)) {
            $city_results[$tournament_result['questions_total'] . '_' . $tournament_result['idteam']] = $tournament_result;
        }
    }

    if (!$city_results) continue;

    krsort($city_results, SORT_NATURAL);

    $results[] = '<h2>';
    $results[] = $tournament_data['name'];
    $results[] = '</h2>';
    $results[] = '<table>';
    $results[] = '<tbody>';
    $results[] = '<tr>';
    $results[] = '<th>';
    $results[] = 'Команда';
    $results[] = '</th>';
    $results[] = '<th>';
    $results[] = 'Взято';
    $results[] = '</th>';
    for ($i = 1; $i <= $tournament_data['length']; $i++) {
        $results[] = '<th>';
        $results[] = $i;
        $results[] = '</th>';
    }
    foreach ($city_results as $city_result) {
        $results[] = '<tr>';
        $results[] = '<td>';
        $results[] = $team_ids[$city_result['idteam']];
        $results[] = '</td>';
        $results[] = '<td>';
        $results[] = $city_result['questions_total'];
        $results[] = '</td>';

        $c = curl_init("http://rating.chgk.info/api/tournaments/{$tournament_id}/results/{$city_result['idteam']}");
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true
        ]);
        $team_results = json_decode(curl_exec($c), true);
        curl_close($c);
        foreach ($team_results as $tour) {
            foreach ($tour['mask'] as $plus) {
                $results[] = '<td>';
                $results[] = $plus;
                $results[] = '</td>';
            }
        }
        $results[] = '</tr>';
    }
    $results[] = '</tbody>';
    $results[] = '</table>';
    $result_array[$tournament_id] = $results;
    $m->set($tournament_result_key, $results, $cache_duration);
}

$result = implode('', array_map(function ($entry) {
    return join('', $entry);
}, $result_array));
//$result = join('', $result_array);
$m->set('result_table', $result, 3600);
echo $result;
?>
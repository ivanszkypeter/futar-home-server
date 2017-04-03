<?php
function downloadVehicles($stopId) {
    $bkk = file_get_contents("http://futar.bkk.hu/bkk-utvonaltervezo-api/ws/otp/api/where/arrivals-and-departures-for-stop.json?" .
        "includeReferences="."agencies,routes,trips,stops" .
        "&stopId=".$stopId .
        "&minutesBefore="."0" .
        "&minutesAfter="."30" .
        "&appVersion=". "2.0.1-20151215222245" .
        "&key="."bkk-web");
    $bkk = json_decode($bkk);
    $results = [];
    foreach ($bkk->data->entry->stopTimes as $stopTime)
    {
        $id = $stopTime->tripId;
        $routeId = $bkk->data->references->trips->{$id}->routeId;
        $arrivalTime = null;
        if (isset($stopTime->predictedArrivalTime))
        {
            $arrivalTime = $stopTime->predictedArrivalTime;
        }
        else
        {
            $arrivalTime = $stopTime->arrivalTime;
        }

        $shortName = $bkk->data->references->routes->{$routeId}->shortName;
        $tripHeadsign = $bkk->data->references->trips->{$id}->tripHeadsign;
        $remainingTime = round(($arrivalTime-time())/60,0);
        $results[] = [
            "shortName" => $shortName,
            "tripHeadsign" =>  $tripHeadsign,
            "remainingTime" => $remainingTime
        ];

    }
    return $results;
}

function compareVehicles($a, $b)
{
    return $a['remainingTime'] - $b['remainingTime'];
}

$vehicles = array_merge(
    downloadVehicles("<ID_OF_THE_STOP>") // For example: BKK_F01232
);

usort($vehicles,"compareVehicles");
$vehicles = array_slice($vehicles, 0, 4);

$paramLength["shortName"] = 0;
$paramLength["remainingTime"] = 0;
foreach ($vehicles as $vehicle)
{
    if (strlen($vehicle['shortName']) > $paramLength["shortName"])
    {
        $paramLength["shortName"] = strlen($vehicle['shortName']);
    }
    if (strlen($vehicle['remainingTime']) > $paramLength["remainingTime"])
    {
        $paramLength["remainingTime"] = strlen($vehicle['remainingTime']);
    }
}
$paramLength["tripHeadsign"] = 20 - $paramLength["shortName"] - 1 - $paramLength["remainingTime"] - 1;
foreach ($vehicles as $vehicle)
{
    $vehicle["shortName"] = str_pad($vehicle["shortName"],$paramLength["shortName"]," ",STR_PAD_RIGHT);
    $vehicle["tripHeadsign"] = str_replace(['á','é','ó','ö','ő','ú','Ú'],['a','e','o','o','o','u','U'],$vehicle["tripHeadsign"]);
    $vehicle["tripHeadsign"] = substr($vehicle["tripHeadsign"], 0, $paramLength["tripHeadsign"]);
    $vehicle["tripHeadsign"] = str_pad($vehicle["tripHeadsign"], $paramLength["tripHeadsign"], " ", STR_PAD_RIGHT);
    $vehicle['remainingTime'] = str_pad($vehicle["remainingTime"], $paramLength["remainingTime"], " ", STR_PAD_LEFT);
    print $vehicle["shortName"]." ".$vehicle["tripHeadsign"]." ".$vehicle["remainingTime"]."#";
}

?>
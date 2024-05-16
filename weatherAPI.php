<?php

/*
https://openweathermap.org/api


DO NOT USE ANY PAID SOLUTIONS THERE

Create an application that allows you to enter city and/or country included, to display current weather situation in
that city.
I have added the API to gather the information. Explore the API and how to access the data.

- Acquire API key that will allow you to get the data
- Use PHP in-built methods to gather the data from the API (DO NOT USE GUZZLE/Packigist) for now

This must be done in its own repository
*/

function getCoordinatesFromAPI(string $apiKey, string $city, string $country): array
{
    $baseLink = "http://api.openweathermap.org/geo/1.0/direct?";
    $q = "$city,,$country";
    $data = ["q" => $q, "limit" => 1, "appid" => $apiKey];
    $query = http_build_query($data);
    $url = $baseLink . $query;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($curl);

    $response = json_decode($response);

    if (!$response) {
        curl_close($curl);
        exit("Failed to connect to geocache API.\n");
    }

    $httpCode = (curl_getinfo($curl, CURLINFO_HTTP_CODE));

    if ($httpCode !== 200) {
        if (property_exists($response, "message")) {
            echo ucfirst($response->message) . "\n";
            exit;
        }
        exit("Unable to get weather data. Status code - $httpCode\n");
    }

    curl_close($curl);

    if (count($response) === 0) {
        exit("Location could not be found!\n");
    }

    return $response;
}

function getWeatherFromAPI(string $apikey, float $latitude, float $longitude): stdClass
{
    $baseLink = "https://api.openweathermap.org/data/2.5/weather?";
    $data = ["lat" => $latitude, "lon" => $longitude, "appid" => $apikey, "units" => "metric"];
    $query = http_build_query($data);
    $url = $baseLink . $query;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($curl);

    if (!$response) {
        curl_close($curl);
        exit("Failed to connect to geocache API.\n");
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $response = json_decode($response);

    if ($httpCode !== 200) {
        if (property_exists($response, "message")) {
            echo ucfirst($response->message) . "\n";
            exit;
        }
        exit("Unable to get weather data. Status code - $httpCode\n");
    }

    if (empty($response)) {
        exit("Failed to get weather for location.\n");
    }

    curl_close($curl);

    return $response;
}

function getCardinalFromAngle(float $windAngle): string
{
    $cardinal = "";
    $degreeLeniency = 60;
    if (360 - $windAngle <= $degreeLeniency || $windAngle <= $degreeLeniency) {
        $cardinal .= "north";
    } elseif (abs(180 - $windAngle) <= $degreeLeniency) {
        $cardinal .= "south";
    }
    if (abs(90 - $windAngle) <= $degreeLeniency) {
        $cardinal .= "east";
    } elseif (abs(270 - $windAngle) <= $degreeLeniency) {
        $cardinal .= "west";
    }
    return $cardinal;
}


while (true) {
    echo "Please provide your API key.\n";
    $apiKey = readline("Key - ");
    if ($apiKey === "") {
        echo "API key cannot be empty.\n";
        continue;
    }
    break;
}

while (true) {
    $country = readline("Enter country: ");
    if (preg_match('~[0-9]+~', $country)) {
        echo "Country name cannot contain numbers.\n";
        continue;
    }
    if ($country == "") {
        echo "Country name cannot be empty.\n";
        continue;
    }
    break;
}

while (true) {
    $city = readline("Enter city: ");
    if (preg_match('~[0-9]+~', $city)) {
        echo "City name cannot contain numbers.\n";
        continue;
    }
    if ($city == "") {
        echo "City name cannot be empty.\n";
        continue;
    }
    break;
}

$response = getCoordinatesFromAPI($apiKey, $city, $country);
$response = $response[0];
$city = $response->name;
$country = $response->country;
$lat = $response->lat;
$lon = $response->lon;

$weather = getWeatherFromAPI($apiKey, $lat, $lon);
$weatherCondition = lcfirst($weather->weather[0]->main);
$weatherConditionDescription = $weather->weather[0]->description;
$temperature = $weather->main->temp;
$windSpeed = $weather->wind->speed;
$windAngle = $weather->wind->deg;
$windDirection = getCardinalFromAngle($windAngle);
$cloudiness = $weather->clouds->all;

echo "In $city, $country the current weather condition is $weatherCondition ($weatherConditionDescription)\n";
echo "The temperature is {$temperature}C and cloudiness is at {$cloudiness}%\n";
echo "The wind speed is {$windSpeed}m/s in the direction of $windDirection\n";

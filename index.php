<?php
require 'AirTraffic.php';
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 23.01.2016
 * Time: 1:15
 */
$routes = new Aircraft\AirTraffic();

function consoleTraffic($data)
{
    $welcomeText = "
Enter the number of function:
1. distance of route
2. arrival time
3. distance of segment
4. arrival time to point
5. amount of in-air routs
6. quit\n
";
    $options = [1, 2, 3, 4, 5, 6];
    fwrite(STDOUT, $welcomeText);

    do {
        $selection = fgetc(STDIN);
    } while (!in_array(trim($selection), $options));

    if ($selection == '1') {
        fwrite(STDOUT, "Enter the number of route\n");
        do {
            $routeNumber = fgets(STDIN);
        } while (trim($routeNumber) == '');
        $routeNumber = trim($routeNumber);
        $route = $data->memcache->get(trim($routeNumber));
        if (!$route) {
            fwrite(STDOUT, "\nNo route with this number\n");
            consoleTraffic($data);
        } else {
            fwrite(STDOUT, "\nDistance is " . strval(round($data->distance($routeNumber), 2)) . " km\n");
            consoleTraffic($data);
        }
    } else if ($selection == '2'){
        fwrite(STDOUT, "Enter the number of route\n");
        do {
            $routeNumber = fgets(STDIN);
        } while (trim($routeNumber) == '');
        $routeNumber = trim($routeNumber);
        $route = $data->memcache->get(trim($routeNumber));
        if (!$route) {
            fwrite(STDOUT, "\nNo route with this number\n");
            consoleTraffic($data);
        } else {
            fwrite(STDOUT, "\nArrival time is " . $data->timeArrival($routeNumber)->format('Y-m-d H:i:s') . "\n");
            consoleTraffic($data);
        }
    } else if ($selection == '5'){
        $amount = $data->inAir();
        fwrite(STDOUT, "\nAmount of in-air routes is " . strval($amount) . "\n");
        consoleTraffic($data);
    } else if ($selection == '6'){
        exit;
    } else if ($selection == '3' || $selection == '4'){
        fwrite(STDOUT, "Sorry! Function is not implemented yet :(\n");
        consoleTraffic($data);
    }

}

consoleTraffic($routes);

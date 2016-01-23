<?php

namespace Aircraft;

require 'Config.php';

Class AirTraffic {
    public $memcache=0;
    function __construct(){
        $this->memcache = new \Memcache;
        $this->memcache->connect(Config::MEMCACHED_HOST, Config::MEMCACHED_PORT) or die ("Could not connect");
        if (!$this->memcache->get('is_active')){
            $scheduleJson = file_get_contents(Config::PATH_TO_SCHEDULE_FILE);
            $schedule = json_decode(@$scheduleJson);
            $routes = $schedule->routes;
            foreach($routes as $route => $params) {
                $this->memcache->set( $route, $params, MEMCACHE_COMPRESSED, 0 );
            }
            $this->memcache->set('is_active', true, MEMCACHE_COMPRESSED, 60);
        }
    }

    function distance($number){
        //S = 111,2×arccos(sin φ1 × sin φ2 + cos φ1 × cos φ2 × cos (L2-L1))
        $route = $this->getRouteByNumber($number);
        if ($route) {
            $pointsCount = count($route->tr);
            $S = 0;
            for ($i = 0; $i < $pointsCount - 1; $i++) {
                $startLat = $route->tr[$i][0];
                $startLon = $route->tr[$i][1];
                $finishLat = $route->tr[$i + 1][0];
                $finishLon = $route->tr[$i + 1][1];
                $partS = 111.2 * acos(sin($startLat) * sin($finishLat)
                        + cos($startLat) * cos($finishLat) * cos($finishLon - $startLon));
                $S = $S + $partS;
            }
            return $S;
        } else {
            return false;
        }
    }

    function timeArrival($number){
        $route = $this->getRouteByNumber($number);
        if ($route) {
            $S = $this->distance($number);
            $V = $route->speed;
            $t = round(($S / $V) * 60, 0);
            $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', $route->start,
                new \DateTimeZone('UTC'));
            return $startTime->modify("+{$t} minutes");
        } else {
            return false;
        }
    }

    function partDistance($number, $n){
        $route = $this->getRouteByNumber($number);
        if ($route){
            $pointsCount = count($route->tr);
            if ($n > $pointsCount-1)
                return false;
            $startLat = $route->tr[$n - 1][0];
            $startLon = $route->tr[$n - 1][1];
            $finishLat = $route->tr[$n][0];
            $finishLon = $route->tr[$n][1];
            return 111.2 * acos(sin($startLat) * sin($finishLat)
                    + cos($startLat) * cos($finishLat) * cos($finishLon - $startLon));
        } else {
            return false;
        }
    }

    function partTimeArrival($number, $n){
        $route = $this->getRouteByNumber($number);
        if ($route){
            $pointsCount = count($route->tr);
            if ($n > $pointsCount || $n == 1)
                return false;
            $S = 0;
            for ($i=1; $i<$n; $i++){
                $S = $S + $this->partDistance($number, $i);
            }
            $V = $route->speed;
            $t = round(($S / $V) * 60, 0);
            $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', $route->start,
                new \DateTimeZone('UTC'));
            return $startTime->modify("+{$t} minutes");
        } else {
            return false;
        }
    }

    function inAir(\DateTime $date = null){
        if (!$date) {
            $date = new \DateTime(null, new \DateTimeZone("UTC"));
        } else {
            $date->setTimezone(new \DateTimeZone("UTC"));
        }
        $scheduleJson = file_get_contents(Config::PATH_TO_SCHEDULE_FILE);
        $schedule = json_decode(@$scheduleJson);
        $routes = $schedule->routes;
        $inAirCount = 0;
        foreach($routes as $number => $params) {
            $startTime = \DateTime::createFromFormat('Y-m-d H:i:s', $params->start,
                new \DateTimeZone('UTC'));
            if ($startTime < $date){
                $arrivalTime = $this->timeArrival($number);
                if ($arrivalTime > $date)
                    $inAirCount++;
            }
        }
        return $inAirCount;
    }

    private function getRouteByNumber($number){
        $route = $this->memcache->get($number);
        return $route?$route:false;
    }
}
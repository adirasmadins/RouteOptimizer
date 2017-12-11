<?php

namespace App\Services\TSP;

class City {

    public $x;
    public $y;
    public $id;

    public function __construct($x, $y, $id)
    {
        $this->x = $x;
        $this->y = $y;
        $this->id = $id;
    }

    public function getX()
    {
        return $this->x;
    }

    public function getY()
    {
        return $this->y;
    }

    public function getId()
    {
        return $this->id;
    }

    public function distanceTo(City $city, $earthRadius = 6371000) {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$this->getX().",".$this->getY()."&destinations=".$city->getX().",".$city->getY()."&mode=driving&language=pl-PL";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response, true);
        $dist = $response_a['rows'][0]['elements'][0]['distance']['value'];
        $time = $response_a['rows'][0]['elements'][0]['duration']['value'];

        return array($dist, $time);

//        $latFrom = deg2rad($this->getX());
//        $lonFrom = deg2rad($this->getY());
//        $latTo = deg2rad($city->getX());
//        $lonTo = deg2rad($city->getY());
//
//        $latDelta = $latTo - $latFrom;
//        $lonDelta = $lonTo - $lonFrom;
//
//        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
//                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
//        return $angle * $earthRadius;

    }

    public function __toString()
    {
        return $this->getX() . ', '  . $this->getY();
    }
}
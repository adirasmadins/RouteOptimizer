<?php
namespace App\Services\TSP;

class Tour
{
    private $tour = array();
    private $fitness = 0;
    private $distance = 0;
    private $tourTime = 0;
    private $distanceMatrix = array();
    private $timeMatrix = array();

    public function __construct($distanceMatrix, $timeMatrix)
    {
        $this->tour = array_fill(0, TourManager::numberOfCities(), null);
        $this->distanceMatrix = $distanceMatrix;
        $this->timeMatrix = $timeMatrix;
    }

    public function generateIndividual()
    {
        for ($cityIndex = 0, $len = TourManager::numberOfCities(); $cityIndex < $len; $cityIndex++) {
            $this->setCity($cityIndex, TourManager::getCity($cityIndex));
        }

        shuffle($this->tour);

        if(($this->getCity(0))->getId() != 0) {
            $city1 = $this->getCity(0);

            $cityKey = null;
            foreach ($this->getTour() as $key => $city) {
                if($city->getId() == 0) {
                    $cityKey = $key;
                }
            }

            $city0 = $this->getCity($cityKey);

            $this->setCity(0, $city0);
            $this->setCity($cityKey, $city1);
        }
    }


    public function getCity($tourPosition)
    {
        return $this->tour[$tourPosition];
    }

    public function getTour()
    {
        return $this->tour;
    }

    public function setCity($tourPosition, City $city)
    {
        $this->tour[$tourPosition] = $city;

        $this->fitness = 0;
        $this->distance = 0;
    }

    public function getFitness()
    {
        if ($this->fitness == 0)
            $this->fitness = 1 / (double)$this->getDistance();

        return $this->fitness;
    }

    public function getDistance()
    {
        if ($this->distance == 0) {
            $tourDistance = 0;
            $tourTime = 0;

            for ($cityIndex = 0, $len = $this->tourSize(); $cityIndex < $len; $cityIndex++) {
                $fromCity = $this->getCity($cityIndex);
                $destinationCity = null;

                // Check we're not on our tour's last city, if we are set our
                // tour's final destination city to our starting city
                if ($cityIndex + 1 < $this->tourSize()) {
                    $destinationCity = $this->getCity($cityIndex + 1);
                } else {
                    $destinationCity = $this->getCity(0);
                }

                $tourDistance += $this->distanceMatrix[$fromCity->getId()][$destinationCity->getId()];

                $tourTime += $this->timeMatrix[$fromCity->getId()][$destinationCity->getId()];

            }

            $this->distance = $tourDistance;

            $this->tourTime = $tourTime;

        }

        return $this->distance;
    }

    public function getTime()
    {
        return $this->tourTime;
    }


    public function tourSize()
    {
        return count($this->tour);
    }

    public function containsCity(City $city)
    {
        return in_array($city, $this->tour);
    }

}

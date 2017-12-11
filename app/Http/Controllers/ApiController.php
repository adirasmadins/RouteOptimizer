<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\TSP\City;
use App\Services\TSP\GA;
use App\Services\TSP\Population;
use App\Services\TSP\TourManager;

class ApiController extends Controller
{
    public function index(Request $request)
    {
        $cities = $request->json()->all();

        foreach ($cities as $key =>$city)
        {
            $city = new City($city['lat'], $city['lng'], $key);
            TourManager::addCity($city);
        }
//        $city1 = new City(50.4501, 30.523400000000038);
//        TourManager::addCity($city1);
//        $city2 = new City(50.0637682, 29.90496840000003);
//        TourManager::addCity($city2);
//        $city3 = new City(49.227717, 31.85223289999999);
//        TourManager::addCity($city3);
//        $city4 = new City(49.5405738, 30.877205499999945);
//        TourManager::addCity($city4);
//        $city5 = new City(49.8620646, 30.819239899999957);
//        TourManager::addCity($city5);
//        $city6 = new City(49.82478829999999, 30.405336900000066);
//        TourManager::addCity($city6);
//        $city7 = new City(49.8697568, 30.395965700000033);
//        TourManager::addCity($city7);

        GA::generateDistanceMatrix();

        $pop = new Population(150, true);

        // Evolve population for 100 generations
        $pop = GA::evolvePopulation($pop);
        for ($i = 0; $i < 100; $i++) {
            $pop = GA::evolvePopulation($pop);
        }

        $tour['distance'] = $pop->getFittest()->getDistance();
        $tour['time'] = $pop->getFittest()->getTime();

        foreach ($pop->getFittest()->getTour() as $city)
        {
            $tour['cities'][] = [
                'lat' => $city->getX(),
                'lng' => $city->getY()
            ];
        }

        return response()->json($tour);
    }

}

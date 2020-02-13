<?php

namespace App\Services;

use Carbon\Carbon;

class TourDataService
{
    protected $api;

    const API_URL = '/module/search?type=1&items_per_page=20&hotel_info=1';
    const API_MODULE_PARAMS = '/module/params/';

    const FORM_DATA_SESSION = 'form_data';

    const COUNTRY_DEFAULT = 338; /* Египет */
    const FROM_CITY_DEFAULT = 2014; /* Киев */

    const MEAL_TYPE_DEFAULT = '388:498:496:1956:560:512'; /* Любой */

    const ADULTS_DEFAULT = 2;
    const CHILDREN_DEFAULT = 0;

    const HOTEL_RATINGS_DEFAULT = '78:4'; /* 5 и 4 */

    const POPULAR_COUNTRY = 1;
    const OTHER_COUNTRY = 2;

    public function __construct()
    {
        $this->api = new ApiOtour();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHotels($page)
    {
        $url = $this->getSearchUrl($page);
        $this->api->setAddr($url);
        return response()->json($this->api->json());
    }

    /**
     * Return prepared url
     * @param null $page
     * @return string
     */
    public function getSearchUrl($page=null): string
    {
        $data = $this->getSearchData();
        $url = self::API_URL
            . ($data['country'] ? '&country=' . $data['country'] : null)
            . (isset($data['region']) ? '&region=' . $this->getPreparedUrlData($data['region']) : null)
            . (isset($data['meal_type']) ? '&meal_type=' . $this->getPreparedUrlData($data['meal_type']) : null)
            . ($data['from_city'] ? '&from_city=' . $data['from_city'] : null)
            . ($data['adult_amount'] ? '&adult_amount=' . $data['adult_amount'] : null)
            . (isset($data['child_amount']) ? '&child_amount=' . $data['child_amount'] : null)
            . (isset($data['child_age']) ? '&child_age=' . $this->getPreparedUrlData($data['child_age']) : null)
            . ($data['hotel_rating'] ? '&hotel_rating=' . $this->getPreparedUrlData($data['hotel_rating']) : null)
            . ($data['night_from'] ? '&night_from=' . $data['night_from'] : null)
            . ($data['night_till'] ? '&night_till=' . $data['night_till'] : null)
            . ($data['date_from'] ? '&date_from=' . $data['date_from'] : null)
            . ($data['date_till'] ? '&date_till=' . $data['date_till'] : null)
            . ($page ? '&page=' . $page : null);
        return $url;
    }

    /**
     * Return Data for Controller
     * @return array
     */
    public function getSearchData(): array
    {
        if (session(self::FORM_DATA_SESSION) !== null) {
            return $this->getSessionData();
        }
        return $this->getDefaultData();
    }

    /**
     * @return array
     */
    public function getDefaultData(): array
    {
        $data = [
            'country' => self::COUNTRY_DEFAULT,
            'region' => null,
            'from_city' => self::FROM_CITY_DEFAULT,
            'meal_type' => explode(":", self::MEAL_TYPE_DEFAULT),
            'adult_amount' => self::ADULTS_DEFAULT,
            'child_amount' => self::CHILDREN_DEFAULT,
            'child_age' => null,
            'hotel_rating' => explode(":", self::HOTEL_RATINGS_DEFAULT),
            'date_from' => Carbon::today()->addDay()->format('d.m.y'),
            'date_till' => Carbon::today()->addDays(6)->format('d.m.y'),
            'night_from' => 6,
            'night_till' => 10,
        ];
        return $data;
    }

    /**
     * Return form data session
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */
    public function getSessionData()
    {
        return session(self::FORM_DATA_SESSION);
    }

    /**
     * @param array $requestData
     */
    public function setSessionData(array $requestData)
    {
        $data = [
            'country' => $requestData['country'],
            'region' => isset($requestData['region']) ? $this->getPreparedSessionData($requestData['region']) : null,
            'from_city' =>  $requestData['from_city'],
            'meal_type' => isset($requestData['meal_type']) ? $this->getPreparedSessionData($requestData['meal_type']) : null,
            'adult_amount' => $requestData['adult_amount'],
            'child_amount' => isset($requestData['child_amount']) ? $requestData['child_amount'] : null,
            'child_age' => isset($requestData['child_age']) ? $this->getPreparedSessionData($requestData['child_age']) : null,
            'hotel_rating' => isset($requestData['hotel_rating']) ? $this->getPreparedSessionData($requestData['hotel_rating']) : $this->getPreparedSessionData(self::HOTEL_RATINGS_DEFAULT),
            'date_from' => isset($requestData['date_from']) ? $requestData['date_from'] : Carbon::today()->addDay()->format('d.m.y'),
            'date_till' => isset($requestData['date_till']) ? $requestData['date_till'] : Carbon::today()->addDays(6)->format('d.m.y'),
            'night_from' => isset($requestData['night_from']) ? $requestData['night_from'] : 6,
            'night_till' => isset($requestData['night_till']) ? $requestData['night_till'] : 10,
        ];
        session([self::FORM_DATA_SESSION => $data]);
    }

    /**
     * Return grouped countries list
     * @return array
     */
    public function getCountriesList(): array
    {
        $params = $this->api->getParams();
        $countries = [];
        foreach ($params->countries as $country) {
            if ($country->group_id == self::POPULAR_COUNTRY) {
                $countries['popular'][] = $country;
            } elseif ($country->group_id == self::OTHER_COUNTRY) {
                $countries['rare'][] = $country;
            }
        }
        return $countries;
    }

    /**
     * Return array of cities objects
     * @param $countryId
     * @return array
     */
    public function getFromCitiesList($countryId): array
    {
        $url = self::API_MODULE_PARAMS .  $countryId . '?entity=from_city';
        $this->api->setAddr($url);
        $response = response()->json($this->api->json());
        return $response->original->from_cities;
    }

    /**
     * Return prepared string for api request
     * @param array|string $data
     * @return string
     */
    public function getPreparedUrlData($data): string
    {
        if (!is_array($data)) {
            return $data;
        }
        return implode(':', $data);
    }

    /**
     * Return prepared array for session
     * @param array|string $data
     * @return array
     */
    public function getPreparedSessionData($data): array
    {
        if (is_array($data)) {
            return $data;
        }
        return explode(':', $data);
    }
}
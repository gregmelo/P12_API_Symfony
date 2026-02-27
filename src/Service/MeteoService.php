<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Entity\User;

/**
 * Service dédié à la récupération de la météo via une API externe
 * (OpenWeatherMap) avec mise en cache.
 *
 * Il encapsule toute la logique d'appel HTTP, de simplification
 * des données retournées et de mise en cache pour éviter de
 * surcharger l'API publique.
 */
class MeteoService
{
    private string $apiKey;
    private string $baseUrl;
    private HttpClientInterface $httpClient;
    private CacheInterface $cache;

    /**
     * @param HttpClientInterface $httpClient Client HTTP Symfony utilisé pour appeler l'API externe.
     * @param CacheInterface      $cache      Cache applicatif (pool "app") pour stocker les réponses.
     * @param string              $apiKey     Clé API OpenWeatherMap.
     * @param string              $baseUrl    URL de base de l'endpoint météo (surchargeable si besoin).
     */
    public function __construct(
        HttpClientInterface $httpClient,
        CacheInterface $cache,
        string $apiKey,
        string $baseUrl = 'https://api.openweathermap.org/data/2.5/weather'
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Récupère la météo pour une ville donnée (nom brut transmis à l'API).
     *
     * Le résultat est mis en cache quelques minutes pour limiter les appels
     * à l'API publique.
     *
     * @param string $city Nom de la ville telle que saisie par l'utilisateur.
     *
     * @return array|null Tableau de données météo simplifiées ou null si ville introuvable.
     */
    public function getWeatherForCity(string $city): ?array
    {
        $city = trim($city);
        if ($city === '') {
            return null;
        }

        // Clé de cache dérivée du nom de la ville (normalisée en minuscule)
        $cacheKey = 'weather_' . md5(mb_strtolower($city));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city) {
            // TTL de 10 minutes : évite de re‑questionner l'API à chaque appel
            $item->expiresAfter(600);

            // Appel HTTP à l'API OpenWeatherMap
            $response = $this->httpClient->request('GET', $this->baseUrl, [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 404) {
                // Ville non trouvée par l'API météo
                return null;
            }

            if ($statusCode >= 400) {
                // Pour une première version on ne gère pas tous les cas d'erreur finement
                return null;
            }

            $data = $response->toArray(false);

            // On simplifie les informations retournées pour l'API interne
            return [
                'city' => $data['name'] ?? $city,
                'temperature' => $data['main']['temp'] ?? null,
                'feels_like' => $data['main']['feels_like'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'description' => $data['weather'][0]['description'] ?? null,
                'wind_speed' => $data['wind']['speed'] ?? null,
            ];
        });
    }

    /**
     * Récupère la météo pour la ville associée à un utilisateur.
     *
     * @param User $user Utilisateur dont on utilise la ville du profil.
     *
     * @return array|null Données météo ou null si la ville est absente ou introuvable.
     */
    public function getWeatherForUser(User $user): ?array
    {
        $city = $user->getCity();
        if ($city === null || trim($city) === '') {
            return null;
        }

        return $this->getWeatherForCity($city);
    }
}

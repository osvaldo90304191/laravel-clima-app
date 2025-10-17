<?php

namespace App\Console\Commands;

use App\Models\WeatherResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetWeatherCommand extends Command
{
    // Requerimiento: Firma del comando con un argumento 'city'
    protected $signature = 'weather:get {city}';
    protected $description = 'Consulta el clima para una ciudad y guarda el resultado en MongoDB.';

    public function handle()
    {
        $city = $this->argument('city');
        $apiKey = env('OPENWEATHER_API_KEY');
        $baseUrl = env('OPENWEATHER_BASE_URL');

        // Verificación de clave API (necesaria para Guzzle)
        if (empty($apiKey)) {
            $this->error("ERROR: La clave API de OpenWeatherMap no está configurada en el .env.");
            Log::error("API Key no configurada.");
            return Command::FAILURE;
        }

        // EXTRA: Lógica de Deduplicación (Evita consultas recientes duplicadas)
        if ($this->isRecentQuery($city)) {
            $this->warn("Consulta para '{$city}' reciente (menos de 30 minutos). Saltando la llamada a la API.");
            Log::info("Consulta deduplicada", ['city' => $city]);
            return Command::SUCCESS;
        }

        $this->info("Iniciando consulta para la ciudad: {$city}...");
        Log::info("Iniciando consulta", ['city' => $city]); // EXTRA: Logs

        try {
            // Petición a la API de OpenWeatherMap usando el Facade HTTP de Laravel
            $response = Http::get($baseUrl, [
                'q' => $city,
                'appid' => $apiKey,
                'units' => 'metric', // Para obtener la temperatura en Celsius
            ]);

            if ($response->failed() || !isset($response->json()['main']['temp'])) {
                $this->error("Error al consultar la API para {$city}. Código: " . $response->status());
                Log::error("Fallo de API", ['city' => $city, 'status' => $response->status(), 'response' => $response->body()]);
                return Command::FAILURE;
            }

            $data = $response->json();
            
            // Requerimiento: Guardar en MongoDB
            $weather = WeatherResult::create([
                'city_name' => $data['name'] ?? $city,
                'temperature' => $data['main']['temp'],
                'coordinates' => [
                    'lon' => $data['coord']['lon'],
                    'lat' => $data['coord']['lat'],
                ],
                'query_datetime' => now(), // Requerimiento: Fecha y hora de consulta
                'raw_response' => $data,
            ]);

            // Requerimiento: Mostrar información en la consola
            $this->info("¡Éxito! Clima guardado en MongoDB para: {$weather->city_name}");
            $this->line("-> Temp: {$weather->temperature}°C | Coords: {$weather->coordinates['lat']}, {$weather->coordinates['lon']}");

            Log::info("Datos guardados en MongoDB", ['id' => $weather->_id, 'city' => $city]); // EXTRA: Logs
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error desconocido durante la ejecución: " . $e->getMessage());
            Log::error("Excepción al consultar el clima", ['error' => $e->getMessage(), 'city' => $city]);
            return Command::FAILURE;
        }
    }

    /**
     * Verifica si ya existe una consulta para la ciudad en los últimos 30 minutos (Deduplicación).
     */
    protected function isRecentQuery(string $city): bool
    {
        // Busca en la colección 'weather_results' de MongoDB
        return WeatherResult::where('city_name', $city)
                            ->where('query_datetime', '>=', now()->subMinutes(30))
                            ->exists();
    }
}
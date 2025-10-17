<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\WeatherResult;          

class WeatherController extends Controller
{
    /**
     * Endpoint HTTP que recibe la ciudad, ejecuta el comando y retorna el resultado JSON.
     */
    public function getWeather(Request $request)
    {
        $city = $request->query('city');

        if (empty($city)) {
            return response()->json(['error' => 'El parámetro "city" es obligatorio.'], 400);
        }

        // Llama al Comando Artisan para ejecutar la lógica de API y guardado en MongoDB
        Artisan::call('weather:get', ['city' => $city]);

        // Busca el último resultado guardado en MongoDB para esa ciudad
        $result = WeatherResult::where('city_name', $city)
                    ->orderBy('query_datetime', 'desc')
                    ->first();

        // Retorna el resultado en JSON
        if (!$result) {
            return response()->json([
                'message' => "No se encontró información reciente. El comando pudo haber fallado o fue deduplicado."
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'city_searched' => $city,
            'data' => $result,
        ]);
    }
}
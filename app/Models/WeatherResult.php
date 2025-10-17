<?php
// app/Models/WeatherResult.php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model; // NECESARIO para usar MongoDB

class WeatherResult extends Model
{
    // Conexión definida en .env
    protected $connection = 'mongodb'; 
    protected $collection = 'weather_results'; 

    /**
     * Los atributos que se pueden asignar masivamente.
     * Requerimiento: Guardar nombre de la ciudad, temperatura, coordenadas, y fecha/hora de consulta.
     */
    protected $fillable = [
        'city_name',
        'temperature',
        'coordinates', 
        'query_datetime',
        'raw_response', // Opcional, útil para guardar la respuesta completa de la API
    ];
}
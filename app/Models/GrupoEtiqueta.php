<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoEtiqueta extends Model
{
    use HasFactory;

    public function etiquetas(){
        return $this->hasMany(Etiqueta::class);
    }
}

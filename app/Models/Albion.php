<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Albion extends Model
{
    use HasFactory;
    protected $table = 'albion_table';

    public function getHumanUtc()
    {
        return \Carbon\Carbon::createFromTimestamp($this->Utc)->subHour()->diffForHumans();
    }

    public function getHumanName()
    {
        return AlbionItem::where('machine_name', $this->ItemTypeId)->first()->human_name ?? $this->ItemTypeId;
    }

    public function getQualityName()
    {
        if ($this->QualityLevel == 1) {
            return 'Normal';
        } elseif ($this->QualityLevel == 2) {
            return 'Good';
        } elseif ($this->QualityLevel == 3) {
            return 'Outstanding';
        } elseif ($this->QualityLevel == 4) {
            return 'Excellent';
        } elseif ($this->QualityLevel == 5) {
            return 'Masterpiece';
        } else {
            return 'Unknown';
        }
    }
}

<?php

namespace App\Models;

use App\Models\Phrase;
use App\Traits\CreatedUpdatedBy;
use App\Traits\GetTableName;
use App\Traits\HasUniqueIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PhraseDetail extends Model
{
	use HasApiTokens, HasFactory, Notifiable, HasUniqueIdentifier, GetTableName;
	protected $table = 'v_phrase_details';
	protected $primaryKey = 'phrase_detail_uuid';
	public $incrementing = false;
	protected $keyType = 'string';	// TODO, check if UUID is valid
	const CREATED_AT = 'insert_date';
	const UPDATED_AT = 'update_date';

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
	protected $fillable = [
        'phrase_uuid',
        'domain_uuid',
        'phrase_detail_group',
        'phrase_detail_tag',
        'phrase_detail_pattern',
        'phrase_detail_function',
        'phrase_detail_data',
        'phrase_detail_method',
        'phrase_detail_type',
        'phrase_detail_order'
	];


	public function phrase(): BelongsTo {
		return $this->belongsTo(Phrase::class, 'phrase_uuid', 'phrase_uuid');
	}
}

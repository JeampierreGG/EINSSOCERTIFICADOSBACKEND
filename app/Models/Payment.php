<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'payment_method_id',
        'status',
        'amount',
        'currency',
        'proof_image_path',
        'transaction_code',
        'date_paid',
        'items',
        'admin_note',
        'payer_first_name',
        'payer_last_name',
        'payer_email',
        'certification_block_id',
    ];

    protected $casts = [
        'date_paid' => 'datetime',
        'items' => 'array',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function certificationBlock()
    {
        return $this->belongsTo(CertificationBlock::class);
    }
}

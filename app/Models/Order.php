<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'tenant_id',
        'order_no',
        'customer_name',
        'customer_contact',
        'subtotal',
        'tax_amount',
        'total_amount',
        'discount_amount',
        'status',
        'payment_method',
        'payment_received',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'payment_received' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNo($tenantId): string
    {
        $date = now()->format('Ymd');
        $count = static::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count() + 1;
        
        return 'ORD-' . $date . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}

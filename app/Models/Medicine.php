<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasUuids;

    protected $fillable = [
        'category_id',
        'dosage_form_id',
        'trade_name',
        'scientific_name',
        'barcode',
        'manufacturer',
        'item_type',
        'dispensing_method',
        'min_stock_alert',
        'is_taxable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_stock_alert' => 'integer',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function dosageForm(): BelongsTo
    {
        return $this->belongsTo(DosageForm::class);
    }

    public function medicineUnits(): HasMany
    {
        return $this->hasMany(MedicineUnit::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'medicine_units')
            ->withPivot([
                'conversion_factor',
                'selling_price',
                'unit_barcode',
                'is_base_unit',
                'is_purchasable',
                'is_sellable',
            ])
            ->withTimestamps();
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'medicine_accessories', 'medicine_id', 'accessory_id')
            ->withPivot(['quantity', 'is_free_with_parent', 'is_required'])
            ->withTimestamps();
    }

    public function accessoryOf(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'medicine_accessories', 'accessory_id', 'medicine_id')
            ->withPivot(['quantity', 'is_free_with_parent', 'is_required'])
            ->withTimestamps();
    }

    public function medicineAccessories(): HasMany
    {
        return $this->hasMany(MedicineAccessory::class);
    }

    public function accessoryLinks(): HasMany
    {
        return $this->hasMany(MedicineAccessory::class, 'accessory_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(Sale::class, 'sale_items')
            ->withPivot([
                'unit_id',
                'batch_id',
                'quantity',
                'base_quantity_deducted',
                'unit_price',
                'landed_cost_at_sale',
                'discount_amount',
                'tax_amount',
                'line_total',
                'is_accessory_free',
                'free_source',
                'insurance_covered_amount',
                'patient_amount',
            ])
            ->withTimestamps();
    }

    public function purchases(): BelongsToMany
    {
        return $this->belongsToMany(Purchase::class, 'purchase_items')
            ->withPivot([
                'unit_id',
                'batch_id',
                'batch_number',
                'expiry_date',
                'quantity',
                'bonus_quantity',
                'total_base_quantity',
                'unit_purchase_price',
                'tax_amount',
                'line_total',
            ])
            ->withTimestamps();
    }

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function purchaseReturns(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseReturn::class, 'purchase_return_items')
            ->withPivot([
                'purchase_item_id',
                'unit_id',
                'batch_id',
                'returned_quantity',
                'base_quantity_deducted',
                'unit_purchase_price',
                'tax_return_amount',
                'line_total',
            ])
            ->withTimestamps();
    }

    public function saleReturns(): BelongsToMany
    {
        return $this->belongsToMany(SaleReturn::class, 'sale_return_items')
            ->withPivot([
                'sale_item_id',
                'unit_id',
                'batch_id',
                'returned_quantity',
                'base_quantity_restored',
                'unit_price_at_sale',
                'tax_refund_amount',
                'line_total',
            ])
            ->withTimestamps();
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnail,

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand?->id,
                    'name' => $this->brand?->name,
                    'slug' => $this->brand?->slug,
                ];
            }),
            'price' => $this->whenLoaded('cheapestVariant', function () {
                return $this->cheapestVariant->price;
            }, 0),

            'compare_at_price' => $this->whenLoaded('cheapestVariant', function () {
                if ($this->cheapestVariant->compare_at_price !== null) {
                    return (float) $this->cheapestVariant->compare_at_price;
                }
                return $this->cheapestVariant->price > 0 ? round($this->cheapestVariant->price / 0.9, 2) : null;
            }),

            'product_variant_id' => $this->whenLoaded('cheapestVariant', function () {
                return $this->cheapestVariant?->id;
            }),
        ];
    }

}

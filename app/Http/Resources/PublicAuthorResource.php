<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicAuthorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'designation' => $this->designation,
            'bio_bn' => $this->bio_bn,
            'bio_en' => $this->bio_en,
            'photo' => $this->photo,
            'email' => $this->email,
            'facebook_url' => $this->facebook_url,
            'x_url' => $this->x_url,
            'linkedin_url' => $this->linkedin_url,
            'website_url' => $this->website_url,
            'featured' => $this->featured,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }
}

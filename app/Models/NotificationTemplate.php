<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'trigger_key',
        'title',
        'message_template',
        'channels',
        'is_active',
    ];

    /**
     * Konversi tipe data atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mengisi placeholder variabel pada isi pesan template.
     *
     * @param  array<string, string>  $variables
     */
    public function renderMessage(array $variables): string
    {
        $message = $this->message_template;
        foreach ($variables as $key => $value) {
            $message = str_replace('{'.$key.'}', $value, $message);
        }

        return $message;
    }
}

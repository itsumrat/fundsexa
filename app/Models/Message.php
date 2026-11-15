<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'user_id', 'sender', 'body', 'attachment', 'attachment_name', 'seen', 'seen_by_admin',
    ];

    protected $casts = [
        'seen'          => 'boolean',
        'seen_by_admin' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function isFromAdmin(): bool
    {
        return $this->sender === 'admin';
    }

    // Returns array of [path => name] pairs
    public function attachmentFiles(): array
    {
        if (!$this->attachment) return [];
        $paths = json_decode($this->attachment, true) ?? [$this->attachment];
        $names = json_decode($this->attachment_name, true) ?? [$this->attachment_name];
        return array_combine($paths, $names);
    }

    // Shape sent to the chat UI (initial page load + Pusher events)
    public function toChatArray(): array
    {
        $files = [];
        foreach ($this->attachmentFiles() as $path => $name) {
            $ext = strtolower(pathinfo($name ?: $path, PATHINFO_EXTENSION));
            $files[] = [
                'url'      => asset('storage/' . $path),
                'name'     => $name,
                'ext'      => $ext,
                'is_image' => in_array($ext, self::IMAGE_TYPES, true),
                'size'     => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
            ];
        }

        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'sender'        => $this->sender,
            'body'          => $this->body,
            'attachments'   => $files,
            'seen'          => $this->seen,
            'seen_by_admin' => $this->seen_by_admin,
            'created_at'    => $this->created_at->toISOString(),
        ];
    }

    // Allowed chat attachments (images show as thumbnails, the rest as file cards)
    public const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const FILE_TYPES  = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
    public const MAX_FILES   = 3;
    public const MAX_KB      = 10240; // 10 MB per file

    // Validation shared by admin + customer chat: text and/or up to 3 attachments
    public static function rules(): array
    {
        return [
            'body'    => 'nullable|string|max:2000|required_without:files',
            'files'   => 'nullable|array|max:' . self::MAX_FILES,
            'files.*' => 'file|max:' . self::MAX_KB . '|mimes:' . implode(',', [...self::IMAGE_TYPES, ...self::FILE_TYPES]),
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'body.required_without' => 'Write a message or attach a file.',
            'files.max'             => 'You can attach up to ' . self::MAX_FILES . ' files per message.',
            'files.*.mimes'         => 'Allowed files: images (JPG, PNG, GIF, WEBP), PDF, Word, Excel, CSV and TXT.',
            'files.*.max'           => 'Each file must be 10 MB or smaller.',
        ];
    }

    // Stores uploaded attachments and returns the JSON columns for create()
    public static function storeFiles(?array $files): array
    {
        $paths = $names = [];
        foreach ($files ?? [] as $file) {
            $names[] = $file->getClientOriginalName();
            $paths[] = $file->store('messages', 'public');
        }
        return [
            'attachment'      => $paths ? json_encode($paths) : null,
            'attachment_name' => $names ? json_encode($names) : null,
        ];
    }

    // Admin → customer messages the customer hasn't opened yet
    public static function unseenCount(int $userId): int
    {
        return static::where('user_id', $userId)->where('sender', 'admin')->where('seen', false)->count();
    }

    // Customer → admin messages no admin has opened yet (all customers, or one)
    public static function unseenByAdminCount(?int $userId = null): int
    {
        return static::where('sender', 'customer')->where('seen_by_admin', false)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->count();
    }
}

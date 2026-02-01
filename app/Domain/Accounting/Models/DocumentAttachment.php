<?php

namespace App\Domain\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class DocumentAttachment extends Model
{
    protected $table = 'document_attachments';
    
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'filename',
        'original_filename',
        'mime_type',
        'file_size',
        'disk',
        'path',
        'description',
        'uploaded_by',
    ];
    
    protected $casts = [
        'file_size' => 'integer',
    ];
    
    // Relationships
    
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    // Computed Attributes
    
    /**
     * Get full URL to the file
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
    
    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }
    
    /**
     * Check if file is an image
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
    
    /**
     * Check if file is a PDF
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
    
    // Methods
    
    /**
     * Delete the file from storage
     */
    public function deleteFile(): bool
    {
        return Storage::disk($this->disk)->delete($this->path);
    }
    
    /**
     * Delete record and file
     */
    public function deleteWithFile(): bool
    {
        $this->deleteFile();
        return $this->delete();
    }
}

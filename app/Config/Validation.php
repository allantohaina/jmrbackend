<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Image upload validation.
     * Input name: file
     */
    public array $uploadImage = [
        'file' => 'uploaded[file]|max_size[file,5120]|ext_in[file,jpg,jpeg,png,webp,avif,heic,heif]|mime_in[file,image/jpg,image/jpeg,image/png,image/webp,image/avif,image/heic,image/heif]|is_image[file]',
    ];

    public array $uploadImage_errors = [
        'file' => [
            'uploaded' => 'Validation.upload.image.uploaded',
            'max_size' => 'Validation.upload.image.max_size',
            'ext_in' => 'Validation.upload.image.ext_in',
            'mime_in' => 'Validation.upload.image.mime_in',
            'is_image' => 'Validation.upload.image.is_image',
        ],
    ];

    public array $uploadImageAlt = [
        'image' => 'uploaded[image]|max_size[image,5120]|ext_in[image,jpg,jpeg,png,webp,avif,heic,heif]|mime_in[image,image/jpg,image/jpeg,image/png,image/webp,image/avif,image/heic,image/heif]|is_image[image]',
    ];

    public array $uploadImageAlt_errors = [
        'image' => [
            'uploaded' => 'Validation.upload.image.uploaded',
            'max_size' => 'Validation.upload.image.max_size',
            'ext_in' => 'Validation.upload.image.ext_in',
            'mime_in' => 'Validation.upload.image.mime_in',
            'is_image' => 'Validation.upload.image.is_image',
        ],
    ];

    /**
     * Document upload validation.
     * Input name: file
     */
    public array $uploadDocument = [
        'file' => 'uploaded[file]|max_size[file,10240]|ext_in[file,pdf,doc,docx,xls,xlsx,csv,txt]|mime_in[file,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain]',
    ];

    public array $uploadDocument_errors = [
        'file' => [
            'uploaded' => 'Validation.upload.document.uploaded',
            'max_size' => 'Validation.upload.document.max_size',
            'ext_in' => 'Validation.upload.document.ext_in',
            'mime_in' => 'Validation.upload.document.mime_in',
        ],
    ];
}

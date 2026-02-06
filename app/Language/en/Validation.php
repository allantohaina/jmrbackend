<?php

return [
    'upload' => [
        'image' => [
            'uploaded' => 'Image is required.',
            'max_size' => 'Image too large (max 5 MB).',
            'ext_in' => 'Image format not allowed.',
            'mime_in' => 'Image MIME type not allowed.',
            'is_image' => 'File must be a valid image.',
        ],
        'document' => [
            'uploaded' => 'Document is required.',
            'max_size' => 'Document too large (max 10 MB).',
            'ext_in' => 'Document format not allowed.',
            'mime_in' => 'Document MIME type not allowed.',
        ],
    ],
];

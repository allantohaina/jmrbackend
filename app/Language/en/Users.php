<?php

return [
    'register' => [
        'success' => 'User created successfully',
    ],
    'login' => [
        'success' => 'Login successful',
        'locked' => 'Account temporarily locked',
        'invalid' => 'Incorrect email or password',
        'required' => 'Email and password required',
    ],
    'profile' => [
        'updated' => 'Profile updated successfully',
        'deleted' => 'Account deleted successfully',
    ],
    'admin' => [
        'updated' => 'User updated successfully',
        'deleted' => 'User deleted successfully',
    ],
    'errors' => [
        'not_found' => 'User not found',
        'delete_account' => 'Error deleting account',
        'delete_user' => 'Error deleting user',
        'required_fields' => 'Required fields missing',
    ],
    'refresh' => [
        'required' => 'Refresh token required',
        'invalid' => 'Invalid refresh token',
        'expired' => 'Refresh token expired',
    ],
    'logout' => [
        'success' => 'Logout successful',
    ],
    'validation' => [
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Email must be valid',
            'is_unique' => 'This email is already used',
        ],
        'password' => [
            'required' => 'Password is required',
            'min_length' => 'Password must be at least 8 characters',
        ],
        'first_name' => [
            'required' => 'First name is required',
            'min_length' => 'First name must be at least 2 characters',
        ],
        'last_name' => [
            'required' => 'Last name is required',
            'min_length' => 'Last name must be at least 2 characters',
        ],
    ],
];

<?php

return [
    'management_title' => 'Stock movements',
    'placeholder' => 'Choose an option',

    'stock' => 'Stock',
    'type' => 'Movement type',
    'quantity' => 'Quantity',
    'quantity_changed' => 'Changed',
    'quantity_after' => 'After movement',
    'note' => 'Note',
    'created_at' => 'Created at',
    'action' => 'Action',

    'types' => [
        'in' => 'Stock in',
        'out' => 'Stock out',
        'adjustment' => 'Adjustment',
    ],

    'create_title' => 'Create stock movement',
    'create_subtitle' => 'Record a stock in, stock out, or adjustment transaction.',
    'save_create' => 'Save movement',
    'stock_placeholder' => 'Choose stock',
    'note_placeholder' => 'Enter note if any',

    'stock_required' => 'Please choose stock.',
    'type_required' => 'Please choose a movement type.',
    'quantity_required' => 'Please enter quantity.',
    'quantity_positive_required' => 'Stock in or stock out quantity must be greater than 0.',

    'create_success' => 'Stock movement recorded successfully!',
    'delete_success' => 'Stock movement deleted.',
    'restore_success' => 'Stock movement restored successfully.',
    'restore_error' => 'System error, unable to restore stock movement.',
    'system_error' => 'System error',

    'confirm_delete' => 'Confirm deleting this stock movement?',
    'undo' => 'Undo',
    'save_loading' => 'Saving...',
    'code_prefix' => 'Error code:',

    'success_title' => 'Success',
    'delete_toast_title' => 'Movement deleted',
    'delete_description' => 'The movement has been moved to trash.',
    'undo_success_title' => 'Undo successful',
    'undo_success_description' => 'The movement has been restored.',
    'restore_error_title' => 'Restore failed',
    'restore_error_description' => 'Unable to undo this action.',
    'generic_error_title' => 'Something went wrong!',
    'generic_error_description' => 'Please try again later',
    'process_failed_title' => 'Process failed',
    'process_failed_description' => 'Invalid data. Please check your inputs.',
    'system_error_title' => 'System error',
    'system_error_description' => 'A system error has occurred!',

    'action_labels' => [
        'delete' => 'Delete movement',
    ],
];

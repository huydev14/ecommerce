<?php

return [
    'management_title' => 'Stocks',
    'placeholder' => 'Choose an option',

    'variant' => 'Product variant',
    'warehouse' => 'Warehouse',
    'quantity' => 'Quantity',
    'reserved_quantity' => 'Reserved',
    'available_quantity' => 'Available',
    'low_stock_threshold' => 'Low stock threshold',
    'updated_at' => 'Updated at',
    'action' => 'Action',

    'create_title' => 'Create stock',
    'create_subtitle' => 'Set initial stock for a product variant in a warehouse.',
    'edit_title' => 'Update stock',
    'edit_subtitle' => 'Adjust quantity, reserved quantity, and alert threshold.',
    'save_create' => 'Save stock',
    'save_edit' => 'Save changes',

    'variant_placeholder' => 'Choose product variant',
    'warehouse_placeholder' => 'Choose warehouse',

    'variant_required' => 'Please choose a product variant.',
    'warehouse_required' => 'Please choose a warehouse.',
    'unique_variant_warehouse' => 'This variant already has stock in the selected warehouse.',
    'reserved_lte_quantity' => 'Reserved quantity may not be greater than stock quantity.',
    'quantity_after_negative' => 'Stock quantity after movement may not be negative.',
    'adjustment_note' => 'Direct adjustment from stock screen',

    'create_success' => 'Stock created successfully!',
    'update_success' => 'Stock updated successfully!',
    'delete_success' => 'Stock deleted.',
    'restore_success' => 'Stock restored successfully.',
    'restore_error' => 'System error, unable to restore stock.',
    'system_error' => 'System error',

    'confirm_delete' => 'Confirm deleting this stock?',
    'undo' => 'Undo',
    'save_loading' => 'Saving...',
    'code_prefix' => 'Error code:',

    'success_title' => 'Success',
    'delete_toast_title' => 'Stock deleted',
    'delete_description' => 'The stock has been moved to trash.',
    'undo_success_title' => 'Undo successful',
    'undo_success_description' => 'The stock has been restored.',
    'restore_error_title' => 'Restore failed',
    'restore_error_description' => 'Unable to undo this action.',
    'generic_error_title' => 'Something went wrong!',
    'generic_error_description' => 'Please try again later',
    'process_failed_title' => 'Process failed',
    'process_failed_description' => 'Invalid data. Please check your inputs.',
    'system_error_title' => 'System error',
    'system_error_description' => 'A system error has occurred!',

    'action_labels' => [
        'edit' => 'Edit',
        'delete' => 'Delete stock',
    ],
];

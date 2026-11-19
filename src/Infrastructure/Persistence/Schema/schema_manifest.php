<?php

declare(strict_types=1);

return array (
  'account' => 
  array (
    'category' => 'core',
    'label' => 'Accounts & Workspaces',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'google_api_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'api_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'business_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'state' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'first_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'last_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'website' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'biography' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'logo' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'contact_number' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(16)',
        'phpType' => 'string',
        'size' => 16,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'address' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'subscription' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => '',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'max_users' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(5) unsigned',
        'phpType' => 'integer',
        'size' => 5,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => true,
      ),
      'social_media' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'social_posting' => 
      array (
        'type' => 'tinyint',
        'dbType' => 'tinyint(1)',
        'phpType' => 'integer',
        'size' => 1,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'linked' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'google_linked' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'facebook_update_all' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 1,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'activation_key' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email_sender' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_follow_up' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'qr_code' => 
      array (
        'type' => 'tinyint',
        'dbType' => 'tinyint(3) unsigned',
        'phpType' => 'integer',
        'size' => 3,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => true,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'nav_mode' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 'sidebar',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-account-api' => 
      array (
        'columns' => 
        array (
          0 => 'api_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-acc-linked' => 
      array (
        'columns' => 
        array (
          0 => 'linked',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'acc-lk' => 
      array (
        'columns' => 
        array (
          0 => 'activation_key',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-acc-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-acc-social' => 
      array (
        'columns' => 
        array (
          0 => 'social_media',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'facebook_update_all' => 
      array (
        'columns' => 
        array (
          0 => 'facebook_update_all',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-gacc-linked' => 
      array (
        'columns' => 
        array (
          0 => 'google_linked',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-account-gapi' => 
      array (
        'columns' => 
        array (
          0 => 'google_api_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-account-api' => 
      array (
        0 => 'api',
        'api_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-account-gapi' => 
      array (
        0 => 'api',
        'google_api_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'account_api' => 
  array (
    'category' => 'social_connection',
    'label' => 'Account API Credentials (Tenant)',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'account_api_id',
    ),
    'columns' => 
    array (
      'account_api_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'api_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'linked' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'token' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'update_all' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'linked' => 
      array (
        'columns' => 
        array (
          0 => 'linked',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'api_id' => 
      array (
        'columns' => 
        array (
          0 => 'api_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'account_id' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'account_api_id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'update_all' => 
      array (
        'columns' => 
        array (
          0 => 'update_all',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'account_api_ibfk_1' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
      'account_api_ibfk_2' => 
      array (
        0 => 'api',
        'api_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'account_invoice' => 
  array (
    'category' => 'billing',
    'label' => 'Invoices & Billing History',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_customer_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_subscription_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_invoice_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'invoice_number' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'amount_paid' => 
      array (
        'type' => 'decimal',
        'dbType' => 'decimal(10,2)',
        'phpType' => 'double',
        'size' => 10,
        'scale' => 2,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'amount_cents' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'currency' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(10)',
        'phpType' => 'string',
        'size' => 10,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 'usd',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(50)',
        'phpType' => 'string',
        'size' => 50,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 'paid',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'billing_reason' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(100)',
        'phpType' => 'string',
        'size' => 100,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'invoice_pdf_url' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'hosted_invoice_url' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'period_start' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'period_end' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'paid_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-account_invoice-user_id' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-account_invoice-account_id' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-account_invoice-stripe_invoice_id' => 
      array (
        'columns' => 
        array (
          0 => 'stripe_invoice_id',
        ),
        'isPrimary' => false,
        'isUnique' => true,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-account_invoice-stripe_subscription_id' => 
      array (
        'columns' => 
        array (
          0 => 'stripe_subscription_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-account_invoice-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'api' => 
  array (
    'category' => 'social_connection',
    'label' => 'Platform API Credentials (Global)',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'service_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'label' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'api_key' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'client_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'token' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'client_json' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-api-service' => 
      array (
        'columns' => 
        array (
          0 => 'service_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-api-service' => 
      array (
        0 => 'service',
        'service_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'auth_assignment' => 
  array (
    'category' => 'core',
    'label' => 'RBAC User Role Assignments',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'item_name',
      1 => 'user_id',
    ),
    'columns' => 
    array (
      'item_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-auth_assignment-user_id' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'item_name',
          1 => 'user_id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'auth_assignment_ibfk_1' => 
      array (
        0 => 'auth_item',
        'item_name' => 'name',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'auth_item' => 
  array (
    'category' => 'core',
    'label' => 'RBAC Items & Permissions',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'name',
    ),
    'columns' => 
    array (
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'type' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'description' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'rule_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'data' => 
      array (
        'type' => 'binary',
        'dbType' => 'blob',
        'phpType' => 'resource',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-auth_item-type' => 
      array (
        'columns' => 
        array (
          0 => 'type',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'rule_name' => 
      array (
        'columns' => 
        array (
          0 => 'rule_name',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'name',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'auth_item_ibfk_1' => 
      array (
        0 => 'auth_rule',
        'rule_name' => 'name',
        'onDelete' => 'SET NULL',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'auth_item_child' => 
  array (
    'category' => 'core',
    'label' => 'RBAC Item Hierarchy',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'parent',
      1 => 'child',
    ),
    'columns' => 
    array (
      'parent' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'child' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'child' => 
      array (
        'columns' => 
        array (
          0 => 'child',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'parent',
          1 => 'child',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'auth_item_child_ibfk_1' => 
      array (
        0 => 'auth_item',
        'parent' => 'name',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
      'auth_item_child_ibfk_2' => 
      array (
        0 => 'auth_item',
        'child' => 'name',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'auth_rule' => 
  array (
    'category' => 'core',
    'label' => 'RBAC Rules',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'name',
    ),
    'columns' => 
    array (
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'data' => 
      array (
        'type' => 'binary',
        'dbType' => 'blob',
        'phpType' => 'resource',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'name',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'chat' => 
  array (
    'category' => 'chat',
    'label' => 'Conversations & Social Feeds',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'start_date' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'read' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-chat-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chat-read' => 
      array (
        'columns' => 
        array (
          0 => 'read',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chat_date' => 
      array (
        'columns' => 
        array (
          0 => 'start_date',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chat_cust' => 
      array (
        'columns' => 
        array (
          0 => 'customer_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chat_cfg' => 
      array (
        'columns' => 
        array (
          0 => 'service_config_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-chat-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-chat_cfg' => 
      array (
        0 => 'service_config',
        'service_config_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'chat_message' => 
  array (
    'category' => 'chat',
    'label' => 'Chat & Feed Messages',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'chat_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(10)',
        'phpType' => 'string',
        'size' => 10,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'obj_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'chat_text' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'chat_extra' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'chat_date' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-chatm_chatm_date' => 
      array (
        'columns' => 
        array (
          0 => 'chat_date',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chatm_customer_name' => 
      array (
        'columns' => 
        array (
          0 => 'customer_name',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chatm_customer_id' => 
      array (
        'columns' => 
        array (
          0 => 'customer_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chatm_obj_id' => 
      array (
        'columns' => 
        array (
          0 => 'obj_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chatm_chat_id' => 
      array (
        'columns' => 
        array (
          0 => 'chat_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chat_message-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chatm_cfg' => 
      array (
        'columns' => 
        array (
          0 => 'service_config_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-chatm-user' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-chat_message-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-chatm-user' => 
      array (
        0 => 'user',
        'user_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-chatm_cfg' => 
      array (
        0 => 'service_config',
        'service_config_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-chatm_chat_id' => 
      array (
        0 => 'chat',
        'chat_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'email_addresses' => 
  array (
    'category' => 'email',
    'label' => 'Email Contact Directory',
    'required' => false,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'type' => 
      array (
        'type' => 'string',
        'dbType' => 'enum(\'to\',\'cc\',\'bcc\')',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 'to',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-ea-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-ea-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-ea-email' => 
      array (
        'columns' => 
        array (
          0 => 'email',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-ea-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'email_queue' => 
  array (
    'category' => 'email',
    'label' => 'Email Outbound Queue',
    'required' => false,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email_template_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'ref' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'bcc' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'subject' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'body' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'send_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'sent_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'locked_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'external_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(300)',
        'phpType' => 'string',
        'size' => 300,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-eq-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-ref' => 
      array (
        'columns' => 
        array (
          0 => 'ref',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-email' => 
      array (
        'columns' => 
        array (
          0 => 'email',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-locked_at' => 
      array (
        'columns' => 
        array (
          0 => 'locked_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-name' => 
      array (
        'columns' => 
        array (
          0 => 'name',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx--eq-ext-id' => 
      array (
        'columns' => 
        array (
          0 => 'external_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-et' => 
      array (
        'columns' => 
        array (
          0 => 'email_template_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-sent_at' => 
      array (
        'columns' => 
        array (
          0 => 'sent_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-acc' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-eq-send_at' => 
      array (
        'columns' => 
        array (
          0 => 'send_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-eq-acc' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-eq-et' => 
      array (
        0 => 'email_template',
        'email_template_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'email_template' => 
  array (
    'category' => 'email',
    'label' => 'Email Campaign Templates',
    'required' => false,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'slug' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'text' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'protected' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-et-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-et-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-et-slug' => 
      array (
        'columns' => 
        array (
          0 => 'slug',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-et-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'location_group' => 
  array (
    'category' => 'location_group',
    'label' => 'Location Groups',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'description' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'tinyint',
        'dbType' => 'tinyint(1)',
        'phpType' => 'integer',
        'size' => 1,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 1,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-location_group-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-location_group-account_id' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-location_group-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'location_group_account' => 
  array (
    'category' => 'location_group',
    'label' => 'Location Group Account Links',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'group_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-lga-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-lga-group' => 
      array (
        'columns' => 
        array (
          0 => 'group_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-lga-unique' => 
      array (
        'columns' => 
        array (
          0 => 'group_id',
          1 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-lga-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
      'fk-lga-group' => 
      array (
        0 => 'location_group',
        'group_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'location_group_service_config' => 
  array (
    'category' => 'location_group',
    'label' => 'Location Group Platform Configs',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'group_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'sub_service_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-lgsc-sub_service_id' => 
      array (
        'columns' => 
        array (
          0 => 'sub_service_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-lgsc-service_config_id' => 
      array (
        'columns' => 
        array (
          0 => 'service_config_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-lgsc-group_id' => 
      array (
        'columns' => 
        array (
          0 => 'group_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-lgsc-group-config-sub' => 
      array (
        'columns' => 
        array (
          0 => 'group_id',
          1 => 'service_config_id',
          2 => 'sub_service_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-lgsc-group' => 
      array (
        0 => 'location_group',
        'group_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
      'fk-lgsc-service_config' => 
      array (
        0 => 'service_config',
        'service_config_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
      'fk-lgsc-sub_service' => 
      array (
        0 => 'sub_service',
        'sub_service_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'plans' => 
  array (
    'category' => 'billing',
    'label' => 'Subscription Plans',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'plan_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_price' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_limit' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_features' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'qrcode' => 
  array (
    'category' => 'qrcode',
    'label' => 'QR Code Generators',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'upload_logo' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'logo_type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'qrcode' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'url' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'template_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'default_logo_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'frame_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'total_scan' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 'active',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'qrcode_scan' => 
  array (
    'category' => 'qrcode',
    'label' => 'QR Code Scans & Tracking',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'qrcode_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'user_ip' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'review' => 
  array (
    'category' => 'reviews',
    'label' => 'Customer Reviews',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'sub_service_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'obj_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 'REVIEW',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_email' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_phone' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(30)',
        'phpType' => 'string',
        'size' => 30,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_state' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_region' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_rate' => 
      array (
        'type' => 'decimal',
        'dbType' => 'decimal(3,1)',
        'phpType' => 'double',
        'size' => 3,
        'scale' => 1,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_text' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_recommend' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_service' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_date' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_extra' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'read' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'spam_score' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'spam_status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 'normal',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-review_date' => 
      array (
        'columns' => 
        array (
          0 => 'review_date',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review_rate' => 
      array (
        'columns' => 
        array (
          0 => 'review_rate',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-customer_name' => 
      array (
        'columns' => 
        array (
          0 => 'customer_name',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review-email' => 
      array (
        'columns' => 
        array (
          0 => 'customer_email',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review_customer_id' => 
      array (
        'columns' => 
        array (
          0 => 'customer_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-rev-subs' => 
      array (
        'columns' => 
        array (
          0 => 'sub_service_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review_obj_id' => 
      array (
        'columns' => 
        array (
          0 => 'obj_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-rev-type' => 
      array (
        'columns' => 
        array (
          0 => 'type',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review_cfg' => 
      array (
        'columns' => 
        array (
          0 => 'service_config_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-rev-subs' => 
      array (
        0 => 'sub_service',
        'sub_service_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-review-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-review_cfg' => 
      array (
        0 => 'service_config',
        'service_config_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'review_reply' => 
  array (
    'category' => 'reviews',
    'label' => 'Review Responses & Replies',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'review_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'obj_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'reply_text' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'reply_date' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'reply_extra' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-rr_customer_id' => 
      array (
        'columns' => 
        array (
          0 => 'customer_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-reply_date' => 
      array (
        'columns' => 
        array (
          0 => 'reply_date',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-reply_obj_id' => 
      array (
        'columns' => 
        array (
          0 => 'obj_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-reply_review' => 
      array (
        'columns' => 
        array (
          0 => 'review_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-reply_user' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-review_reply-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-reply_cfg' => 
      array (
        'columns' => 
        array (
          0 => 'service_config_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-rr-customer_name' => 
      array (
        'columns' => 
        array (
          0 => 'customer_name',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-reply_cfg' => 
      array (
        0 => 'service_config',
        'service_config_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-reply_review' => 
      array (
        0 => 'review',
        'review_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-reply_user' => 
      array (
        0 => 'user',
        'user_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-review_reply-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'service' => 
  array (
    'category' => 'social_connection',
    'label' => 'Platform Service Providers',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'label' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'class' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-label' => 
      array (
        'columns' => 
        array (
          0 => 'label',
        ),
        'isPrimary' => false,
        'isUnique' => true,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'service_config' => 
  array (
    'category' => 'social_connection',
    'label' => 'Connected Social Accounts & Locations',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'api_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'label' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'api_key' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'client_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'token' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'page_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'google_place_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'google_maps_uri' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'page_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'page_token' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'linked' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => 1,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email_low_review' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-service_config-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx_service_config_google_place_id' => 
      array (
        'columns' => 
        array (
          0 => 'google_place_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-cfg-service' => 
      array (
        'columns' => 
        array (
          0 => 'api_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-cfg_service-service' => 
      array (
        'columns' => 
        array (
          0 => 'service_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-cfg-service' => 
      array (
        0 => 'api',
        'api_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-cfg_service-service' => 
      array (
        0 => 'service',
        'service_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-service_config-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'session' => 
  array (
    'category' => 'core',
    'label' => 'HTTP Sessions',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'char',
        'dbType' => 'char(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'expire' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'data' => 
      array (
        'type' => 'binary',
        'dbType' => 'longblob',
        'phpType' => 'resource',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'social_post' => 
  array (
    'category' => 'social_post',
    'label' => 'Social Posts (Studio)',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'content' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'media_type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 'none',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'media_url' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(500)',
        'phpType' => 'string',
        'size' => 500,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'post_type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 'immediate',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'scheduled_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-social_post-user_id' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-social_post-account_id' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'social_post_destination' => 
  array (
    'category' => 'social_post',
    'label' => 'Social Post Publishing Destinations',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'social_post_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'platform' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'sub_service_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(20)',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 'queued',
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'scheduled_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'published_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'remote_post_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'error_message' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'retry_count' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'locked_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-social_post_destination-account_id' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-social_post_destination-post_id' => 
      array (
        'columns' => 
        array (
          0 => 'social_post_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-social_post_destination-status-scheduled' => 
      array (
        'columns' => 
        array (
          0 => 'status',
          1 => 'scheduled_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'sub_service' => 
  array (
    'category' => 'social_connection',
    'label' => 'Service Sub-Categories',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'service_config_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(15)',
        'phpType' => 'string',
        'size' => 15,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'obj_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'token' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'extra' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-subs-type' => 
      array (
        'columns' => 
        array (
          0 => 'type',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-subs-obj_id' => 
      array (
        'columns' => 
        array (
          0 => 'obj_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-subs-sc' => 
      array (
        'columns' => 
        array (
          0 => 'service_config_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-subs-acc' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-subs-acc' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-subs-sc' => 
      array (
        0 => 'service_config',
        'service_config_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'subscriptions' => 
  array (
    'category' => 'billing',
    'label' => 'Account Subscriptions',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'bigint',
        'dbType' => 'bigint(20) unsigned',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => true,
      ),
      'user_id' => 
      array (
        'type' => 'bigint',
        'dbType' => 'bigint(20) unsigned',
        'phpType' => 'string',
        'size' => 20,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => true,
      ),
      'plan_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_customer_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_plan_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_subscription_id' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'default_payment_method' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'default_source' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'paid_amount' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'paid_amount_currency' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_interval' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_interval_count' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'customer_email' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_period_start' => 
      array (
        'type' => 'timestamp',
        'dbType' => 'timestamp',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'plan_period_end' => 
      array (
        'type' => 'timestamp',
        'dbType' => 'timestamp',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'stripe_status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'expire_at' => 
      array (
        'type' => 'timestamp',
        'dbType' => 'timestamp',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'system_setting' => 
  array (
    'category' => 'core',
    'label' => 'System Settings Store',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'key',
    ),
    'columns' => 
    array (
      'key' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'value' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      '' => 
      array (
        'columns' => 
        array (
          0 => 'key',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'user' => 
  array (
    'category' => 'core',
    'label' => 'Users & Staff',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'owner_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'email' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'auth_key' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(32)',
        'phpType' => 'string',
        'size' => 32,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'password_hash' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'password_reset_token' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'account_activation_token' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-user-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'password_reset_token' => 
      array (
        'columns' => 
        array (
          0 => 'password_reset_token',
        ),
        'isPrimary' => false,
        'isUnique' => true,
      ),
      'idx-email' => 
      array (
        'columns' => 
        array (
          0 => 'email',
        ),
        'isPrimary' => false,
        'isUnique' => true,
      ),
      'email' => 
      array (
        'columns' => 
        array (
          0 => 'email',
        ),
        'isPrimary' => false,
        'isUnique' => true,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-user-owner' => 
      array (
        'columns' => 
        array (
          0 => 'owner_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-user-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
      'fk-user-owner' => 
      array (
        0 => 'user',
        'owner_id' => 'id',
        'onDelete' => 'RESTRICT',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'user_notification' => 
  array (
    'category' => 'notifications',
    'label' => 'User Notifications',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'type' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(64)',
        'phpType' => 'string',
        'size' => 64,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'title' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'message' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'action_url' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(500)',
        'phpType' => 'string',
        'size' => 500,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'is_read' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'read_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-user_notification-user_unread' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
          1 => 'is_read',
          2 => 'created_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-user_notification-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
          1 => 'created_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
    ),
  ),
  'user_token' => 
  array (
    'category' => 'core',
    'label' => 'User Authentication Tokens',
    'required' => true,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'user_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'token' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'expires_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-user_token-user' => 
      array (
        'columns' => 
        array (
          0 => 'user_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-user_token-user' => 
      array (
        0 => 'user',
        'user_id' => 'id',
        'onDelete' => 'CASCADE',
        'onUpdate' => 'RESTRICT',
      ),
    ),
  ),
  'whatsapp_addresses' => 
  array (
    'category' => 'whatsapp',
    'label' => 'WhatsApp Contact Directory',
    'required' => false,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'phone' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-wa-addr-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-wa-addr-phone' => 
      array (
        'columns' => 
        array (
          0 => 'phone',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-wa-addr-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-wa-addr-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'SET NULL',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'whatsapp_queue' => 
  array (
    'category' => 'whatsapp',
    'label' => 'WhatsApp Outbound Queue',
    'required' => false,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'phone' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'message' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'send_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'sent_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'locked_at' => 
      array (
        'type' => 'datetime',
        'dbType' => 'datetime',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'provider_message_id' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-waq-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-waq-phone' => 
      array (
        'columns' => 
        array (
          0 => 'phone',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-waq-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
      'idx-waq-locked_at' => 
      array (
        'columns' => 
        array (
          0 => 'locked_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-waq-sent_at' => 
      array (
        'columns' => 
        array (
          0 => 'sent_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-waq-send_at' => 
      array (
        'columns' => 
        array (
          0 => 'send_at',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-waq-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'SET NULL',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
  'whatsapp_template' => 
  array (
    'category' => 'whatsapp',
    'label' => 'WhatsApp Cloud Templates',
    'required' => false,
    'primaryKey' => 
    array (
      0 => 'id',
    ),
    'columns' => 
    array (
      'id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => true,
        'unsigned' => false,
      ),
      'account_id' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'slug' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'name' => 
      array (
        'type' => 'string',
        'dbType' => 'varchar(255)',
        'phpType' => 'string',
        'size' => 255,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'text' => 
      array (
        'type' => 'text',
        'dbType' => 'text',
        'phpType' => 'string',
        'size' => NULL,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'protected' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => 0,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'status' => 
      array (
        'type' => 'smallint',
        'dbType' => 'smallint(6)',
        'phpType' => 'integer',
        'size' => 6,
        'scale' => NULL,
        'allowNull' => false,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'created_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
      'updated_at' => 
      array (
        'type' => 'integer',
        'dbType' => 'int(11)',
        'phpType' => 'integer',
        'size' => 11,
        'scale' => NULL,
        'allowNull' => true,
        'defaultValue' => NULL,
        'autoIncrement' => false,
        'unsigned' => false,
      ),
    ),
    'indexes' => 
    array (
      'idx-wa-tpl-account' => 
      array (
        'columns' => 
        array (
          0 => 'account_id',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-wa-tpl-status' => 
      array (
        'columns' => 
        array (
          0 => 'status',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      'idx-wa-tpl-slug' => 
      array (
        'columns' => 
        array (
          0 => 'slug',
        ),
        'isPrimary' => false,
        'isUnique' => false,
      ),
      '' => 
      array (
        'columns' => 
        array (
          0 => 'id',
        ),
        'isPrimary' => true,
        'isUnique' => true,
      ),
    ),
    'foreignKeys' => 
    array (
      'fk-wa-tpl-account' => 
      array (
        0 => 'account',
        'account_id' => 'id',
        'onDelete' => 'SET NULL',
        'onUpdate' => 'CASCADE',
      ),
    ),
  ),
);

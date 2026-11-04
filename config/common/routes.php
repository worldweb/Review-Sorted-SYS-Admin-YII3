<?php

declare(strict_types=1);

use App\Environment;
use App\Web;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create(Environment::appBasePath())
        ->routes(
            Route::get('[/]')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
            Route::methods(['GET', 'POST'], '/login')
                ->action(Web\Auth\LoginAction::class)
                ->name('login'),
            Route::methods(['GET', 'POST'], '/logout')
                ->action(Web\Auth\LogoutAction::class)
                ->name('logout'),
            Route::methods(['GET', 'POST'], '/new-client')
                ->action(Web\Registration\ClientRegistrationAction::class)
                ->name('new_client'),

            // User Management Routes
            Route::get('/users')
                ->action(Web\User\UserListAction::class)
                ->name('users_index'),
            Route::get('/user')
                ->action(Web\User\UserListAction::class)
                ->name('user_index_alias'),
            Route::get('/user/view')
                ->action(Web\User\UserViewAction::class)
                ->name('user_view'),
            Route::methods(['GET', 'POST'], '/user/create')
                ->action(Web\User\UserCreateAction::class)
                ->name('user_create'),
            Route::methods(['GET', 'POST'], '/user/create-acc-admin')
                ->action(Web\User\UserCreateAccAdminAction::class)
                ->name('user_create_acc_admin'),
            Route::methods(['GET', 'POST'], '/user/create-worker')
                ->action(Web\User\UserCreateWorkerAction::class)
                ->name('user_create_worker'),
            Route::methods(['GET', 'POST'], '/user/update')
                ->action(Web\User\UserUpdateAction::class)
                ->name('user_update'),
            Route::methods(['GET', 'POST'], '/user/delete')
                ->action(Web\User\UserDeleteAction::class)
                ->name('user_delete'),
            Route::get('/user/switch')
                ->action(Web\User\UserSwitchAction::class)
                ->name('user_switch'),
            Route::get('/profile')
                ->action(Web\User\UserViewAction::class)
                ->name('profile_view'),
            Route::methods(['GET', 'POST'], '/profile/edit')
                ->action(Web\User\UserUpdateAction::class)
                ->name('profile_edit'),

            // Workspace & Account Management Routes
            Route::get('/accounts')
                ->action(Web\Account\AccountListAction::class)
                ->name('accounts_index'),
            Route::get('/accounts/export')
                ->action(Web\Account\AccountExportAction::class)
                ->name('accounts_export'),
            Route::get('/accounts/view')
                ->action(Web\Account\AccountViewAction::class)
                ->name('accounts_view'),
            Route::methods(['GET', 'POST'], '/accounts/create')
                ->action(Web\Account\AccountCreateAction::class)
                ->name('accounts_create'),
            Route::methods(['GET', 'POST'], '/accounts/update')
                ->action(Web\Account\AccountUpdateAction::class)
                ->name('accounts_update'),
            Route::methods(['GET', 'POST'], '/accounts/delete')
                ->action(Web\Account\AccountDeleteAction::class)
                ->name('accounts_delete'),
            Route::methods(['GET', 'POST'], '/accounts/subscribe')
                ->action(Web\Account\AccountSubscribeAction::class)
                ->name('accounts_subscribe'),

            // Reviews & Mentions Routes
            Route::get('/reviews')
                ->action(Web\Review\ReviewListAction::class)
                ->name('reviews_index'),
            Route::methods(['GET', 'POST'], '/reviews/view')
                ->action(Web\Review\ReviewViewAction::class)
                ->name('reviews_view'),
            Route::methods(['GET', 'POST'], '/reviews/reply-all')
                ->action(Web\Review\ReviewReplyAllAction::class)
                ->name('reviews_reply_all'),
            Route::methods(['GET', 'POST'], '/reviews/delete')
                ->action(Web\Review\ReviewDeleteAction::class)
                ->name('reviews_delete'),
            Route::methods(['GET', 'POST'], '/reviews/export')
                ->action(Web\Review\ReviewExportAction::class)
                ->name('reviews_export'),

            // QR Code Studio Routes
            Route::get('/qrcode/index')
                ->action(Web\QrCode\Action\QrIndexAction::class)
                ->name('qrcode_index'),
            Route::get('/qrcode')
                ->action(Web\QrCode\Action\QrIndexAction::class)
                ->name('qrcode_index_alias'),
            Route::methods(['GET', 'POST'], '/qrcode/create')
                ->action(Web\QrCode\Action\QrCreateAction::class)
                ->name('qrcode_create'),
            Route::methods(['GET', 'POST'], '/qrcode/update')
                ->action(Web\QrCode\Action\QrUpdateAction::class)
                ->name('qrcode_update'),
            Route::get('/qrcode/view')
                ->action(Web\QrCode\Action\QrViewAction::class)
                ->name('qrcode_view'),
            Route::post('/qrcode/delete')
                ->action(Web\QrCode\Action\QrDeleteAction::class)
                ->name('qrcode_delete'),
            Route::post('/qrcode/ajax')
                ->action(Web\QrCode\Action\QrAjaxAction::class)
                ->name('qrcode_ajax'),
            Route::get('/qrcode/download')
                ->action(Web\QrCode\Action\QrDownloadAction::class)
                ->name('qrcode_download'),
            Route::get('/qrcode/scan')
                ->action(Web\QrCode\Action\QrScanAction::class)
                ->name('qrcode_scan'),

            // Location Groups Routes
            Route::get('/location-group/index')
                ->action(Web\LocationGroup\Action\LocationGroupIndexAction::class)
                ->name('location_group_index'),
            Route::get('/location-group')
                ->action(Web\LocationGroup\Action\LocationGroupIndexAction::class)
                ->name('location_group_index_alias'),
            Route::get('/location-group/view')
                ->action(Web\LocationGroup\Action\LocationGroupViewAction::class)
                ->name('location_group_view'),
            Route::methods(['GET', 'POST'], '/location-group/create')
                ->action(Web\LocationGroup\Action\LocationGroupCreateAction::class)
                ->name('location_group_create'),
            Route::methods(['GET', 'POST'], '/location-group/update')
                ->action(Web\LocationGroup\Action\LocationGroupUpdateAction::class)
                ->name('location_group_update'),
            Route::post('/location-group/delete')
                ->action(Web\LocationGroup\Action\LocationGroupDeleteAction::class)
                ->name('location_group_delete'),
            Route::get('/location-group/destinations')
                ->action(Web\LocationGroup\Action\LocationGroupDestinationsAction::class)
                ->name('location_group_destinations'),
            Route::get('/location-group/get-destinations')
                ->action(Web\LocationGroup\Action\LocationGroupDestinationsAction::class)
                ->name('location_group_get_destinations_alias'),

            // Email Templates, Send & Queue Routes
            Route::get('/email')
                ->action(Web\Email\Action\EmailTemplateIndexAction::class)
                ->name('email_index'),
            Route::get('/email/index')
                ->action(Web\Email\Action\EmailTemplateIndexAction::class)
                ->name('email_index_alias'),
            Route::get('/email/templates')
                ->action(Web\Email\Action\EmailTemplateIndexAction::class)
                ->name('email_templates'),
            Route::get('/email/template/view')
                ->action(Web\Email\Action\EmailTemplateViewAction::class)
                ->name('email_template_view'),
            Route::methods(['GET', 'POST'], '/email/template/create')
                ->action(Web\Email\Action\EmailTemplateCreateAction::class)
                ->name('email_template_create'),
            Route::methods(['GET', 'POST'], '/email/template/update')
                ->action(Web\Email\Action\EmailTemplateUpdateAction::class)
                ->name('email_template_update'),
            Route::post('/email/template/delete')
                ->action(Web\Email\Action\EmailTemplateDeleteAction::class)
                ->name('email_template_delete'),
            Route::methods(['GET', 'POST'], '/email/send')
                ->action(Web\Email\Action\EmailSendAction::class)
                ->name('email_send'),
            Route::get('/email/queue')
                ->action(Web\Email\Action\EmailQueueAction::class)
                ->name('email_queue'),

            // Email Contacts & Importer Routes
            Route::get('/email/importer')
                ->action(Web\Email\Action\EmailContactIndexAction::class)
                ->name('email_importer'),
            Route::get('/email/contacts')
                ->action(Web\Email\Action\EmailContactIndexAction::class)
                ->name('email_contacts_alias'),
            Route::post('/email/importer')
                ->action(Web\Email\Action\EmailImporterAction::class)
                ->name('email_importer_upload'),
            Route::post('/email/contact/delete')
                ->action(Web\Email\Action\EmailContactDeleteAction::class)
                ->name('email_contact_delete'),
            Route::post('/email/contact/delete-bulk')
                ->action(Web\Email\Action\EmailContactBulkDeleteAction::class)
                ->name('email_contact_delete_bulk'),
            Route::get('/email/importer/sample')
                ->action(Web\Email\Action\EmailSampleCsvAction::class)
                ->name('email_importer_sample'),

            // WhatsApp Templates, Send & History Routes
            Route::get('/whatsapp')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateIndexAction::class)
                ->name('whatsapp_index'),
            Route::get('/whatsapp/index')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateIndexAction::class)
                ->name('whatsapp_index_alias'),
            Route::get('/whatsapp/templates')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateIndexAction::class)
                ->name('whatsapp_templates'),
            Route::get('/whatsapp/template/view')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateViewAction::class)
                ->name('whatsapp_template_view'),
            Route::methods(['GET', 'POST'], '/whatsapp/template/create')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateCreateAction::class)
                ->name('whatsapp_template_create'),
            Route::methods(['GET', 'POST'], '/whatsapp/template/update')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateUpdateAction::class)
                ->name('whatsapp_template_update'),
            Route::post('/whatsapp/template/delete')
                ->action(Web\WhatsApp\Action\WhatsAppTemplateDeleteAction::class)
                ->name('whatsapp_template_delete'),
            Route::methods(['GET', 'POST'], '/whatsapp/send')
                ->action(Web\WhatsApp\Action\WhatsAppSendAction::class)
                ->name('whatsapp_send'),
            Route::get('/whatsapp/history')
                ->action(Web\WhatsApp\Action\WhatsAppHistoryAction::class)
                ->name('whatsapp_history'),

            // WhatsApp Contacts & Importer Routes
            Route::get('/whatsapp/importer')
                ->action(Web\WhatsApp\Action\WhatsAppContactIndexAction::class)
                ->name('whatsapp_importer'),
            Route::get('/whatsapp/contacts')
                ->action(Web\WhatsApp\Action\WhatsAppContactIndexAction::class)
                ->name('whatsapp_contacts_alias'),
            Route::post('/whatsapp/importer')
                ->action(Web\WhatsApp\Action\WhatsAppImporterAction::class)
                ->name('whatsapp_importer_upload'),
            Route::post('/whatsapp/contact/delete')
                ->action(Web\WhatsApp\Action\WhatsAppContactDeleteAction::class)
                ->name('whatsapp_contact_delete'),
            Route::post('/whatsapp/contact/delete-bulk')
                ->action(Web\WhatsApp\Action\WhatsAppContactBulkDeleteAction::class)
                ->name('whatsapp_contact_delete_bulk'),
            Route::get('/whatsapp/importer/sample')
                ->action(Web\WhatsApp\Action\WhatsAppSampleCsvAction::class)
                ->name('whatsapp_importer_sample'),

            // Social Connections & Settings Routes
            Route::get('/settings')
                ->action(Web\SocialConnection\Action\SettingsIndexAction::class)
                ->name('settings_index'),
            Route::get('/settings/index')
                ->action(Web\SocialConnection\Action\SettingsIndexAction::class)
                ->name('settings_index_alias'),
            Route::get('/settings/facebook')
                ->action(Web\SocialConnection\Action\FacebookIndexAction::class)
                ->name('settings_facebook'),
            Route::get('/settings/facebook/view')
                ->action(Web\SocialConnection\Action\FacebookViewAction::class)
                ->name('settings_facebook_view'),
            Route::methods(['GET', 'POST'], '/settings/facebook/update')
                ->action(Web\SocialConnection\Action\FacebookUpdateAction::class)
                ->name('settings_facebook_update'),
            Route::get('/settings/facebook/revoke')
                ->action(Web\SocialConnection\Action\FacebookRevokeAction::class)
                ->name('settings_facebook_revoke'),
            Route::get('/settings/facebook/delete')
                ->action(Web\SocialConnection\Action\FacebookDeleteAction::class)
                ->name('settings_facebook_delete'),
            Route::get('/settings/facebook/setup')
                ->action(Web\SocialConnection\Action\FacebookSetupAction::class)
                ->name('settings_facebook_setup'),
            Route::get('/settings/google')
                ->action(Web\SocialConnection\Action\GoogleIndexAction::class)
                ->name('settings_google'),
            Route::get('/settings/google/view')
                ->action(Web\SocialConnection\Action\GoogleViewAction::class)
                ->name('settings_google_view'),
            Route::methods(['GET', 'POST'], '/settings/google/update')
                ->action(Web\SocialConnection\Action\GoogleUpdateAction::class)
                ->name('settings_google_update'),
            Route::get('/settings/google/revoke')
                ->action(Web\SocialConnection\Action\GoogleRevokeAction::class)
                ->name('settings_google_revoke'),
            Route::get('/settings/google/delete')
                ->action(Web\SocialConnection\Action\GoogleDeleteAction::class)
                ->name('settings_google_delete'),
            Route::get('/settings/google/setup')
                ->action(Web\SocialConnection\Action\GoogleSetupAction::class)
                ->name('settings_google_setup'),
            Route::get('/settings/google/link')
                ->action(Web\SocialConnection\Action\GoogleLinkAction::class)
                ->name('settings_google_link'),

            // Facebook API Routes
            Route::get('/settings/facebook-api')
                ->action(Web\SocialConnection\Action\FacebookApiIndexAction::class)
                ->name('settings_facebook_api_index'),
            Route::methods(['GET', 'POST'], '/settings/facebook-api/create')
                ->action(Web\SocialConnection\Action\FacebookApiCreateAction::class)
                ->name('settings_facebook_api_create'),
            Route::methods(['GET', 'POST'], '/settings/facebook-api/update')
                ->action(Web\SocialConnection\Action\FacebookApiUpdateAction::class)
                ->name('settings_facebook_api_update'),
            Route::get('/settings/facebook-api/view')
                ->action(Web\SocialConnection\Action\FacebookApiViewAction::class)
                ->name('settings_facebook_api_view'),
            Route::get('/settings/facebook-api/delete')
                ->action(Web\SocialConnection\Action\FacebookApiDeleteAction::class)
                ->name('settings_facebook_api_delete'),

            // Google API Routes
            Route::get('/settings/google-api')
                ->action(Web\SocialConnection\Action\GoogleApiIndexAction::class)
                ->name('settings_google_api_index'),
            Route::methods(['GET', 'POST'], '/settings/google-api/create')
                ->action(Web\SocialConnection\Action\GoogleApiCreateAction::class)
                ->name('settings_google_api_create'),
            Route::methods(['GET', 'POST'], '/settings/google-api/update')
                ->action(Web\SocialConnection\Action\GoogleApiUpdateAction::class)
                ->name('settings_google_api_update'),
            Route::get('/settings/google-api/view')
                ->action(Web\SocialConnection\Action\GoogleApiViewAction::class)
                ->name('settings_google_api_view'),
            Route::get('/settings/google-api/delete')
                ->action(Web\SocialConnection\Action\GoogleApiDeleteAction::class)
                ->name('settings_google_api_delete'),

            // Social Publishing Studio Routes
            Route::get('/social-post')
                ->action(Web\SocialPost\Action\SocialPostIndexAction::class)
                ->name('social_post_index'),
            Route::get('/social-post/index')
                ->action(Web\SocialPost\Action\SocialPostIndexAction::class)
                ->name('social_post_index_alias'),
            Route::get('/social-post/history')
                ->action(Web\SocialPost\Action\SocialPostHistoryAction::class)
                ->name('social_post_history'),
            Route::methods(['GET', 'POST'], '/social-post/create')
                ->action(Web\SocialPost\Action\SocialPostCreateAction::class)
                ->name('social_post_create'),
            Route::get('/social-post/view')
                ->action(Web\SocialPost\Action\SocialPostViewAction::class)
                ->name('social_post_view'),
            Route::methods(['GET', 'POST'], '/social-post/update')
                ->action(Web\SocialPost\Action\SocialPostUpdateAction::class)
                ->name('social_post_update'),
            Route::get('/social-post/cancel')
                ->action(Web\SocialPost\Action\SocialPostCancelAction::class)
                ->name('social_post_cancel'),
            Route::get('/social-post/delete')
                ->action(Web\SocialPost\Action\SocialPostDeleteAction::class)
                ->name('social_post_delete'),
            Route::post('/social-post/retry')
                ->action(Web\SocialPost\Action\SocialPostRetryAction::class)
                ->name('social_post_retry'),

            // Subscription & Billing Routes
            Route::get('/subscription')
                ->action(Web\Subscription\Action\SubscriptionIndexAction::class)
                ->name('subscription_index'),
            Route::get('/subscription/index')
                ->action(Web\Subscription\Action\SubscriptionIndexAction::class)
                ->name('subscription_index_alias'),
            Route::methods(['GET', 'POST'], '/subscription/create')
                ->action(Web\Subscription\Action\SubscriptionCreateAction::class)
                ->name('subscription_create'),
            Route::methods(['GET', 'POST'], '/subscription/update')
                ->action(Web\Subscription\Action\SubscriptionUpdateAction::class)
                ->name('subscription_update'),
            Route::get('/subscription/delete')
                ->action(Web\Subscription\Action\SubscriptionDeleteAction::class)
                ->name('subscription_delete'),
            Route::methods(['GET', 'POST'], '/stripe-webhook/handle-stripe-event')
                ->action(Web\Subscription\Action\StripeWebhookAction::class)
                ->name('stripe_webhook_handle'),
            Route::methods(['GET', 'POST'], '/stripe-webhook/index')
                ->action(Web\Subscription\Action\StripeWebhookAction::class)
                ->name('stripe_webhook_index'),

            // Global Platform Settings Routes
            Route::methods(['GET', 'POST'], '/global-settings')
                ->action(Web\GlobalSettings\Action\GlobalSettingsIndexAction::class)
                ->name('global_settings_index'),
            Route::methods(['GET', 'POST'], '/global-settings/index')
                ->action(Web\GlobalSettings\Action\GlobalSettingsIndexAction::class)
                ->name('global_settings_index_alias'),

            // API & External Webhooks
            Route::get('/accountapi')
                ->action(Web\Api\Action\AccountApiAction::class)
                ->name('account_api_index'),
            Route::get('/accountapi/index')
                ->action(Web\Api\Action\AccountApiAction::class)
                ->name('account_api_index_alias'),
            Route::methods(['GET', 'POST'], '/hooks/messages')
                ->action(Web\Hooks\Action\HooksMessagesAction::class)
                ->name('hooks_messages'),

            // Notifications
            Route::get('/notification/latest')
                ->action(Web\Notification\Action\NotificationLatestAction::class)
                ->name('notification_latest'),
            Route::methods(['GET', 'POST'], '/notification/mark-read')
                ->action(Web\Notification\Action\NotificationMarkReadAction::class)
                ->name('notification_mark_read'),
            Route::methods(['GET', 'POST'], '/notification/mark-all-read')
                ->action(Web\Notification\Action\NotificationMarkAllReadAction::class)
                ->name('notification_mark_all_read'),

            // Global Search / Command Palette
            Route::get('/site/search')
                ->action(Web\HomePage\GlobalSearchAction::class)
                ->name('site_search'),
        ),
    Route::get('/')
        ->action(Web\HomePage\Action::class)
        ->name('root_home'),
    Route::methods(['GET', 'POST'], '/login')
        ->action(Web\Auth\LoginAction::class)
        ->name('root_login'),
    Route::methods(['GET', 'POST'], '/logout')
        ->action(Web\Auth\LogoutAction::class)
        ->name('root_logout'),
    Route::methods(['GET', 'POST'], '/new-client')
        ->action(Web\Registration\ClientRegistrationAction::class)
        ->name('root_new_client'),
    Route::get('/reviews')
        ->action(Web\Review\ReviewListAction::class)
        ->name('root_reviews_index'),
    Route::methods(['GET', 'POST'], '/reviews/view')
        ->action(Web\Review\ReviewViewAction::class)
        ->name('root_reviews_view'),
    Route::methods(['GET', 'POST'], '/reviews/reply-all')
        ->action(Web\Review\ReviewReplyAllAction::class)
        ->name('root_reviews_reply_all'),
    Route::methods(['GET', 'POST'], '/reviews/delete')
        ->action(Web\Review\ReviewDeleteAction::class)
        ->name('root_reviews_delete'),
    Route::methods(['GET', 'POST'], '/reviews/export')
        ->action(Web\Review\ReviewExportAction::class)
        ->name('root_reviews_export'),

    // Root QR Code Studio Routes
    Route::get('/qrcode/index')
        ->action(Web\QrCode\Action\QrIndexAction::class)
        ->name('root_qrcode_index'),
    Route::get('/qrcode')
        ->action(Web\QrCode\Action\QrIndexAction::class)
        ->name('root_qrcode_index_alias'),
    Route::methods(['GET', 'POST'], '/qrcode/create')
        ->action(Web\QrCode\Action\QrCreateAction::class)
        ->name('root_qrcode_create'),
    Route::methods(['GET', 'POST'], '/qrcode/update')
        ->action(Web\QrCode\Action\QrUpdateAction::class)
        ->name('root_qrcode_update'),
    Route::get('/qrcode/view')
        ->action(Web\QrCode\Action\QrViewAction::class)
        ->name('root_qrcode_view'),
    Route::post('/qrcode/delete')
        ->action(Web\QrCode\Action\QrDeleteAction::class)
        ->name('root_qrcode_delete'),
    Route::post('/qrcode/ajax')
        ->action(Web\QrCode\Action\QrAjaxAction::class)
        ->name('root_qrcode_ajax'),
    Route::get('/qrcode/download')
        ->action(Web\QrCode\Action\QrDownloadAction::class)
        ->name('root_qrcode_download'),
    Route::get('/qrcode/scan')
        ->action(Web\QrCode\Action\QrScanAction::class)
        ->name('root_qrcode_scan'),

    // Root Location Groups Routes
    Route::get('/location-group/index')
        ->action(Web\LocationGroup\Action\LocationGroupIndexAction::class)
        ->name('root_location_group_index'),
    Route::get('/location-group')
        ->action(Web\LocationGroup\Action\LocationGroupIndexAction::class)
        ->name('root_location_group_index_alias'),
    Route::get('/location-group/view')
        ->action(Web\LocationGroup\Action\LocationGroupViewAction::class)
        ->name('root_location_group_view'),
    Route::methods(['GET', 'POST'], '/location-group/create')
        ->action(Web\LocationGroup\Action\LocationGroupCreateAction::class)
        ->name('root_location_group_create'),
    Route::methods(['GET', 'POST'], '/location-group/update')
        ->action(Web\LocationGroup\Action\LocationGroupUpdateAction::class)
        ->name('root_location_group_update'),
    Route::post('/location-group/delete')
        ->action(Web\LocationGroup\Action\LocationGroupDeleteAction::class)
        ->name('root_location_group_delete'),
    Route::get('/location-group/destinations')
        ->action(Web\LocationGroup\Action\LocationGroupDestinationsAction::class)
        ->name('root_location_group_destinations'),
    Route::get('/location-group/get-destinations')
        ->action(Web\LocationGroup\Action\LocationGroupDestinationsAction::class)
        ->name('root_location_group_get_destinations_alias'),

    // Root Email Templates, Send & Queue Routes
    Route::get('/email')
        ->action(Web\Email\Action\EmailTemplateIndexAction::class)
        ->name('root_email_index'),
    Route::get('/email/index')
        ->action(Web\Email\Action\EmailTemplateIndexAction::class)
        ->name('root_email_index_alias'),
    Route::get('/email/templates')
        ->action(Web\Email\Action\EmailTemplateIndexAction::class)
        ->name('root_email_templates'),
    Route::get('/email/template/view')
        ->action(Web\Email\Action\EmailTemplateViewAction::class)
        ->name('root_email_template_view'),
    Route::methods(['GET', 'POST'], '/email/template/create')
        ->action(Web\Email\Action\EmailTemplateCreateAction::class)
        ->name('root_email_template_create'),
    Route::methods(['GET', 'POST'], '/email/template/update')
        ->action(Web\Email\Action\EmailTemplateUpdateAction::class)
        ->name('root_email_template_update'),
    Route::post('/email/template/delete')
        ->action(Web\Email\Action\EmailTemplateDeleteAction::class)
        ->name('root_email_template_delete'),
    Route::methods(['GET', 'POST'], '/email/send')
        ->action(Web\Email\Action\EmailSendAction::class)
        ->name('root_email_send'),
    Route::get('/email/queue')
        ->action(Web\Email\Action\EmailQueueAction::class)
        ->name('root_email_queue'),

    // Root Email Contacts & Importer Routes
    Route::get('/email/importer')
        ->action(Web\Email\Action\EmailContactIndexAction::class)
        ->name('root_email_importer'),
    Route::get('/email/contacts')
        ->action(Web\Email\Action\EmailContactIndexAction::class)
        ->name('root_email_contacts_alias'),
    Route::post('/email/importer')
        ->action(Web\Email\Action\EmailImporterAction::class)
        ->name('root_email_importer_upload'),
    Route::post('/email/contact/delete')
        ->action(Web\Email\Action\EmailContactDeleteAction::class)
        ->name('root_email_contact_delete'),
    Route::post('/email/contact/delete-bulk')
        ->action(Web\Email\Action\EmailContactBulkDeleteAction::class)
        ->name('root_email_contact_delete_bulk'),
    Route::get('/email/importer/sample')
        ->action(Web\Email\Action\EmailSampleCsvAction::class)
        ->name('root_email_importer_sample'),

    // Root WhatsApp Templates, Send & History Routes
    Route::get('/whatsapp')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateIndexAction::class)
        ->name('root_whatsapp_index'),
    Route::get('/whatsapp/index')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateIndexAction::class)
        ->name('root_whatsapp_index_alias'),
    Route::get('/whatsapp/templates')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateIndexAction::class)
        ->name('root_whatsapp_templates'),
    Route::get('/whatsapp/template/view')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateViewAction::class)
        ->name('root_whatsapp_template_view'),
    Route::methods(['GET', 'POST'], '/whatsapp/template/create')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateCreateAction::class)
        ->name('root_whatsapp_template_create'),
    Route::methods(['GET', 'POST'], '/whatsapp/template/update')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateUpdateAction::class)
        ->name('root_whatsapp_template_update'),
    Route::post('/whatsapp/template/delete')
        ->action(Web\WhatsApp\Action\WhatsAppTemplateDeleteAction::class)
        ->name('root_whatsapp_template_delete'),
    Route::methods(['GET', 'POST'], '/whatsapp/send')
        ->action(Web\WhatsApp\Action\WhatsAppSendAction::class)
        ->name('root_whatsapp_send'),
    Route::get('/whatsapp/history')
        ->action(Web\WhatsApp\Action\WhatsAppHistoryAction::class)
        ->name('root_whatsapp_history'),

    // Root WhatsApp Contacts & Importer Routes
    Route::get('/whatsapp/importer')
        ->action(Web\WhatsApp\Action\WhatsAppContactIndexAction::class)
        ->name('root_whatsapp_importer'),
    Route::get('/whatsapp/contacts')
        ->action(Web\WhatsApp\Action\WhatsAppContactIndexAction::class)
        ->name('root_whatsapp_contacts_alias'),
    Route::post('/whatsapp/importer')
        ->action(Web\WhatsApp\Action\WhatsAppImporterAction::class)
        ->name('root_whatsapp_importer_upload'),
    Route::post('/whatsapp/contact/delete')
        ->action(Web\WhatsApp\Action\WhatsAppContactDeleteAction::class)
        ->name('root_whatsapp_contact_delete'),
    Route::post('/whatsapp/contact/delete-bulk')
        ->action(Web\WhatsApp\Action\WhatsAppContactBulkDeleteAction::class)
        ->name('root_whatsapp_contact_delete_bulk'),
    Route::get('/whatsapp/importer/sample')
        ->action(Web\WhatsApp\Action\WhatsAppSampleCsvAction::class)
        ->name('root_whatsapp_importer_sample'),

    // Root Social Connections & Settings Routes
    Route::get('/settings')
        ->action(Web\SocialConnection\Action\SettingsIndexAction::class)
        ->name('root_settings_index'),
    Route::get('/settings/index')
        ->action(Web\SocialConnection\Action\SettingsIndexAction::class)
        ->name('root_settings_index_alias'),
    Route::get('/settings/facebook')
        ->action(Web\SocialConnection\Action\FacebookIndexAction::class)
        ->name('root_settings_facebook'),
    Route::get('/settings/facebook/view')
        ->action(Web\SocialConnection\Action\FacebookViewAction::class)
        ->name('root_settings_facebook_view'),
    Route::methods(['GET', 'POST'], '/settings/facebook/update')
        ->action(Web\SocialConnection\Action\FacebookUpdateAction::class)
        ->name('root_settings_facebook_update'),
    Route::get('/settings/facebook/revoke')
        ->action(Web\SocialConnection\Action\FacebookRevokeAction::class)
        ->name('root_settings_facebook_revoke'),
    Route::get('/settings/facebook/delete')
        ->action(Web\SocialConnection\Action\FacebookDeleteAction::class)
        ->name('root_settings_facebook_delete'),
    Route::get('/settings/facebook/setup')
        ->action(Web\SocialConnection\Action\FacebookSetupAction::class)
        ->name('root_settings_facebook_setup'),
    Route::get('/settings/google')
        ->action(Web\SocialConnection\Action\GoogleIndexAction::class)
        ->name('root_settings_google'),
    Route::get('/settings/google/view')
        ->action(Web\SocialConnection\Action\GoogleViewAction::class)
        ->name('root_settings_google_view'),
    Route::methods(['GET', 'POST'], '/settings/google/update')
        ->action(Web\SocialConnection\Action\GoogleUpdateAction::class)
        ->name('root_settings_google_update'),
    Route::get('/settings/google/revoke')
        ->action(Web\SocialConnection\Action\GoogleRevokeAction::class)
        ->name('root_settings_google_revoke'),
    Route::get('/settings/google/delete')
        ->action(Web\SocialConnection\Action\GoogleDeleteAction::class)
        ->name('root_settings_google_delete'),
    Route::get('/settings/google/setup')
        ->action(Web\SocialConnection\Action\GoogleSetupAction::class)
        ->name('root_settings_google_setup'),
    Route::get('/settings/google/link')
        ->action(Web\SocialConnection\Action\GoogleLinkAction::class)
        ->name('root_settings_google_link'),

    // Root Facebook API Routes
    Route::get('/settings/facebook-api')
        ->action(Web\SocialConnection\Action\FacebookApiIndexAction::class)
        ->name('root_settings_facebook_api_index'),
    Route::methods(['GET', 'POST'], '/settings/facebook-api/create')
        ->action(Web\SocialConnection\Action\FacebookApiCreateAction::class)
        ->name('root_settings_facebook_api_create'),
    Route::methods(['GET', 'POST'], '/settings/facebook-api/update')
        ->action(Web\SocialConnection\Action\FacebookApiUpdateAction::class)
        ->name('root_settings_facebook_api_update'),
    Route::get('/settings/facebook-api/view')
        ->action(Web\SocialConnection\Action\FacebookApiViewAction::class)
        ->name('root_settings_facebook_api_view'),
    Route::get('/settings/facebook-api/delete')
        ->action(Web\SocialConnection\Action\FacebookApiDeleteAction::class)
        ->name('root_settings_facebook_api_delete'),

    // Root Google API Routes
    Route::get('/settings/google-api')
        ->action(Web\SocialConnection\Action\GoogleApiIndexAction::class)
        ->name('root_settings_google_api_index'),
    Route::methods(['GET', 'POST'], '/settings/google-api/create')
        ->action(Web\SocialConnection\Action\GoogleApiCreateAction::class)
        ->name('root_settings_google_api_create'),
    Route::methods(['GET', 'POST'], '/settings/google-api/update')
        ->action(Web\SocialConnection\Action\GoogleApiUpdateAction::class)
        ->name('root_settings_google_api_update'),
    Route::get('/settings/google-api/view')
        ->action(Web\SocialConnection\Action\GoogleApiViewAction::class)
        ->name('root_settings_google_api_view'),
    Route::get('/settings/google-api/delete')
        ->action(Web\SocialConnection\Action\GoogleApiDeleteAction::class)
        ->name('root_settings_google_api_delete'),

    // Root Social Publishing Studio Routes
    Route::get('/social-post')
        ->action(Web\SocialPost\Action\SocialPostIndexAction::class)
        ->name('root_social_post_index'),
    Route::get('/social-post/index')
        ->action(Web\SocialPost\Action\SocialPostIndexAction::class)
        ->name('root_social_post_index_alias'),
    Route::get('/social-post/history')
        ->action(Web\SocialPost\Action\SocialPostHistoryAction::class)
        ->name('root_social_post_history'),
    Route::methods(['GET', 'POST'], '/social-post/create')
        ->action(Web\SocialPost\Action\SocialPostCreateAction::class)
        ->name('root_social_post_create'),
    Route::get('/social-post/view')
        ->action(Web\SocialPost\Action\SocialPostViewAction::class)
        ->name('root_social_post_view'),
    Route::methods(['GET', 'POST'], '/social-post/update')
        ->action(Web\SocialPost\Action\SocialPostUpdateAction::class)
        ->name('root_social_post_update'),
    Route::get('/social-post/cancel')
        ->action(Web\SocialPost\Action\SocialPostCancelAction::class)
        ->name('root_social_post_cancel'),
    Route::get('/social-post/delete')
        ->action(Web\SocialPost\Action\SocialPostDeleteAction::class)
        ->name('root_social_post_delete'),
    Route::post('/social-post/retry')
        ->action(Web\SocialPost\Action\SocialPostRetryAction::class)
        ->name('root_social_post_retry'),

    // Root Subscription & Billing Routes
    Route::get('/subscription')
        ->action(Web\Subscription\Action\SubscriptionIndexAction::class)
        ->name('root_subscription_index'),
    Route::get('/subscription/index')
        ->action(Web\Subscription\Action\SubscriptionIndexAction::class)
        ->name('root_subscription_index_alias'),
    Route::methods(['GET', 'POST'], '/subscription/create')
        ->action(Web\Subscription\Action\SubscriptionCreateAction::class)
        ->name('root_subscription_create'),
    Route::methods(['GET', 'POST'], '/subscription/update')
        ->action(Web\Subscription\Action\SubscriptionUpdateAction::class)
        ->name('root_subscription_update'),
    Route::get('/subscription/delete')
        ->action(Web\Subscription\Action\SubscriptionDeleteAction::class)
        ->name('root_subscription_delete'),
    Route::methods(['GET', 'POST'], '/stripe-webhook/handle-stripe-event')
        ->action(Web\Subscription\Action\StripeWebhookAction::class)
        ->name('root_stripe_webhook_handle'),
    Route::methods(['GET', 'POST'], '/stripe-webhook/index')
        ->action(Web\Subscription\Action\StripeWebhookAction::class)
        ->name('root_stripe_webhook_index'),

    // Root Global Platform Settings Routes
    Route::methods(['GET', 'POST'], '/global-settings')
        ->action(Web\GlobalSettings\Action\GlobalSettingsIndexAction::class)
        ->name('root_global_settings_index'),
    Route::methods(['GET', 'POST'], '/global-settings/index')
        ->action(Web\GlobalSettings\Action\GlobalSettingsIndexAction::class)
        ->name('root_global_settings_index_alias'),

    // Root API & External Webhooks
    Route::get('/accountapi')
        ->action(Web\Api\Action\AccountApiAction::class)
        ->name('root_account_api_index'),
    Route::get('/accountapi/index')
        ->action(Web\Api\Action\AccountApiAction::class)
        ->name('root_account_api_index_alias'),
    Route::methods(['GET', 'POST'], '/hooks/messages')
        ->action(Web\Hooks\Action\HooksMessagesAction::class)
        ->name('root_hooks_messages'),

    // Root Notifications
    Route::get('/notification/latest')
        ->action(Web\Notification\Action\NotificationLatestAction::class)
        ->name('root_notification_latest'),
    Route::methods(['GET', 'POST'], '/notification/mark-read')
        ->action(Web\Notification\Action\NotificationMarkReadAction::class)
        ->name('root_notification_mark_read'),
    Route::methods(['GET', 'POST'], '/notification/mark-all-read')
        ->action(Web\Notification\Action\NotificationMarkAllReadAction::class)
        ->name('root_notification_mark_all_read'),

    // Root Global Search / Command Palette
    Route::get('/site/search')
        ->action(Web\HomePage\GlobalSearchAction::class)
        ->name('root_site_search'),
];

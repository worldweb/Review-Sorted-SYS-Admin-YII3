<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Password Reset HTML Email Template.
 *
 * @var string $userName
 * @var string $appName
 * @var string $resetLink
 * @var string $expireStr
 */

$safeUserName = !empty($userName) ? Html::encode(trim($userName)) : 'there';
$safeAppName  = Html::encode($appName ?? 'Reviews Sorted');
$safeLink     = Html::encode($resetLink);
$expireNotice = Html::encode($expireStr ?? '1 hour');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; background-color: #fefbf6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a2f26; line-height: 1.6;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fefbf6; padding: 36px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 12px; border: 1px solid #ced4da; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;">
                    <tr>
                        <td align="center" style="background-color: #ffffff; padding: 32px 32px 20px 32px; border-bottom: 1px solid #f1f5f9;">
                            <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #234137; letter-spacing: -0.025em;"><?= $safeAppName ?></h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 32px 24px 32px;">
                            <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #1a2f26;">Reset Your Password</h2>
                            <p style="margin: 0 0 16px 0; font-size: 15px; color: #2d5244;">Hello <?= $safeUserName ?>,</p>
                            <p style="margin: 0 0 24px 0; font-size: 15px; color: #475569;">We received a request to reset the password for your <?= $safeAppName ?> account. Click the button below to choose a new password:</p>

                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="background-color: #234137; border-radius: 8px;">
                                                    <a href="<?= $safeLink ?>" target="_blank" style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; letter-spacing: 0.01em; background-color: #234137;">Reset Password</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 16px 0; font-size: 13px; color: #64748b; line-height: 1.5;">This password reset link will expire in <strong><?= $expireNotice ?></strong>. If you did not request a password reset, you can safely ignore this email — your password will remain unchanged.</p>
                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">If the button above does not work, copy and paste this URL into your browser:<br/><a href="<?= $safeLink ?>" style="color: #234137; word-break: break-all;"><?= $safeLink ?></a></p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px 32px; background-color: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                            &copy; <?= date('Y') ?> <?= $safeAppName ?>. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>


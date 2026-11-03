<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Domain\Account\Account;
use App\Domain\Account\AccountRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Security\TenantContext;
use App\Security\UserAuthIdentity;
use App\Security\UserAuthenticationRepositoryInterface;

/**
 * LoginForm.
 * Encapsulates credentials validation and status verification
 * reproducing Yii 2 frontend\models\LoginForm behavior.
 */
final class LoginForm
{
    private string $email = '';
    private string $password = '';
    private bool $rememberMe = true;
    private array $errors = [];
    private ?UserAuthIdentity $identity = null;

    public function __construct(array $data = [])
    {
        $this->load($data);
    }

    public function load(array $data): void
    {
        if (isset($data['email']) && is_string($data['email'])) {
            $this->email = trim($data['email']);
        }
        if (isset($data['password']) && is_string($data['password'])) {
            $this->password = $data['password'];
        }
        if (isset($data['rememberMe'])) {
            $this->rememberMe = (bool) $data['rememberMe'] || $data['rememberMe'] === '1' || $data['rememberMe'] === 'on';
        }
    }

    /**
     * Validates credentials and tenant status.
     * Follows Yii 2 rules:
     * - Email and password are required.
     * - User must exist and have status = ACTIVE (1).
     * - For tenant roles (account_admin, worker), their account status must = ACTIVE (1).
     * - Password must match bcrypt hash via password_verify.
     * - Generic error message to prevent user enumeration.
     */
    public function validate(
        UserAuthenticationRepositoryInterface $authRepo,
        AccountRepositoryInterface $accountRepo,
        ?UserRepositoryInterface $userRepo = null,
    ): bool {
        $this->errors = [];
        $this->identity = null;

        if ($this->email === '') {
            $this->errors['email'] = 'Email cannot be blank.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Email is not a valid email address.';
        }

        if ($this->password === '') {
            $this->errors['password'] = 'Password cannot be blank.';
        }

        if (!empty($this->errors)) {
            return false;
        }

        // Look up identity by email
        $identity = $authRepo->findAuthIdentityByEmail($this->email);

        // Check if user exists and is active
        if ($identity === null || !$identity->isActive()) {
            $this->errors['general'] = 'Incorrect email or password.';
            return false;
        }

        // Verify account status for tenant users (reproducing Yii 2 LoginForm lines 113-118)
        $role = $identity->getRole();
        if ($role === 'account_admin' || $role === 'worker') {
            $accountId = $identity->getAccountId();
            if (($accountId === null || $accountId <= 0) && $userRepo !== null && $identity->getOwnerId() !== null && $identity->getOwnerId() > 0) {
                $owner = $userRepo->findById($identity->getOwnerId(), TenantContext::unrestricted());
                $accountId = $owner?->getAccountId();
            }

            if ($accountId !== null && $accountId > 0) {
                // Must pass unrestricted context during unauthenticated login lookup
                $account = $accountRepo->findById($accountId, TenantContext::unrestricted());
                if ($account !== null && !$account->isActive()) {
                    $this->errors['general'] = 'Your account is disabled. Please contact support.';
                    return false;
                }
            }
        }

        // Validate password using secure password_verify
        if (!$identity->validatePassword($this->password)) {
            $this->errors['general'] = 'Incorrect email or password.';
            return false;
        }

        $this->identity = $identity;
        return true;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return reset($this->errors) ?: null;
    }

    public function getIdentity(): ?UserAuthIdentity
    {
        return $this->identity;
    }
}

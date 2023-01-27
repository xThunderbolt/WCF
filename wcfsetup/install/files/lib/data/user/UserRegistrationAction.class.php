<?php

namespace wcf\data\user;

use wcf\util\UserRegistrationUtil;

/**
 * Executes user registration-related actions.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class UserRegistrationAction extends UserAction
{
    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['validateEmailAddress', 'validateUsername'];

    /**
     * Validates the validate username function.
     */
    public function validateValidateUsername()
    {
        $this->readString('username');
    }

    /**
     * Validates the validate email address function.
     */
    public function validateValidateEmailAddress()
    {
        $this->readString('email');
    }

    /**
     * Validates the given username.
     *
     * @return  array
     */
    public function validateUsername()
    {
        if (!UserRegistrationUtil::isValidUsername($this->parameters['username'])) {
            return [
                'isValid' => false,
                'error' => 'invalid',
            ];
        }

        if (User::getUserByUsername($this->parameters['username'])->userID) {
            return [
                'isValid' => false,
                'error' => 'notUnique',
            ];
        }

        return [
            'isValid' => true,
        ];
    }

    /**
     * Validates given email address.
     *
     * @return  array
     */
    public function validateEmailAddress()
    {
        if (!UserRegistrationUtil::isValidEmail($this->parameters['email'])) {
            return [
                'isValid' => false,
                'error' => 'invalid',
            ];
        }

        if (User::getUserByEmail($this->parameters['email'])->userID) {
            return [
                'isValid' => false,
                'error' => 'notUnique',
            ];
        }

        return [
            'isValid' => true,
        ];
    }
}

<?php

namespace wcf\form;

use wcf\system\exception\NamedUserException;
use wcf\system\exception\UserInputException;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Shows the disclaimer.
 *
 * @author  Marcel Werk
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class DisclaimerForm extends AbstractForm
{
    /**
     * true, if the user has accepted the disclaimer
     * @var bool
     */
    public $accept = false;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        // registration disabled
        if (!WCF::getUser()->userID && REGISTER_DISABLED) {
            throw new NamedUserException(WCF::getLanguage()->get('wcf.user.register.error.disabled'));
        }
    }

    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        parent::readFormParameters();

        if (!WCF::getUser()->userID && isset($_POST['accept'])) {
            $this->accept = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        parent::validate();

        if (!$this->accept) {
            throw new UserInputException('accept');
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        WCF::getSession()->register('disclaimerAccepted', true);
        $this->saved();
        WCF::getSession()->update();

        HeaderUtil::redirect(LinkHandler::getInstance()->getLink('Register'));

        exit;
    }
}

<?php

namespace wcf\system\user\multifactor;

use wcf\system\email\SimpleEmail;
use wcf\system\flood\FloodControl;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\ButtonFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\LanguageItemFormNode;
use wcf\system\form\builder\TemplateFormNode;
use wcf\system\user\authentication\password\algorithm\Bcrypt;
use wcf\system\user\authentication\password\IPasswordAlgorithm;
use wcf\system\user\authentication\password\PasswordAlgorithmManager;
use wcf\system\user\multifactor\backup\CodeFormField;
use wcf\system\WCF;

/**
 * Implementation of random backup codes.
 *
 * @author  Tim Duesterhus
 * @copyright   2001-2020 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since   5.4
 */
final class BackupMultifactorMethod implements IMultifactorMethod
{
    /**
     * @var IPasswordAlgorithm
     */
    private $algorithm;

    // 4 chunks of 5 digits each result in code space of 10^20 which
    // is equivalent to 66.4 bits of security. The unhashed 3 chunks
    // of 5 digits result in 10^15 which is equivalent to 49.8 bits
    // of security.
    // This is sufficient for a rate-limited online attack, but a bit
    // short for an offline attack using a stolen database. In the
    // latter case the TOTP secret which needs to be stored in a form
    // that allows generating valid codes poses a far bigger threat
    // to a single user's security.
    // Thus we use a 20 digit code. It gives users a warm and fuzzy
    // feeling that the codes cannot be easily guessed (due to being
    // longish), while not being unwieldy like a hexadecimal, base32
    // or base64 string.
    public const CHUNKS = 4;

    public const CHUNK_LENGTH = 5;

    /**
     * The number of codes to generate.
     */
    private const CODE_COUNT = 10;

    private const USER_ATTEMPTS_PER_HOUR = 5;

    public function __construct()
    {
        $this->algorithm = new Bcrypt(9);
    }

    /**
     * Returns the number of remaining codes.
     */
    public function getStatusText(Setup $setup): string
    {
        $sql = "SELECT  COUNT(*) - COUNT(useTime) AS count,
                        MAX(useTime) AS lastUsed
                FROM    wcf1_user_multifactor_backup
                WHERE   setupID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$setup->getId()]);

        return WCF::getLanguage()->getDynamicVariable(
            'wcf.user.security.multifactor.backup.status',
            $statement->fetchArray()
        );
    }

    /**
     * @inheritDoc
     */
    public function createManagementForm(IFormDocument $form, ?Setup $setup, $returnData = null): void
    {
        $form->addDefaultButton(false);
        $form->successMessage('wcf.user.security.multifactor.backup.success');

        if ($setup) {
            $sql = "SELECT  *
                    FROM    wcf1_user_multifactor_backup
                    WHERE   setupID = ?";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$setup->getId()]);

            $codes = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $codes = \array_map(static function ($code) use ($returnData) {
                if (isset($returnData[$code['identifier']])) {
                    $code['chunks'] = \str_split($returnData[$code['identifier']], self::CHUNK_LENGTH);
                } else {
                    $code['chunks'] = [
                        $code['identifier'],
                    ];

                    while (\count($code['chunks']) < self::CHUNKS) {
                        $code['chunks'][] = \str_repeat("\u{2022}", self::CHUNK_LENGTH);
                    }
                }

                return $code;
            }, $codes);

            $statusContainer = FormContainer::create('existingCodesContainer')
                ->label('wcf.user.security.multifactor.backup.existingCodes')
                ->appendChildren([
                    TemplateFormNode::create('existingCodes')
                        ->templateName('multifactorManageBackup')
                        ->variables([
                            'codes' => $codes,
                            'isUnveiled' => $returnData !== null,
                        ]),
                ]);
            $form->appendChild($statusContainer);

            $regenerateContainer = FormContainer::create('regenerateCodesContainer')
                ->label('wcf.user.security.multifactor.backup.regenerateCodes')
                ->appendChildren([
                    LanguageItemFormNode::create('explanation')
                        ->languageItem('wcf.user.security.multifactor.backup.regenerateCodes.description'),
                    ButtonFormField::create('regenerateCodes')
                        ->buttonLabel('wcf.user.security.multifactor.backup.regenerateCodes')
                        ->objectProperty('action')
                        ->value('regenerateCodes')
                        ->addValidator(new FormFieldValidator(
                            'regenerateCodes',
                            static function (ButtonFormField $field) {
                                if ($field->getValue() === null) {
                                    $field->addValidationError(
                                        new FormFieldValidationError('unreachable', 'unreachable')
                                    );
                                }
                            }
                        )),
                ]);
            $form->appendChild($regenerateContainer);
        } else {
            // This part of the form is not visible to the end user. It will be implicitly filled in
            // when setting up the first multi-factor method.
            $generateContainer = FormContainer::create('generateCodesContainer')
                ->label('wcf.user.security.multifactor.backup.generateCodes')
                ->appendChildren([
                    ButtonFormField::create('generateCodes')
                        ->buttonLabel('wcf.user.security.multifactor.backup.generateCodes')
                        ->objectProperty('action')
                        ->value('generateCodes')
                        ->addValidator(new FormFieldValidator(
                            'generateCodes',
                            static function (ButtonFormField $field) {
                                if ($field->getValue() === null) {
                                    $field->addValidationError(
                                        new FormFieldValidationError('unreachable', 'unreachable')
                                    );
                                }
                            }
                        )),
                ]);
            $form->appendChild($generateContainer);
        }
    }

    /**
     * Generates a list of codes.
     */
    private function generateCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $chunks = [];
            for ($part = 0; $part < self::CHUNKS; $part++) {
                $chunks[] = \random_int(
                    10 ** (self::CHUNK_LENGTH - 1),
                    (10 ** self::CHUNK_LENGTH) - 1
                );
            }

            $identifier = $chunks[0];
            if (isset($codes[$identifier])) {
                continue;
            }

            $codes[$identifier] = \implode('', $chunks);
        }

        return $codes;
    }

    /**
     * @inheritDoc
     */
    public function processManagementForm(IFormDocument $form, Setup $setup): array
    {
        $formData = $form->getData();
        \assert($formData['action'] === 'generateCodes' || $formData['action'] === 'regenerateCodes');

        $sql = "DELETE FROM wcf1_user_multifactor_backup
                WHERE       setupID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$setup->getId()]);

        $codes = $this->generateCodes();

        $sql = "INSERT INTO wcf1_user_multifactor_backup
                            (setupID, identifier, code, createTime)
                VALUES      (?, ?, ?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        $algorithmName = PasswordAlgorithmManager::getInstance()->getNameFromAlgorithm($this->algorithm);
        foreach ($codes as $identifier => $code) {
            $statement->execute([
                $setup->getId(),
                $identifier,
                $algorithmName . ':' . $this->algorithm->hash($code),
                \TIME_NOW,
            ]);
        }

        return $codes;
    }

    /**
     * Returns a code from $codes matching the $userCode. `null` is returned if
     * no matching code could be found.
     */
    private function findValidCode(string $userCode, array $codes): ?array
    {
        $manager = PasswordAlgorithmManager::getInstance();

        $result = null;
        foreach ($codes as $code) {
            [$algorithmName, $hash] = \explode(':', $code['code'], 2);
            $algorithm = $manager->getAlgorithmFromName($algorithmName);

            // The use of `&` is intentional to disable the shortcutting logic.
            if ($algorithm->verify($userCode, $hash) & $code['useTime'] === null) {
                $result = $code;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function createAuthenticationForm(IFormDocument $form, Setup $setup): void
    {
        $form->markRequiredFields(false);

        $sql = "SELECT  *
                FROM    wcf1_user_multifactor_backup
                WHERE   setupID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$setup->getId()]);
        $codes = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $form->appendChildren([
            CodeFormField::create()
                ->label('wcf.user.security.multifactor.backup.code')
                ->description('wcf.user.security.multifactor.backup.code.description')
                ->autoFocus()
                ->required()
                ->addValidator(new FormFieldValidator(
                    'code',
                    function (TextFormField $field) use ($codes, $setup) {
                        FloodControl::getInstance()->registerUserContent(
                            'com.woltlab.wcf.multifactor.backup',
                            $setup->getId()
                        );
                        $attempts = FloodControl::getInstance()->countUserContent(
                            'com.woltlab.wcf.multifactor.backup',
                            $setup->getId(),
                            new \DateInterval('PT1H')
                        );
                        if ($attempts['count'] > self::USER_ATTEMPTS_PER_HOUR) {
                            $field->value('');
                            $field->addValidationError(new FormFieldValidationError(
                                'flood',
                                'wcf.user.security.multifactor.backup.error.flood',
                                $attempts
                            ));

                            return;
                        }

                        $userCode = \preg_replace('/\s+/', '', $field->getValue());

                        if ($this->findValidCode($userCode, $codes) === null) {
                            $field->value('');
                            $field->addValidationError(new FormFieldValidationError(
                                'invalidCode',
                                'wcf.user.security.multifactor.error.invalidCode'
                            ));
                        }
                    }
                )),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function processAuthenticationForm(IFormDocument $form, Setup $setup): void
    {
        $userCode = \preg_replace('/\s+/', '', $form->getData()['data']['code']);

        $sql = "SELECT  *
                FROM    wcf1_user_multifactor_backup
                WHERE   setupID = ?
                FOR UPDATE";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$setup->getId()]);
        $codes = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $usedCode = $this->findValidCode($userCode, $codes);

        if ($usedCode === null) {
            throw new \RuntimeException('Unable to find a valid code.');
        }

        $sql = "UPDATE  wcf1_user_multifactor_backup
                SET     useTime = ?
                WHERE   setupID = ?
                    AND identifier = ?
                    AND useTime IS NULL";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([
            \TIME_NOW,
            $setup->getId(),
            $usedCode['identifier'],
        ]);

        if ($statement->getAffectedRows() !== 1) {
            throw new \RuntimeException('Unable to invalidate the code.');
        }

        $this->sendAuthenticationEmail($setup, $usedCode);
    }

    /**
     * Notifies the user that an emergency code has been used.
     */
    private function sendAuthenticationEmail(Setup $setup, array $usedCode): void
    {
        $sql = "SELECT  COUNT(*) - COUNT(useTime) AS count
                FROM    wcf1_user_multifactor_backup
                WHERE   setupID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$setup->getId()]);

        $remaining = $statement->fetchSingleColumn();

        $email = new SimpleEmail();
        $email->setRecipient($setup->getUser());
        $email->setMessageID(\sprintf(
            'com.woltlab.wcf.multifactor.backup.used/%d/%d/%s',
            $setup->getUser()->userID,
            TIME_NOW,
            \bin2hex(\random_bytes(8))
        ));

        $email->setSubject(
            WCF::getLanguage()->getDynamicVariable(
                'wcf.user.security.multifactor.backup.authenticationEmail.subject',
                [
                    'remaining' => $remaining,
                    'usedCode' => $usedCode,
                    'setup' => $setup,
                ]
            )
        );
        $email->setHtmlMessage(
            WCF::getLanguage()->getDynamicVariable(
                'wcf.user.security.multifactor.backup.authenticationEmail.body.html',
                [
                    'remaining' => $remaining,
                    'usedCode' => $usedCode,
                    'setup' => $setup,
                ]
            )
        );
        $email->setMessage(
            WCF::getLanguage()->getDynamicVariable(
                'wcf.user.security.multifactor.backup.authenticationEmail.body.plain',
                [
                    'remaining' => $remaining,
                    'usedCode' => $usedCode,
                    'setup' => $setup,
                ]
            )
        );

        $email->send();
    }
}

<?php

namespace wcf\system\bbcode;

use wcf\data\user\group\UserGroup;
use wcf\system\WCF;

/**
 * Parses the [group] bbcode tag.
 *
 * @author      Alexander Ebert
 * @copyright   2001-2019 WoltLab GmbH
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since       5.2
 */
final class GroupBBCode extends AbstractBBCode
{
    /**
     * @inheritDoc
     */
    public function getParsedTag(array $openingTag, $content, array $closingTag, BBCodeParser $parser): string
    {
        $groupID = (!empty($openingTag['attributes'][0])) ? \intval($openingTag['attributes'][0]) : 0;
        $group = UserGroup::getGroupByID($groupID);
        if ($group === null || !$group->canBeMentioned()) {
            return "[group]{$content}[/group]";
        }

        return WCF::getTPL()->fetch('groupBBCodeTag', 'wcf', [
            'group' => $group,
            'groupName' => $content,
        ], true);
    }
}

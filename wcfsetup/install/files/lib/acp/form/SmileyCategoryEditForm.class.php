<?php

namespace wcf\acp\form;

/**
 * Shows the category edit form.
 *
 * @author  Tim Duesterhus
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
class SmileyCategoryEditForm extends AbstractCategoryEditForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'wcf.acp.menu.link.smiley.category.list';

    /**
     * @inheritDoc
     */
    public $objectTypeName = 'com.woltlab.wcf.bbcode.smiley';

    /**
     * @inheritDoc
     */
    public $pageTitle = 'wcf.acp.smiley.category.edit';

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_SMILEY'];
}

/**
 * @author  Joshua Ruesweg
 * @copyright  2001-2021 WoltLab GmbH
 * @license  GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  WoltLabSuite/Core/Acp/Ui/User/Action
 * @since       5.5
 */
define(["require", "exports", "tslib", "./AbstractUserAction", "../../../../Ajax", "../../../../Core", "../../../../Ui/Notification"], function (require, exports, tslib_1, AbstractUserAction_1, Ajax, Core, UiNotification) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.ToggleConfirmEmailAction = void 0;
    AbstractUserAction_1 = tslib_1.__importDefault(AbstractUserAction_1);
    Ajax = tslib_1.__importStar(Ajax);
    Core = tslib_1.__importStar(Core);
    UiNotification = tslib_1.__importStar(UiNotification);
    class ToggleConfirmEmailAction extends AbstractUserAction_1.default {
        init() {
            this.button.addEventListener("click", (event) => {
                event.preventDefault();
                const isEmailConfirmed = Core.stringToBool(this.userDataElement.dataset.emailConfirmed);
                Ajax.api(this, {
                    actionName: (isEmailConfirmed ? "un" : "") + "confirmEmail",
                });
            });
        }
        _ajaxSetup() {
            return {
                data: {
                    className: "wcf\\data\\user\\UserAction",
                    objectIDs: [this.userId],
                },
            };
        }
        _ajaxSuccess(data) {
            data.objectIDs.forEach((objectId) => {
                if (~~objectId == this.userId) {
                    switch (data.actionName) {
                        case "confirmEmail":
                            this.userDataElement.dataset.emailConfirmed = "true";
                            this.button.textContent = this.button.dataset.unconfirmEmailMessage;
                            break;
                        case "unconfirmEmail":
                            this.userDataElement.dataset.emailConfirmed = "false";
                            this.button.textContent = this.button.dataset.confirmEmailMessage;
                            break;
                        default:
                            throw new Error("Unreachable");
                    }
                }
            });
            UiNotification.show();
        }
    }
    exports.ToggleConfirmEmailAction = ToggleConfirmEmailAction;
    exports.default = ToggleConfirmEmailAction;
});

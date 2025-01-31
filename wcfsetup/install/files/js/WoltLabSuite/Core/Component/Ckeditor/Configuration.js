/**
 * Helper class to construct the CKEditor configuration.
 *
 * @author Alexander Ebert
 * @copyright 2001-2023 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.0
 */
define(["require", "exports", "../../Language"], function (require, exports, Language_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.createConfigurationFor = void 0;
    class ConfigurationBuilder {
        #features;
        #divider = "|";
        #removePlugins = [];
        #toolbar = [];
        #toolbarGroups = {};
        constructor(features) {
            this.#features = features;
        }
        #setupHeading() {
            if (this.#features.heading) {
                this.#toolbar.push("heading");
            }
            else {
                this.#removePlugins.push("Heading");
            }
        }
        #setupRemoveFormatting() {
            this.#toolbar.push("removeFormat");
        }
        #setupBasicFormat() {
            this.#toolbar.push("bold", "italic");
        }
        #setupTextFormat() {
            const items = [];
            if (this.#features.underline) {
                items.push("underline");
            }
            else {
                this.#removePlugins.push("Underline");
            }
            if (this.#features.strikethrough) {
                items.push("strikethrough");
            }
            else {
                this.#removePlugins.push("Strikethrough");
            }
            if (this.#features.subscript) {
                items.push("subscript");
            }
            else {
                this.#removePlugins.push("Subscript");
            }
            if (this.#features.superscript) {
                items.push("superscript");
            }
            else {
                this.#removePlugins.push("Superscript");
            }
            if (this.#features.fontColor) {
                items.push("fontColor");
            }
            else {
                this.#removePlugins.push("FontColor");
            }
            if (this.#features.fontFamily) {
                items.push("fontFamily");
            }
            else {
                this.#removePlugins.push("FontFamily");
            }
            if (this.#features.fontSize) {
                items.push("fontSize");
            }
            else {
                this.#removePlugins.push("FontSize");
            }
            if (this.#features.code) {
                items.push("code");
            }
            else {
                this.#removePlugins.push("Code");
            }
            if (items.length > 0) {
                this.#toolbar.push({
                    label: "woltlabToolbarGroup_format",
                    items,
                });
                this.#toolbarGroups["format"] = {
                    icon: "ellipsis;false",
                    label: (0, Language_1.getPhrase)("wcf.editor.button.group.format"),
                };
            }
        }
        #setupList() {
            if (this.#features.list) {
                this.#toolbar.push({
                    label: "woltlabToolbarGroup_list",
                    items: ["bulletedList", "numberedList", "outdent", "indent"],
                });
                this.#toolbarGroups["list"] = {
                    icon: "list;false",
                    label: (0, Language_1.getPhrase)("wcf.editor.button.group.list"),
                };
            }
            else {
                this.#removePlugins.push("List");
            }
        }
        #setupAlignment() {
            if (this.#features.alignment) {
                this.#toolbar.push("alignment");
            }
            else {
                this.#removePlugins.push("Alignment");
            }
        }
        #setupLink() {
            if (this.#features.link) {
                this.#toolbar.push("link");
            }
            else {
                this.#removePlugins.push("Link", "LinkImage");
            }
        }
        #setupImage() {
            if (this.#features.image) {
                this.#toolbar.push("insertImage");
                if (!this.#features.attachment) {
                    this.#removePlugins.push("ImageUpload", "ImageUploadUI", "WoltlabAttachment");
                }
            }
            else {
                this.#removePlugins.push("ImageInsertUI", "ImageToolbar", "ImageStyle", "ImageUpload", "ImageUploadUI");
                if (this.#features.link) {
                    this.#removePlugins.push("LinkImage");
                }
                // Disable built-in plugins that rely on the image plugin.
                this.#removePlugins.push("WoltlabAttachment");
                this.#removePlugins.push("WoltlabSmiley");
            }
        }
        #setupBlocks() {
            const items = [];
            if (this.#features.table) {
                items.push("insertTable");
            }
            else {
                this.#removePlugins.push("Table", "TableToolbar");
            }
            if (this.#features.quoteBlock) {
                items.push("blockQuote");
            }
            else {
                this.#removePlugins.push("BlockQuote", "WoltlabBlockQuote");
            }
            if (this.#features.codeBlock) {
                items.push("codeBlock");
            }
            else {
                this.#removePlugins.push("CodeBlock", "WoltlabCodeBlock");
            }
            if (this.#features.spoiler) {
                items.push("spoiler");
            }
            else {
                this.#removePlugins.push("WoltlabSpoiler");
            }
            if (this.#features.html) {
                items.push("htmlEmbed");
            }
            else {
                this.#removePlugins.push("HtmlEmbed");
            }
            if (items.length > 0) {
                this.#toolbar.push({
                    label: (0, Language_1.getPhrase)("wcf.editor.button.group.block"),
                    icon: "plus",
                    items,
                });
            }
        }
        #insertDivider() {
            this.#toolbar.push(this.#divider);
        }
        #setupMedia() {
            if (!this.#features.media) {
                this.#removePlugins.push("WoltlabMedia");
            }
        }
        #setupMention() {
            if (!this.#features.mention) {
                this.#removePlugins.push("Mention", "WoltlabMention");
            }
        }
        #getToolbar() {
            let allowDivider = false;
            const toolbar = this.#toolbar.filter((item) => {
                if (typeof item === "string" && item === this.#divider) {
                    if (!allowDivider) {
                        return false;
                    }
                    allowDivider = false;
                    return true;
                }
                allowDivider = true;
                return true;
            });
            return toolbar;
        }
        build() {
            if (this.#removePlugins.length > 0 || this.#toolbar.length > 0) {
                throw new Error("Cannot build the configuration twice.");
            }
            this.#setupHeading();
            this.#insertDivider();
            this.#setupRemoveFormatting();
            this.#insertDivider();
            this.#setupBasicFormat();
            this.#setupTextFormat();
            this.#insertDivider();
            this.#setupList();
            this.#setupAlignment();
            this.#setupLink();
            this.#setupImage();
            this.#setupBlocks();
            this.#insertDivider();
            this.#setupMedia();
            this.#setupMention();
        }
        toConfig() {
            const language = Object.keys(window.CKEDITOR_TRANSLATIONS).find((language) => language !== "en");
            const key = language ? language : "en";
            window.CKEDITOR_TRANSLATIONS[key].dictionary["Spoiler"] = (0, Language_1.getPhrase)("wcf.editor.button.spoiler");
            // TODO: The typings are both incompleted and outdated.
            return {
                alignment: {
                    options: [
                        { name: "center", className: "text-center" },
                        { name: "left", className: "text-left" },
                        { name: "justify", className: "text-justify" },
                        { name: "right", className: "text-right" },
                    ],
                },
                language,
                removePlugins: this.#removePlugins,
                fontFamily: {
                    options: [
                        "default",
                        "Arial, Helvetica, sans-serif",
                        "Comic Sans MS, Marker Felt, cursive",
                        "Consolas, Courier New, Courier, monospace",
                        "Georgia, serif",
                        "Lucida Sans Unicode, Lucida Grande, sans-serif",
                        "Tahoma, Geneva, sans-serif",
                        "Times New Roman, Times, serif",
                        'Trebuchet MS", Helvetica, sans-serif',
                        "Verdana, Geneva, sans-serif",
                    ],
                },
                fontSize: {
                    options: [8, 10, 12, "default", 18, 24, 36],
                },
                toolbar: this.#getToolbar(),
                ui: {
                    viewportOffset: {
                        top: 50,
                    },
                },
                woltlabToolbarGroup: this.#toolbarGroups,
            };
        }
    }
    function createConfigurationFor(features) {
        const configuration = new ConfigurationBuilder(features);
        configuration.build();
        return configuration.toConfig();
    }
    exports.createConfigurationFor = createConfigurationFor;
});

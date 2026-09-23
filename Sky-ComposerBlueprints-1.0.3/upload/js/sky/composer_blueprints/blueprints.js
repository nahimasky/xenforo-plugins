/* Sky/ComposerBlueprints v0.1: scoped native editor insertion only; never replace drafts or access Froala directly. */
(function($, window, document)
{
    'use strict';

    XF.ComposerBlueprints = XF.ComposerBlueprints || {};

    XF.ScbBlueprintTrigger = XF.Element.newHandler({
        init: function()
        {
            this.$target.on('click', XF.proxy(this, 'rememberEditor'));
        },

        rememberEditor: function()
        {
            XF.ComposerBlueprints.activeEditor = this.$target.closest('form').find('.js-editor').first();
        }
    });

    XF.ScbBlueprintList = XF.Element.newHandler({
        init: function()
        {
            this.$target.on('click', '.js-scbInsertBlueprint', XF.proxy(this, 'insertBlueprint'));
        },

        insertBlueprint: function(e)
        {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var $editor = XF.ComposerBlueprints.activeEditor;
            if (!$editor || !$editor.length)
            {
                return;
            }

            XF.insertIntoEditor(
                $editor,
                $button.attr('data-scb-html') || '',
                $button.attr('data-scb-message') || ''
            );
            XF.focusEditor($editor);
        }
    });

    XF.Element.register('scb-blueprint-trigger', 'XF.ScbBlueprintTrigger');
    XF.Element.register('scb-blueprint-list', 'XF.ScbBlueprintList');
})(jQuery, window, document);

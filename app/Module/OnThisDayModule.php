<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2018 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Functions\FunctionsEdit;
use Fisharebest\Webtrees\Functions\FunctionsPrintLists;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Theme;

/**
 * Class OnThisDayModule
 */
class OnThisDayModule extends AbstractModule implements ModuleBlockInterface
{
    /** {@inheritdoc} */
    public function getTitle()
    {
        return /* I18N: Name of a module */ I18N::translate('On this day');
    }

    /** {@inheritdoc} */
    public function getDescription()
    {
        return /* I18N: Description of the “On this day” module */ I18N::translate('A list of the anniversaries that occur today.');
    }

    /**
     * Generate the HTML content of this block.
     *
     * @param int      $block_id
     * @param bool     $template
     * @param string[] $cfg
     *
     * @return string
     */
    public function getBlock($block_id, $template = true, $cfg = array())
    {
        global $ctype, $WT_TREE;

        $filter    = $this->getBlockSetting($block_id, 'filter', '1');
        $infoStyle = $this->getBlockSetting($block_id, 'infoStyle', 'table');
        $sortStyle = $this->getBlockSetting($block_id, 'sortStyle', 'alpha');
        $block     = $this->getBlockSetting($block_id, 'block', '1');

        foreach (array('filter', 'infoStyle', 'sortStyle', 'block') as $name) {
            if (array_key_exists($name, $cfg)) {
                $$name = $cfg[$name];
            }
        }

        $todayjd = WT_CLIENT_JD;

        $id    = $this->getName() . $block_id;
        $class = $this->getName() . '_block';
        if ($ctype === 'gedcom' && Auth::isManager($WT_TREE) || $ctype === 'user' && Auth::check()) {
            $title = '<a class="icon-admin" title="' . I18N::translate('Preferences') . '" href="block_edit.php?block_id=' . $block_id . '&amp;ged=' . $WT_TREE->getNameHtml() . '&amp;ctype=' . $ctype . '"></a>';
        } else {
            $title = '';
        }
        $title .= $this->getTitle();

        $content = '';

        // If we are only showing living individuals, then we don't need to search for DEAT events.
        $tags = $filter ? 'BIRT MARR' : 'BIRT MARR DEAT';

        switch ($infoStyle) {
            case 'list':
                // Output style 1:  Old format, no visible tables, much smaller text. Better suited to right side of page.
                $content .= FunctionsPrintLists::eventsList($todayjd, $todayjd, $tags, $filter, $sortStyle);
                break;
            case 'table':
                // Style 2: New format, tables, big text, etc. Not too good on right side of page
                ob_start();
                $content .= FunctionsPrintLists::eventsTable($todayjd, $todayjd, $tags, $filter, $sortStyle);
                $content .= ob_get_clean();
                break;
        }

        if ($template) {
            if ($block) {
                $class .= ' small_inner_block';
            }

            return Theme::theme()->formatBlock($id, $title, $class, $content);
        } else {
            return $content;
        }
    }

    /** {@inheritdoc} */
    public function loadAjax()
    {
        return true;
    }

    /** {@inheritdoc} */
    public function isUserBlock()
    {
        return true;
    }

    /** {@inheritdoc} */
    public function isGedcomBlock()
    {
        return true;
    }

    /**
     * An HTML form to edit block settings
     *
     * @param int $block_id
     */
    public function configureBlock($block_id)
    {
        if (Filter::postBool('save') && Filter::checkCsrf()) {
            $this->setBlockSetting($block_id, 'filter', Filter::postBool('filter'));
            $this->setBlockSetting($block_id, 'infoStyle', Filter::post('infoStyle', 'list|table', 'table'));
            $this->setBlockSetting($block_id, 'sortStyle', Filter::post('sortStyle', 'alpha|anniv', 'alpha'));
            $this->setBlockSetting($block_id, 'block', Filter::postBool('block'));
        }

        $filter    = $this->getBlockSetting($block_id, 'filter', '1');
        $infoStyle = $this->getBlockSetting($block_id, 'infoStyle', 'table');
        $sortStyle = $this->getBlockSetting($block_id, 'sortStyle', 'alpha');
        $block     = $this->getBlockSetting($block_id, 'block', '1');

        echo '<tr><td class="descriptionbox wrap width33">';
        echo /* I18N: Label for a configuration option */ I18N::translate('Show only events of living individuals');
        echo '</td><td class="optionbox">';
        echo FunctionsEdit::editFieldYesNo('filter', $filter);
        echo '</td></tr>';

        echo '<tr><td class="descriptionbox wrap width33">';
        echo /* I18N: Label for a configuration option */ I18N::translate('Presentation style');
        echo '</td><td class="optionbox">';
        echo FunctionsEdit::selectEditControl('infoStyle', array('list' => I18N::translate('list'), 'table' => I18N::translate('table')), null, $infoStyle, '');
        echo '</td></tr>';

        echo '<tr><td class="descriptionbox wrap width33">';
        echo /* I18N: Label for a configuration option */ I18N::translate('Sort order');
        echo '</td><td class="optionbox">';
        echo FunctionsEdit::selectEditControl('sortStyle', array(
            /* I18N: An option in a list-box */ 'alpha' => I18N::translate('sort by name'),
            /* I18N: An option in a list-box */ 'anniv' => I18N::translate('sort by date'),
        ), null, $sortStyle, '');
        echo '</td></tr>';

        echo '<tr><td class="descriptionbox wrap width33">';
        echo /* I18N: label for a yes/no option */ I18N::translate('Add a scrollbar when block contents grow');
        echo '</td><td class="optionbox">';
        echo FunctionsEdit::editFieldYesNo('block', $block);
        echo '</td></tr>';
    }
}

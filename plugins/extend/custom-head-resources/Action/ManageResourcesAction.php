<?php

namespace SunlightExtend\CustomHeadResources\Action;

use DOMDocument;
use Sunlight\Action\ActionResult;
use Sunlight\Core;
use Sunlight\GenericTemplates;
use Sunlight\Message;
use Sunlight\Plugin\Action\PluginAction;
use Sunlight\Router;
use Sunlight\Util\ConfigurationFile;
use Sunlight\Util\Request;
use Sunlight\Xsrf;

class ManageResourcesAction extends PluginAction
{
    public function getTitle(): string
    {
        return _lang('admin.plugins.action.do.config');
    }

    protected function execute(): ActionResult
    {
        global $_admin;

        $output = '';

        if (isset($_GET['saved'])) {
            $output .= Message::ok(_lang('global.saved') . ' <small>(' . GenericTemplates::renderTime(time()) . ')</small>', true);
        }

        // resource map file
        /** @var $resourceMap ConfigurationFile */
        $resourceMap = $this->plugin->getResourcesMap();

        // post request
        if (isset($_POST['save_resources'])) {
            try {
                foreach ($_POST as $k => $v) {
                    if ($resourceMap->offsetExists($k)) {
                        if (is_array($v)) {
                            $resourceMap->offsetSet($k, array_filter(Request::post($k, [], true)));
                        } else {
                            $val = Request::post($k, '');
                            $resourceMap->offsetSet($k, $val);
                        }
                    }
                }
                $resourceMap->save();
                // redirect after save
                $_admin->redirect(Router::admin($_admin->currentModule, ['query' => Core::getCurrentUrl()->getQuery() + ['saved' => 1]]));
                return '';
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        // compose form
        $output .= _buffer(function () use ($resourceMap) { ?>
            <form method="post">

                <h2>CSS</h2>

                <fieldset>
                    <legend>Soubory CSS</legend>
                    <?= $this->getTableBlock('css_files', $resourceMap['css_files']); ?>
                </fieldset>

                <fieldset>
                    <legend>Obsah před CSS soubory</legend>
                    <textarea name="css_before" class="arealine"><?= $resourceMap['css_before'] ?></textarea>
                </fieldset>

                <fieldset>
                    <legend>Obsah po CSS souborech</legend>
                    <textarea name="css_after" class="arealine"><?= $resourceMap['css_after'] ?></textarea>
                </fieldset>

                <h2>JavaScript</h2>
                <fieldset>
                    <legend>Soubory JS</legend>
                    <?= $this->getTableBlock('js_files', $resourceMap['js_files']); ?>
                </fieldset>

                <fieldset>
                    <legend>Obsah před JS soubory</legend>
                    <textarea name="js_before" class="arealine"><?= $resourceMap['js_before'] ?></textarea>
                </fieldset>

                <fieldset>
                    <legend>Obsah po JS souborech</legend>
                    <textarea name="js_after" class="arealine"><?= $resourceMap['js_after'] ?></textarea>
                </fieldset>

                <?= Xsrf::getInput(); ?>
                <br>
                <input type="submit" name="save_resources" value="<?= _lang('global.savechanges') ?>">
            </form>

            <script type="text/javascript">
                $(document).ready(function () {
                    $(".row-adder").click(function (e) {
                        e.preventDefault();
                        var parent = $(this).data('parent');
                        var $replacer = $('<div>').html('<?= $this->composeRemovableRow(); ?>');
                        $replacer.find('input[type=text]').attr('name', parent + '[]');
                        $('#' + parent + ' tbody').append($replacer.html());
                    });
                    $("body").on("click", "#row-deleter", function (e) {
                        e.preventDefault();
                        $(this).parents("tr").remove();
                    });
                });
            </script>
        <?php });

        return ActionResult::output($output);
    }

    private function composeRemovableRow(string $inputName = 'input', string $value = ''): string
    {
        $input = '<input type="text" name="' . _e($inputName) . '[]" value="' . _e($value) . '" class="inputbig">';
        $deleteBtn = '<a class="button" id="row-deleter" href=""><img src="' . _e(Router::path('admin/images/icons/delete.png')) . '" alt="del" class="icon">' . _lang('headresources.btn.delete') . '</a>';
        return '<tr><td class="row-order-cell"><span class="sortable-handle ui-sortable-handle"></span>' . $input . '&nbsp;&nbsp;' . $deleteBtn . '</td></tr>';
    }

    private function getTableBlock(string $blockId, array $values = []): string
    {
        $rows = '';
        if (count($values) > 0) {
            foreach ($values as $value) {
                $rows .= $this->composeRemovableRow($blockId, $value);
            }
        }
        // add new row
        $rows .= $this->composeRemovableRow($blockId);


        return '<table id="' . $blockId . '" style="border-collapse: collapse;">
                <tbody class="sortable ui-sortable" data-handle-selector="td.row-sortable-cell, .sortable-handle">' . $rows . '</tbody>
                <tfoot>
                    <tr><td>
                        <a class="button row-adder" href="" data-parent="' . $blockId . '"><img src="' . _e(Router::path('admin/images/icons/new.png')) . '" alt="add" class="icon">' . _lang('headresources.btn.addfile') . '</a>
                    </td></tr>
                </tfoot>
            </table>';
    }
}
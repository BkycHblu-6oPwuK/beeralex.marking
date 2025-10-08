<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Beeralex\Core\Config\Tab;
use Beeralex\Core\Config\TabsBuilder;
use Beeralex\Core\Modules\Options\Fields\Checkbox;
use Beeralex\Core\Modules\Options\Fields\Input;

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
$POST_RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($POST_RIGHT < "S") {
    $APPLICATION->AuthForm('Недостаточные права доступа');
}
Loader::includeModule($module_id);

$accessTab = new Tab("edit2", Loc::getMessage("MAIN_TAB_RIGHTS"), Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"));
$mainTab = new Tab("edit1", "Настройки", "Настройки");
$mainTab->addField(new Input('MARKING_OAUTH_KEY', 'OAUTH_KEY - любой документ подписанный с помощью УКЭП в base64'));
$mainTab->addField(new Input('MARKING_TOKEN', 'токен полученный через лк, берется если не заполнен OAUTH_KEY'));
$mainTab->addField(new Input('MARKING_DEFAULT_FISKAL_DRIVE_NUMBER', 'ФН (Fiscal Drive Number)'));
$mainTab->addField(new Input('MARKING_BASE_TEST_URL', 'Базовый тестовый url'));
$mainTab->addField(new Input('MARKING_BASE_PROD_URL', 'Базовый боевой url'));
$mainTab->addField(new Checkbox('MARKING_TEST', 'Тестовый режим'));
$mainTab->addField(new Checkbox('MARKING_LOGS', 'Включить логирование'));
$tabsBuilder = (new TabsBuilder())->addTab($mainTab)->addTab($accessTab);

$tabs = $tabsBuilder->getTabs();

if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($tabs as $tab) {
        $fileds = $tab->getFields();
        if (!isset($fileds)) {
            continue;
        }
        foreach ($fileds as $filed) {
            if($name = $filed->getName()){
                if ($request["apply"]) {
                    $optionValue = $request->getPost($name);
                    $optionValue = is_array($optionValue) ? implode(",", $optionValue) : $optionValue;
                    Option::set($module_id, $name, $optionValue);
                }
                if ($request["default"]) {
                    Option::set($module_id, $name, $filed->getDefaultValue());
                }
            }
        }
    }
}
// отрисовываем форму, для этого создаем новый экземпляр класса CAdminTabControl, куда и передаём массив с настройками
$tabControl = new CAdminTabControl(
    "tabControl",
    $tabsBuilder->getTabsFormattedArray()
);

// отображаем заголовки закладок
$tabControl->Begin();
?>

<form action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= $module_id ?>&lang=<?= LANG ?>" method="post">
    <? foreach ($tabs as $tab) {
        if ($options = $tab->getOptionsFormattedArray()) {
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id, $options);
        }
    }
    $tabControl->BeginNextTab();

    require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php";

    $tabControl->Buttons();
    echo (bitrix_sessid_post());
    ?>
    <input class="adm-btn-save" type="submit" name="apply" value="Применить" />
    <input type="submit" name="default" value="По умолчанию" />
</form>
<?
// обозначаем конец отрисовки формы
$tabControl->End();
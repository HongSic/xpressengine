<?php
/**
 * ThemeController.php
 *
 * PHP version 7
 *
 * @category    Controllers
 * @package     App\Http\Controllers
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */

namespace App\Http\Controllers\Plugin;

use App\Http\Controllers\Controller;
use File;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Plugin\PluginHandler;
use Xpressengine\Plugin\PluginProvider;
use Xpressengine\Support\Exceptions\FileAccessDeniedHttpException;
use Xpressengine\Theme\Exceptions\NotSupportSettingException;
use Xpressengine\Theme\ThemeEntityInterface;
use Xpressengine\Theme\ThemeHandler;

/**
 * Class ThemeController
 *
 * @category    Controllers
 * @package     App\Http\Controllers
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */
class ThemeSettingsController extends Controller
{
    public function __construct()
    {
        XePresenter::setSettingsSkinTargetId('plugins');

        app('xe.frontend')->js('assets/core/xe-ui-component/js/xe-form.js')->load();
        app('xe.frontend')->js('assets/core/xe-ui-component/js/xe-page.js')->load();
        app('xe.frontend')->js('assets/core/plugin/js/plugin-index.js')->before(
            [
                'assets/core/xe-ui-component/js/xe-page.js',
                'assets/core/xe-ui-component/js/xe-form.js'
            ]
        )->load();
    }

    public function installed(Request $request, PluginHandler $handler)
    {
        $field = [];
        $field['status'] = $request->get('status');
        $field['keyword'] = $request->get('query', null);

        $themes = $handler->getAllThemes(true);
        $themes = $themes->fetch($field);

        $unresolvedComponents = $handler->getUnresolvedComponents();

        return XePresenter::make('theme.installed', compact(
            'handler',
            'themes',
            'unresolvedComponents'
        ));
    }

    public function install(Request $request, PluginHandler $pluginHandler, PluginProvider $pluginProvider)
    {
        $saleType = $request->get('sale_type', 'free');

        $filter = [
            'collection' => 'theme',
            'site_token' => app('xe.config')->get('plugin')->get('site_token'),
            'sale_type' => $saleType
        ];

        //TODO 릴리즈 기준 확인
        $orderTypes = [
            '1' => ['name' => '다운로드 많은 순', 'order' => 'downloadeds', 'order_type' => 'desc'],
            '2' => ['name' => '다운로드 적은 순', 'order' => 'downloadeds', 'order_type' => 'asc'],
            '3' => ['name' => '가격 낮은 순', 'order' => 'price', 'order_type' => 'asc'],
            '4' => ['name' => '가격 높은 순', 'order' => 'price', 'order_type' => 'desc'],
            '5' => ['name' => '빠른 순', 'order' => 'updated_at', 'order_type' => 'asc'],
            '6' => ['name' => '늦은 순', 'order' => 'updated_at', 'order_type' => 'desc'],
        ];

        if ($orderType = $request->get('order_key')) {
            $order = $orderTypes[$orderType];
            array_forget($order, 'name');
            $filter = array_merge($filter, $order);
        }

        $storeThemes = $pluginProvider->search(array_merge($filter, $request->except('_token', 'order_key')), $request->get('page', 1));
        $themeCategories = $pluginProvider->getPluginCategories('theme');

        $countFree = $countCharge = $countMySite = 0;
        $items = new Collection($storeThemes->data);
        $items->map(function ($item) use (&$countFree, &$countCharge, &$countMySite) {
            if ($item->is_free == true) {
                $countFree++;
            } else {
                $countCharge++;
            }

            if ($item->is_purchased == true) {
                $countMySite++;
            }
        });

        if ($request->get('sale_type') == 'my_site') {
            $items = $items->filter(function ($item, $value) {
                return $item->is_purchased == true;
            });
        }

        $typeCounts = [
            'free' => $countFree,
            'charge' => $countCharge,
            'mySite' => $countMySite
        ];

        $themes = new LengthAwarePaginator(
            $items,
            $storeThemes->total,
            $storeThemes->per_page,
            $storeThemes->current_page
        );
        $themes->setPath(route('settings.theme.install'));
        $themes->appends('filter', $filter);

        return XePresenter::make(
            'theme.install',
            compact('saleType', 'themes', 'themeCategories', 'pluginHandler', 'orderTypes', 'typeCounts')
        );
    }

    public function getDetailPopup(Request $request)
    {
        return api_render('common.detail_popup');
    }

    public function editSetting(ThemeHandler $themeHandler)
    {
        $selectedTheme = $themeHandler->getSiteThemeId();

        return \XePresenter::make('theme.setting', compact('selectedTheme'));
    }

    public function updateSetting(ThemeHandler $themeHandler, Request $request)
    {
        // resolve theme
        $theme = $request->only(['theme_desktop', 'theme_mobile']);
        $theme = ['desktop' => $theme['theme_desktop'], 'mobile' => $theme['theme_mobile']];
        $themeHandler->setSiteTheme($theme);

        return \Redirect::back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function createConfig(Request $request, ThemeHandler $themeHandler)
    {
        $instanceId = $request->get('theme');
        $title = $request->get('title');
        $theme = $themeHandler->getTheme($instanceId);

        if (!$theme->hasSetting()) {
            throw new NotSupportSettingException();
        }

        $configs = $themeHandler->getThemeConfigList($theme->getId());

        $last = array_pop($configs);
        $lastId = $last->name;

        $prefix = $themeHandler->getConfigId($theme->getId());
        $id = str_replace([$prefix, $themeHandler->configDelimiter], '', $lastId);
        $id = (int)$id + 1;
        $newId = $instanceId.$themeHandler->configDelimiter.$id;

        $themeHandler->setThemeConfig($newId, '_configTitle', $title);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::wasCreated')]);
    }

    public function editConfig(Request $request, ThemeHandler $themeHandler)
    {
        $this->validate($request, [
            'theme' => 'required',
        ]);

        $themeId = $request->get('theme');
        $theme = $themeHandler->getTheme($themeId);

        if (!$theme->hasSetting()) {
            throw new NotSupportSettingException();
        }

        $config = $theme->setting();

        return \XePresenter::make('theme.config', compact('theme', 'config'));
    }

    public function updateConfig(Request $request, ThemeHandler $themeHandler)
    {
        $this->validate($request, [
            'theme' => 'required',
        ]);

        // beta.24 에서 laravel 5.5 가 적용된 이후
        // 공백('')값이 "ConvertEmptyStringsToNull" middleware 에 의해
        // null 로 변환됨. config 에 "set" 메서드를 통해 값을 갱신하는 경우
        // null 인 값은 변경사항에서 제외 시키므로 값이 공백상태로 넘겨져야 함.
        // 파일의 경우 $request->all() 호출시 내부적으로 다시 merge 하며
        // null 값이 유지됨.
        $request->merge(array_map(function ($v) {
            return is_null($v) ? '' : $v;
        }, $request->all()));

        $themeId = $request->get('theme');
        $theme = $themeHandler->getTheme($themeId);

        if (!$theme->hasSetting()) {
            throw new NotSupportSettingException();
        }

        $configInfo = $request->only('_configTitle', '_configId');

        $inputs =  $request->except('_token', '_method');
        $inputs['_configId'] = $themeId;

        // 해당 테마에게 config를 가공할 수 있는 기회를 준다.
        $config = $theme->resolveSetting($inputs);

        $config = array_merge($configInfo, $config);

        $themeHandler->setThemeConfig($config['_configId'], $config);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    //TODO return 확인
    public function deleteConfig(Request $request, ThemeHandler $themeHandler)
    {
        $themeId = $request->get('theme');
        $theme = $themeHandler->getTheme($themeId);
        $config = $theme->setting();

        $theme->deleteSetting($config);

        $themeHandler->deleteThemeConfig($themeId);

        return \XePresenter::makeApi([]);
    }

    public function setStartPreview(Request $request)
    {
        $configId = $request->get('configId');

        $array = [
            'configId' => $configId,
            'userId' => \Auth::user()->getId()
        ];

        file_put_contents(app_storage_path('theme_preview.json'), json_enc($array));

        return redirect()->to('/');
    }

    public function setStopPreview(Request $request)
    {
        if (file_exists(app_storage_path('theme_preview.json'))) {
            \File::delete(app_storage_path('theme_preview.json'));
        }
    }

    public function edit(Request $request, ThemeHandler $themeHandler)
    {
        $themes = $themeHandler->getAllTheme();
        $blankThemeClass = app('config')->get('xe.theme.blank');
        $blankThemeId = $blankThemeClass::getId();

        if (isset($themes[$blankThemeId]) == true) {
            unset($themes[$blankThemeId]);
        }

        $themeId = $request->get('theme', null);
        if ($themeId == null) {
            $themeId = array_first($themes)->getId();
        }

        $fileName = $request->get('file', null);

        $theme = \XeTheme::getTheme($themeId);

        /** @var ThemeEntityInterface $theme */
        $files = $theme->getEditFiles();

        if (empty($files)) {
            return \XePresenter::make('theme.edit', [
                'themes' => $themes,
                'theme' => $theme,
                'files' => $files,
            ]);
        }

        if ($fileName === null) {
            $fileName = key($files);
        }

        $filePath = realpath($files[$fileName]);

        $editFile = [
            'fileName' => $fileName,
            'path' => $filePath,
        ];

        if ($themeHandler->hasCache($filePath)) {
            $editFile['hasCache'] = true;
            $fileContent = file_get_contents($themeHandler->getCachePath($filePath));
        } else {
            $editFile['hasCache'] = false;
            $fileContent = file_get_contents($filePath);
        }

        $editFile['content'] = $fileContent;

        return \XePresenter::make('theme.edit', [
            'themes' => $themes,
            'theme' => $theme,
            'files' => $files,
            'editFile' => $editFile
        ]);
    }

    public function update(Request $request, ThemeHandler $themeHandler)
    {
        $themeId = $request->get('theme');
        $fileName = $request->get('file');
        $reset = $request->get('reset');

        $content = $request->get('content');

        $theme = $themeHandler->getTheme($themeId);
        $files = $theme->getEditFiles();

        $filePath = realpath($files[$fileName]);

        $cachePath = $themeHandler->getCachePath($filePath);

        $cacheDir = dirname($cachePath);
        File::makeDirectory($cacheDir, 0755, true, true);

        try {
            if ($reset === 'Y') {
                File::delete($cachePath);
            } else {
                file_put_contents($cachePath, $content);
            }
        } catch (\Exception $e) {
            throw new FileAccessDeniedHttpException();
        }

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }
}

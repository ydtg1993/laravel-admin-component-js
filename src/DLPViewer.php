<?php

namespace DLP;

use Encore\Admin\Actions\RowAction;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;

/**
 * Class DLPViewer
 * @package DLP
 */
class DLPViewer
{
    /**
     * 点
     * @param Form $form
     * @param string $column  字段名
     * @param string $title   名称
     * @param array $select   全部选项
     * @param array $selected 已选择选项
     * @param bool $strict    json严格模式
     */
    public static function makeComponentDot(Form $form, string $column, string $title, array $select = [], array $selected = [],bool $strict = true)
    {
        if ($strict) {
            $select = self::safeJson($select);
            $selected = self::safeJson($selected);
        } else {
            $select = json_encode($select, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
            $selected = json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        }
        self::script(<<<EOF
componentDot("{$column}",JSON.parse('{$selected}'),JSON.parse('{$select}'));
EOF
        );
        $form->html("<div id='{$column}'></div>", $title);
    }

    /**
     * 线
     * @param Form $form
     * @param string $column  字段名
     * @param string $title   名称
     * @param array $settings  设置项
     * @param array $data     数据
     * @param bool $strict    json严格模式
     */
    public static function makeComponentLine(Form $form, string $column, string $title, array $settings = [], array $data = [], bool $strict = true)
    {
        if($strict) {
            $data = self::safeJson($data);
        }else{
            $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS);
        }
        $settings = json_encode($settings, JSON_UNESCAPED_UNICODE);
        self::script(<<<EOF
componentLine("{$column}",JSON.parse('{$settings}'),JSON.parse('{$data}'));
EOF
        );
        $form->html("<div id='{$column}'></div>", $title);
    }

    /**
     * 头部-多操作添加
     * @param Grid $grid
     * @param array $settings [setting,...]
     *  setting.document_class 自定义类名
     *  setting.title 自定义按钮名
     *  setting.url 加载页地址
     *  setting.xhr_url ajax提交地址
     *  setting.method ajax提交方法
     */
    public static function makeHeadPlaneAction(Grid $grid,array $settings = [
        ['document_class'=>'','title'=>'','url'=>'','xhr_url'=>'','method'=>'POST']
    ])
    {
        $script = '';
        foreach ($settings as $setting){
            $xhr_url = isset($setting['xhr_url']) ? $setting['xhr_url'] : $setting['url'];
            $method = isset($setting['method']) ? $setting['method'] : 'POST';
            $script.=<<<EOF
            $('#{$setting['document_id']}').click(function(){
                componentPlane('{$setting['url']}','{$xhr_url}','{$method}');
            });
EOF;
            Admin::script($script);
            $grid->tools->append(new class($setting['title'],$setting['document_id']) extends RowAction {
                private $title;
                private $document_id;
                public function __construct($title,$document_id)
                {
                    parent::__construct();
                    $this->title = $title;
                    $this->document_id = $document_id;
                }
                public function render()
                {
                    return <<<EOF
<div class="btn-group pull-right grid-create-btn" style="margin-right: 10px">
    <a href='javascript:void(0);' class="btn btn-sm btn-primary" id="{$this->document_id}" title="{$this->title}">
        <span class="hidden-xs">{$this->title}</span>
    </a>
</div>
EOF;
                }
            });
        }
    }

    /**
     * 列-多操作添加
     * @param Grid $grid
     * @param array $settings [setting,...]
     *  setting.document_class 自定义类名
     *  setting.title 自定义按钮名
     *  setting.url 加载页地址
     *  setting.xhr_url ajax提交地址
     *  setting.method ajax提交方法
     * @param array $disable ['view','edit','delete']
     */
    public static function makeRowPlaneAction(Grid $grid,array $settings = [
        ['document_class'=>'','title'=>'','url'=>'','xhr_url'=>'','method'=>'POST']
    ],array $disable=[])
    {
        $script = '';
        foreach ($settings as $setting){
            $url = $setting['url'];
            $method = isset($setting['method']) ? $setting['method'] : 'POST';
            $xhr_url = isset($setting['xhr_url']) ? $setting['xhr_url'] : $url;
            $script.=<<<EOF
            $('.{$setting['document_class']}').click(function(){
                let url = '$url'.replace('{id}',$(this).attr('data-id'));
                componentPlane(url,'{$xhr_url}','{$method}');
            });
EOF;
        }
        Admin::script($script);
        $grid->actions(function ($actions)use($settings,$disable) {
            foreach ($settings as $setting) {
                $actions->add(new
                class($setting['document_class'], $setting['title']) extends RowAction {
                    private $title;
                    private $document_class;
                    public function __construct($document_class, $title)
                    {
                        parent::__construct();
                        $this->document_class = $document_class;
                        $this->title = $title;
                    }
                    public function render()
                    {
                        return "<a href='javascript:void(0);' class='{$this->document_class}' data-id='{$this->getKey()}'>{$this->title}</a>";
                    }
                });
            }
            foreach ($disable as $dis){
                $dis == 'view' && $actions->disableView();
                $dis == 'edit' && $actions->disableEdit();
                $dis == 'delete' && $actions->disableDelete();
            }
        });
    }

    /**
     * 新增表单-按钮
     * @param Grid $grid
     */
    public static function makeAddFormAction(Grid $grid)
    {
        $url = Request::capture()->getPathInfo();
        Admin::script(<<<EOF
            $('.CAForm').click(function(){
                componentPlane('{$url}/create','{$url}');
            });
EOF
        );
        $grid->disableCreateButton();
        $grid->tools->append(new
        class extends RowAction {
            public function render()
            {
                return <<<EOF
<div class="btn-group pull-right grid-create-btn" style="margin-right: 10px">
    <a href='javascript:void(0);' class="btn btn-sm btn-success CAForm" title="新增">
        <i class="fa fa-plus"></i><span class="hidden-xs">&nbsp;&nbsp;新增</span>
    </a>
</div>
EOF;
            }
        });
    }

    /**
     * 修改表单-按钮
     * @param Grid $grid
     */
    public static function makeEditFormAction(Grid $grid)
    {
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $url = Request::capture()->getPathInfo();
            Admin::script(<<<EOF
            $('.CEForm').click(function(){
                let url = '{$url}' + '/'+this.getAttribute('data-id');
                componentPlane(url+'/edit',url);
            });
EOF
            );

            $actions->add(new
            class extends RowAction {
                public function render()
                {
                    return "<a href='javascript:void(0);' class='CEForm' data-id='{$this->getKey()}'>修改</a>";
                }
            });
        });
    }

    /**
     * 修改表单-按钮  (旧版图标模式)
     * @param Grid $grid
     */
    public static function _makeEditFormAction(Grid &$grid)
    {
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $url = Request::capture()->getPathInfo();
            Admin::script(<<<EOF
            $('.CEForm').click(function(){
                let url = '{$url}' + '/'+this.getAttribute('data-id');
                componentPlane(url+'/edit',url);
            });
EOF
            );
            $actions->append("<a data-id='{$actions->getKey()}' href='javascript:void(0);' class='CEForm'><i class='fa fa-edit'></i></a>");
        });
    }

    /**
     * 弹窗表单视图生成
     * @param Content $content
     * @return array|string
     * @throws \Throwable
     */
    public static function makeForm(Content $content)
    {
        $items = [
            '_content_' => str_replace('pjax-container', '', $content->build())
        ];
        return view('component.content', $items)->render();
    }

    /**
     * 表单提交ajax返回数据格式
     * @param bool $success
     * @param string $message
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function result($success = true, $message = 'OK', $data = [])
    {
        return response()->json([
            'code' => $success ? 0 : 1,
            'data' => $data,
            'message' => $message
        ], 200)
            ->header('Content-Type', 'application/json;charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * 表单代码段插入js片段代码
     * @param $script
     * @return array|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public static function script($script)
    {
        return Admin::script(<<<EOF
new Promise((resolve, reject) => {
    while (true){
        if(document.getElementById('component') instanceof HTMLElement){
            return resolve();
        }
    }
}).then(function() {
    {$script}
});
EOF
        );
    }

    /**
     * @param array $data
     * @return false|string
     */
    protected static function safeJson(array $data)
    {
        self::recursiveJsonArray($data);
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array $data
     */
    private static function recursiveJsonArray(array &$data)
    {
        foreach ($data as &$d) {
            if (is_array($d)) {
                self::recursiveJsonArray($d);
            } else {
                $d = str_replace(['"', '\'', ':', '\\', '{', '}', '[', ']','`'], '', $d);
            }
        }
    }
}

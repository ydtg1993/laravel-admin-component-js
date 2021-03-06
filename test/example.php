<?php

namespace App\Admin\Controllers;

use DLP\PlaneHeadAction;
use DLP\PlaneRowAction;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use DLP\DLPHelper;
use DLP\DLPViewer;
use Illuminate\Support\Facades\Route;


class ExampleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '测试样例';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Model());
        $grid->model()->where('status', 1);
        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '名称');
        $grid->column('created_at', __('创建时间'))->sortable();
        $grid->column('updated_at', __('更新时间'))->sortable();

        /*配置 禁用原生创建*/
        $grid->disableCreateButton();
        $url = config('app.url') . Route::current()->uri;
        /**
         * 弹窗模式:设置列表-头部操作方法 PlaneHeadAction
         * title    名称
         * url      弹窗页地址
         * xhr_url  表单提交地址
         * method   提交方式 POST PUT GET ...
         * callback ajax请求回调函数
         *      function(response){
         *          alert(response);
         *      }
         */
        $grid->tools->append(new PlaneHeadAction('新增',  $url . '/create', $url, 'POST'));

        /**
         * 弹窗模式:设置列表-行操作方法 PlaneRowAction
         * title    名称
         * url      弹窗页地址 {id}反向匹配当前行id
         * xhr_url  表单提交地址 {id}反向匹配当前行id
         * method   提交方式 POST PUT GET ...
         * callback ajax请求回调函数
         *      function(response){
         *          alert(response);
         *      }
         */
        $grid->actions(function ($actions)use($url){
            $actions->disableEdit();
            $actions->add(new PlaneRowAction('编辑',$url . '/{id}/edit',$url . '/{id}'));
            $actions->add(new PlaneRowAction('自定义页',$url . '/{id}/blank'));
        });
        return $grid;
    }

    public function create(Content $content)
    {
        $content = $content
            ->body($this->form());
        /*弹窗模式 渲染模板DLPViewer::makeForm*/
        return DLPViewer::makeForm($content);
    }

    public function store()
    {
        $request = Request::capture();
        $data = $request->all();
        try {
           //TODO
        } catch (\Exception $e) {
            DB::rollBack();
            return DLPViewer::result(false, $e->getMessage());
        }
        DB::commit();
        return DLPViewer::result(true, '');
    }


    public function edit($id, Content $content)
    {
        $content = $content
            ->body($this->form($id)->edit($id));
        /*弹窗模式 渲染模板DLPViewer::makeForm*/
        return DLPViewer::makeForm($content);
    }

    public function update($id)
    {
        $request = Request::capture();
        $data = $request->all();
        try {
            //TODO
        } catch (\Exception $e) {
            DB::rollBack();
            return DLPViewer::result(false, $e->getMessage());
        }
        DB::commit();
        return DLPViewer::result(true, '');
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id)
    {
        $form = new Form(new Model);

        /**
         * 点组件
         * options 设置数据集 一维数组 格式 [value1=>text1,value2=>text2...]
         * checked 已选择 一维数组 值类型integer
         * attribute.height 设置高度 默认200px
         */
        $form->Dot('dot','标签选择器')
            ->options([1=>'松下紗栄子',2=>'上原亜衣',3=>'白石茉莉奈',4=>'美谷朱里',5=>'沖田杏梨',
            6=>'由愛可奈',7=>'七瀬あいり',8=>'五十嵐星蘭',9=>'仲里紗羽',10=>'波多野結衣'])
            ->checked([1,2,3])
            ->attribute(['height'=>'200px']);

        /**
         * 级联点组件
         * options 设置数据集 多维数组 格式[[key=>key1,val=>value1,nodes=>[...]],...]
         *          链表结构数据辅助组装
         *              1.倒排父节点查询
         *          $select = Model::orderBy('parent_id','DESC')->select('id as key','name as val','parent_id as par')->get()->toArray();
         *              2.辅助函数dimension 组装后的结构参考$this->cascadeData()的示例数据
         *          DLPHelper::dimension($select);
         * checked 已选择 一维数组 值类型integer
         * attribute.height 设置高度 默认200px
         */
        $form->CascadeDot('cascadeDot','级联标签选择器')
            ->options($this->cascadeData())
            ->checked([614,377,550,543,544])
            ->attribute(['height'=>'200px']);


        /**
         * 线组件
         * options 设置数据集 二维数组
         * attribute.columns 设置列表head名称 row字段输出格式input,text,hidden
         * attribute.height  设置高度 默认360px
         * attribute.options 设置操作列 默认开启['sortable'=>true,'delete'=>true]
         */
        $form->Linear('flux_linkage', '磁力链接')
            ->options([
            ['name'=>'01','meta'=>'test info1','url'=>'1','time'=>'2021-05-15 00:00:00','is-small'=>1,'is-warning'=>1],
            ['name'=>'02','meta'=>'test info2','url'=>'2','time'=>'2021-05-15 00:00:00','is-small'=>1,'is-warning'=>1],
            ['name'=>'03','meta'=>'test info3','url'=>'3','time'=>'2021-05-15 00:00:00','is-small'=>1,'is-warning'=>1]])
            ->attribute([
            'columns' => [
                'name' => ['name' => '名称', 'type' => 'input'],
                'meta' => ['name' => '信息', 'type' => 'input'],
                'url' => ['name' => '链接', 'type' => 'input'],
                'time' => ['name' => '更新时间', 'type' => 'text'],
                'is-small' => ['name' => '高清[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                'is-warning' => ['name' => '含字幕[1是 2否]', 'type' => 'input', 'style' => 'width:60px'],
                'tooltip' => ['name' => '可下载[1是 2否]', 'type' => 'input', 'style' => 'width:60px']]
        ]);
        return $form;
    }

    public function blank()
    {
        $html = '<h1>松下紗栄子</h1>';
        return DLPViewer::makeHtml($html);
    }

    private function cascadeData()
    {
        return [
            ["key" => "3", "val" => "基本", "nodes" => [
                ["key" => "6895", "val" => "可播放"],
                ["key" => "6896", "val" => "可下載"],
                ["key" => "6863", "val" => "含字幕"],
                ["key" => "6855", "val" => "含預覽圖"],
                ["key" => "6862", "val" => "含預覽視頻"]
            ]],
            ["key" => "10", "val" => "年份", "nodes" => [
                ["key" => "6868", "val" => "2021"],
                ["key" => "6867", "val" => "2020"],
                ["key" => "6866", "val" => "2019"],
                ["key" => "14", "val" => "2018"],
                ["key" => "15", "val" => "2017"],
                ["key" => "16", "val" => "2016"],
                ["key" => "6897", "val" => "2015"],
                ["key" => "18", "val" => "2014"],
                ["key" => "19", "val" => "2013"],
                ["key" => "20", "val" => "2012"],
                ["key" => "6898", "val" => "2011"],
                ["key" => "22", "val" => "2010"]
            ]],
            ["key" => "32", "val" => "主題", "nodes" => [
                ["key" => "443", "val" => "按摩油"],
                ["key" => "444", "val" => "成熟妈妈"],
                ["key" => "445", "val" => "綠帽男"]
            ]],
            ["key" => "86", "val" => "角色", "nodes" => [
                ["key" => "614", "val" => "空姐"],
                ["key" => "616", "val" => "繼女"],
                ["key" => "587", "val" => "少女"],
                ["key" => "617", "val" => "角色扮演"],
                ["key" => "588", "val" => "醫生\/護士"],
                ["key" => "618", "val" => "女友"],
                ["key" => "589", "val" => "性愛專家"],
                ["key" => "619", "val" => "女神"],
                ["key" => "377", "val" => "熟女"],
                ["key" => "590", "val" => "媽媽"],
                ["key" => "591", "val" => "女抖S"],
                ["key" => "592", "val" => "抖M"],
                ["key" => "593", "val" => "妻子"],
                ["key" => "601", "val" => "女傭"],
                ["key" => "603", "val" => "水管工"],
                ["key" => "604", "val" => "警察"],
                ["key" => "402", "val" => "女學生"],
                ["key" => "607", "val" => "女戰士"],
                ["key" => "608", "val" => "特務"],
                ["key" => "609", "val" => "老師"],
                ["key" => "610", "val" => "女服務員"],
                ["key" => "110", "val" => "秘書"]
            ]],
            ["key" => "133", "val" => "服裝", "nodes" => [
                ["key" => "550", "val" => "吊襪腰帶"],
                ["key" => "551", "val" => "背心"],
                ["key" => "552", "val" => "裙子"],
                ["key" => "553", "val" => "短裙"],
                ["key" => "554", "val" => "短褲"],
                ["key" => "555", "val" => "綁腿"],
                ["key" => "556", "val" => "太陽鏡"],
                ["key" => "557", "val" => "帽子"],
                ["key" => "558", "val" => "襯衫"],
                ["key" => "559", "val" => "睡衣"],
                ["key" => "560", "val" => "內褲"],
                ["key" => "561", "val" => "牛仔短褲"],
                ["key" => "542", "val" => "蕾絲"],
                ["key" => "543", "val" => "高跟鞋"],
                ["key" => "544", "val" => "絲襪"],
                ["key" => "545", "val" => "吊帶"],
                ["key" => "416", "val" => "比基尼"],
                ["key" => "6912", "val" => "眼鏡"],
                ["key" => "546", "val" => "眼罩"],
                ["key" => "137", "val" => "制服"],
                ["key" => "547", "val" => "牛仔褲"],
                ["key" => "158", "val" => "緊身衣"]
            ]]
        ];
    }
}

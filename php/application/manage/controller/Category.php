<?php
/**
 * XNRCMS<562909771@qq.com>
 * ============================================================================
 * 版权所有 2018-2028 杭州新苗科技有限公司，并保留所有权利。
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: xnrcms<562909771@qq.com>
 * Date: 2018-04-13
 * Description:系统分类模块
 */

namespace app\manage\controller;

use app\manage\controller\Base;

class Category extends Base
{
    private $apiUrl         = [];

    public function __construct()
    {
        parent::__construct();

        $this->apiUrl['goodscat']     = 'Admin/Category/listData';
        $this->apiUrl['articlecat']   = 'Admin/Category/listData';
        $this->apiUrl['edit']         = 'Admin/Category/detailData';
        $this->apiUrl['add_save']     = 'Admin/Category/saveData';
        $this->apiUrl['edit_save']    = 'Admin/Category/saveData';
        $this->apiUrl['quickedit']    = 'Admin/Category/quickEditData';
        $this->apiUrl['del']          = 'Admin/Category/delData';
    }

    //商品分类
    public function goodscat()
    {

        $arr['ctype']              = 1;
        $arr['isback']             = 0;
        $arr['title1']             = '分类管理';
        $arr['title2']             = '彩种分类添加与管理';
        $arr['notice']             = ['温馨提示：顶级分类（一级大类）设为推荐时才会在首页楼层中显示','最多只能分类到三级','<strong style="color:#ff0000;">"是否推荐"-&gt;设置为推荐之后, 该分类会在首页楼层显示</strong>'];

        return $this->index($arr);
    }

    //商品分类
    public function articlecat()
    {

        $arr['ctype']              = 2;
        $arr['isback']             = 0;
        $arr['title1']             = '分类管理';
        $arr['title2']             = '文章分类添加与管理';
        $arr['notice']             = ['温馨提示：顶级分类（一级大类）设为推荐时才会在首页楼层中显示','最多只能分类到三级','<strong style="color:#ff0000;">"是否推荐"-&gt;设置为推荐之后, 该分类会在首页楼层显示</strong>'];

        return $this->index($arr);
    }

	//列表页面
	public function index($arr)
    {
		$menuid     = input('menuid',0) ;
		$search 	= input('search','');
        $page       = input('page',1);

        //页面操作功能菜单
        $topMenu    = formatMenuByPidAndPos($menuid,2, $this->menu);
        $rightMenu  = formatMenuByPidAndPos($menuid,3, $this->menu);

        //获取表头以及搜索数据
        $tags       = strtolower(request()->controller() . '/' . request()->action());
        $listNode   = $this->getListNote($tags) ;

        //获取列表数据
        $parame['uid']      = $this->uid;
        $parame['hashid']   = $this->hashid;
        $parame['page']     = $page;
        $parame['ctype']    = $arr['ctype'];
        $parame['search']   = !empty($search) ? json_encode($search) : '' ;

        //请求数据
        if (!isset($this->apiUrl[request()->action()]) || empty($this->apiUrl[request()->action()]))
        $this->error('未设置接口地址');

        $res                = $this->apiData($parame,$this->apiUrl[request()->action()]);
        $data               = $this->getApiData() ;

        $total 				= 0;
        $p 					= '';
        $listData 			= [];

        if ($res){

            //分页信息
            $page           = new \xnrcms\Page($data['total'], $data['limit']);
            if($data['total']>=1){

                $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
                $page->setConfig('header','');
            }

            $p 				= trim($page->show());
            $total 			= $data['total'];
            $listData   	= $data['lists'];
        }

        $Tree                           = new \xnrcms\DataTree($listData);
        $listData                       = $Tree->toFormatTree();

        //缓存数据
        cache('category_list_for_ctype',$arr['ctype']);
        cache('category_list_for_select',$listData);

        //页面头信息设置
        $pageData['isback']             = $arr['isback'];
        $pageData['title1']             = $arr['title1'];
        $pageData['title2']             = $arr['title2'];
        $pageData['notice']             = $arr['notice'];

        //渲染数据到页面模板上
        $assignData['isTree']           = 1;
        $assignData['_page']            = $p;
        $assignData['_total']           = $total;
        $assignData['topMenu']          = $topMenu;
        $assignData['rightMenu']        = $rightMenu;
        $assignData['listId']           = isset($listNode['info']['id']) ? intval($listNode['info']['id']) : 0;
        $assignData['listNode']         = $listNode;
        $assignData['listData']         = $listData;
        $assignData['pageData']         = $pageData;
        $this->assignData($assignData);

        //记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        //异步请求处理
        if(request()->isAjax()){

            echo json_encode(['listData'=>$this->fetch('public/list/listData'),'listPage'=>$p]);exit();
        }

        //加载视图模板
        return view('catlist');
	}

	//新增页面
	public function add()
    {
		//数据提交
        if (request()->isPost()) $this->update();

        //表单模板
        $tags                           = strtolower(request()->controller() . '/addedit');
        $formData                       = $this->getFormFields($tags,0) ;

        //数据详情
        $info                           = $this->getDetail(0);
        $info['pid']                    = input('pid',0);
        $info['status']                 = 1;

        //页面头信息设置
        $pageData['isback']             = 0;
        $pageData['title1']             = '';
        $pageData['title2']             = '';
        $pageData['notice']             = [];
        
        //记录当前列表页的cookie
        cookie('__forward__',$_SERVER['REQUEST_URI']);

        //渲染数据到页面模板上
        $assignData['formId']           = isset($formData['info']['id']) ? intval($formData['info']['id']) : 0;
        $assignData['formFieldList']    = $formData['list'];
        $assignData['info']             = $info;
        $assignData['defaultData']      = $this->getDefaultParameData();
        $assignData['pageData']         = $pageData;
        $this->assignData($assignData);

        //加载视图模板
        return view('addedit');
	}

	//编辑页面
	public function edit($id = 0)
    {
		//数据提交
        if (request()->isPost()) $this->update();

		//表单模板
        $tags                           = strtolower(request()->controller() . '/addedit');
        $formData                       = $this->getFormFields($tags,1);

        //数据详情
        $info                           = $this->getDetail($id);

        //页面头信息设置
        $pageData['isback']             = 0;
        $pageData['title1']             = '';
        $pageData['title2']             = '';
        $pageData['notice']             = [];
        
        //记录当前列表页的cookie
        cookie('__forward__',$_SERVER['REQUEST_URI']);

        //渲染数据到页面模板上
        $assignData['formId']           = isset($formData['info']['id']) ? intval($formData['info']['id']) : 0;
        $assignData['formFieldList']    = $formData['list'];
        $assignData['info']             = $info;
        $assignData['defaultData']      = $this->getDefaultParameData();
        $assignData['pageData']         = $pageData;
        $this->assignData($assignData);

        //加载视图模板
        return view('addedit');
	}

    //数据删除
    public function del()
    {
        $ids     = request()->param();
        $ids     = (isset($ids['ids']) && !empty($ids['ids'])) ? $ids['ids'] : $this->error('请选择要操作的数据');;
        $ids     = is_array($ids) ? implode($ids,',') : '';

        //请求参数
        $parame['uid']          = $this->uid;
        $parame['hashid']       = $this->hashid;
        $parame['id']           = $ids ;

        //请求地址
        if (!isset($this->apiUrl[request()->action()]) || empty($this->apiUrl[request()->action()]))
        $this->error('未设置接口地址');

        //接口调用
        $res       = $this->apiData($parame,$this->apiUrl[request()->action()]);
        $data      = $this->getApiData() ;

        if($res == true){

            $this->success('删除成功',Cookie('__forward__'));
        }else{
            
            $this->error($this->getApiError());
        }
    }

    //快捷编辑
	public function quickEdit()
    {
        //请求地址
        if (!isset($this->apiUrl[request()->action()]) || empty($this->apiUrl[request()->action()]))
        $this->error('未设置接口地址');
        
        //接口调用
        if ($this->questBaseEdit($this->apiUrl[request()->action()])){
            $postData                   = input('post.');
            if ($postData['fieldName'] == 'delay') {
                $lotterid   = $postData['dataId'];
                $delay      = $postData['value'];
                $lottery        = new \app\api\lottery\Lottery($lotterid);
                $lottery->updataLotteryOpenTime($delay);
            }
            $this->success('更新成功');
        }

        $this->error('更新失败');
    }

    //处理提交新增或编辑的数据
    private function update()
    {
        $formid                     = intval(input('formId'));
        $formInfo                   = cache('DevformDetails'.$formid);
        if(empty($formInfo)) $this->error('表单模板数据不存在');

        //表单数据
        $postData                   = input('post.');

        $ctype                      = cache('category_list_for_ctype');
        //用户信息
        $postData['uid']            = $this->uid;
        $postData['hashid']         = $this->hashid;
        $postData['ctype']          = empty($ctype) ? 0 : $ctype;

        //表单中不允许提交至接口的参数
        $notAllow                   = ['formId'];

        //过滤不允许字段
        if(!empty($notAllow)){

            foreach ($notAllow as $key => $value) unset($postData[$value]);
        }
        
        //请求数据
        if (!isset($this->apiUrl[request()->action().'_save'])||empty($this->apiUrl[request()->action().'_save'])) 
        $this->error('未设置接口地址');

        $res       = $this->apiData($postData,$this->apiUrl[request()->action().'_save']) ;
        $data      = $this->getApiData() ;

        if($res){

            $this->success($postData['id']  > 0 ? '更新成功' : '新增成功',Cookie('__forward__')) ;
        }else{

            $this->error($this->getApiError()) ;
        }
    }
    
    //获取数据详情
    private function getDetail($id = 0)
    {
        $info           = [];

        if ($id > 0)
        {
            //请求参数
            $parame             = [];
            $parame['uid']      = $this->uid;
            $parame['hashid']   = $this->hashid;
            $parame['id']       = $id ;

            //请求数据
            $apiUrl     = (isset($this->apiUrl[request()->action()]) && !empty($this->apiUrl[request()->action()])) ? $this->apiUrl[request()->action()] : $this->error('未设置接口地址');
            $res        = $this->apiData($parame,$apiUrl,false);
            $info       = $res ? $this->getApiData() : $this->error($this->getApiError());
        }

        return $info;
    }

    //扩展枚举，布尔，单选，复选等数据选项
    protected function getDefaultParameData()
    {
        $defaultData['getCategoryList']   = $this->getCategoryList();

        return $defaultData;
    }

    private function getCategoryList()
    {
        $clist  = cache('category_list_for_select');
        $arr    = [];

        if (!empty($clist)) {
            foreach ($clist as $key => $value) $arr[$value['id']]  = $value['title_show'];
        }

        return $arr;
    }
}
?>
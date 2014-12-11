<?php

class CompanyAction extends UserAction {

    public $token;
    public $isBranch;
    public $company_model;

    public function _initialize() {
        parent::_initialize();
        $this->token = session('token');
        $this->assign('token', $this->token);
        //权限
        if ($this->token != $_GET['token']) {
            exit();
        }
        //是否是分店
        $this->isBranch = 0;
        if (isset($_GET['isBranch']) && intval($_GET['isBranch'])) {
            $this->isBranch = $_GET['isBranch'];
        }
        $this->assign('isBranch', $this->isBranch);
        //
        $this->company_model = M('Company');
    }

    public function index() {
        $where = array('token' => $this->token);
        if ($this->isBranch) {
            $id = intval($_GET['id']);
            $where['id'] = $id;
            $where['isbranch'] = 1;
        } else {
            $where['isbranch'] = 0;
        }
        $thisCompany = $this->company_model->where($where)->find();
        if (!$this->isBranch) {
            $fatherCompany = $this->company_model->where(array('token' => $this->token, 'isbranch' => 0))->order('id ASC')->find();
            if ($fatherCompany) {
                $tj = array('token' => $this->token);
                $tj['id'] = array('neq', intval($fatherCompany['id']));
                $this->company_model->where($tj)->save(array('isbranch' => 1));
            }
        }
        if (IS_POST) {//post提交
            $_POST['password'] = isset($_POST['password']) && $_POST['password'] ? md5(trim($_POST['password'])) : '';
            if (!$thisCompany) {//新增
                if ($this->isBranch) {
                    $this->insert('Company', U('Company/branches', array('token' => $this->token, 'isBranch' => $this->isBranch)));
                } else {
                    $this->insert('Company', U('Company/index', array('token' => $this->token, 'isBranch' => $this->isBranch)));
                }
            } else {//修改
                $amap = new amap();//高德地图LBS类
                if (!$thisCompany['amapid'] && $thisCompany['longitude'] == $_POST['longitude']) {//经度没变
                    $locations = $amap->coordinateConvert($thisCompany['longitude'], $thisCompany['latitude']);
                    $_POST['longitude'] = $locations['longitude'];
                    $_POST['latitude'] = $locations['latitude'];
                }
                if (!$thisCompany['amapid']) {//生成ampaid
                    $ampaid = $amap->create($_POST['name'], $_POST['longitude'] . ',' . $_POST['latitude'], $_POST['tel'], $_POST['address']);
                    $_POST['amapid'] = intval($ampaid);
                } else {//修改地图的坐标
                    $amap->update($thisCompany['amapid'], $_POST['name'], $_POST['longitude'] . ',' . $_POST['latitude'], $_POST['tel'], $_POST['address']);
                }
                //
                if ($this->company_model->create()) {
                    if (empty($_POST['password'])) {
                        unset($_POST['password']);
                    }
                    if ($this->company_model->where($where)->save($_POST)) {
                        if ($this->isBranch) {
                            $this->success('修改成功', U('Company/branches', array('token' => $this->token, 'isBranch' => $this->isBranch)));
                        } else {
                            $this->success('修改成功', U('Company/index', array('token' => $this->token, 'isBranch' => $this->isBranch)));
                        }
                    } else {
                        $this->error('操作失败');
                    }
                } else {
                    $this->error($this->company_model->getError());
                }
            }
        } else {
            $this->assign('set', $thisCompany);
            $this->display();
        }
    }

    public function branches() {
        $thisCompany = $this->company_model->where(array('token' => $this->token))->order('id ASC')->find();
        $where = array('token' => $this->token);
        $where['id'] = array('neq', $thisCompany['id']);
        $branches = $this->company_model->where($where)->order('taxis ASC')->select();
        $list = array();
        foreach ($branches as $b) {
            $b['url'] = $_SERVER['HTTP_HOST'] . '/index.php?m=Index&a=clogin&cid=' . $b['id'] . '&k=' . md5($b['id'] . $b['username']);
            $list[] = $b;
        }
        $this->assign('branches', $list);
        $this->display();
    }
    /**
     * 商家列表
     */
    public function shangjia_list(){
        $where = array('token' => $this->token);
        $where['isbranch'] = 2;
        $branches = $this->company_model->where($where)->order('id ASC')->select();
        $list = array();
        foreach ($branches as $b) {
            //生成商家后台的链接
            $b['url'] = $_SERVER['HTTP_HOST'] . '/index.php?m=Index&a=clogin&cid=' . $b['id'] . '&k=' . md5($b['id'] . $b['username']);
            $list[] = $b;
        }
        $this->assign('branches', $list);
        $this->display('');
    }
    /**
     * 添加商家
     */
    public function shangjia_add(){
        $where = array('token' => $this->token);
        $id = intval($_GET['id']);
        $where['id'] = $id;
        $where['isbranch'] = $this->isBranch;
        $thisCompany = $this->company_model->where($where)->find();
        if (IS_POST) {//post提交
            $_POST['password'] = isset($_POST['password']) && $_POST['password'] ? md5(trim($_POST['password'])) : '';
            if (!$thisCompany) {//新增
                $this->insert('Company', U('Company/shangjia_list', array('token' => $this->token, 'isBranch' => $this->isBranch)));
            } else {//修改
                $amap = new amap();//高德地图LBS类
                if (!$thisCompany['amapid'] && $thisCompany['longitude'] == $_POST['longitude']) {//经度没变
                    $locations = $amap->coordinateConvert($thisCompany['longitude'], $thisCompany['latitude']);
                    $_POST['longitude'] = $locations['longitude'];
                    $_POST['latitude'] = $locations['latitude'];
                }
                if (!$thisCompany['amapid']) {//生成ampaid
                    $ampaid = $amap->create($_POST['name'], $_POST['longitude'] . ',' . $_POST['latitude'], $_POST['tel'], $_POST['address']);
                    $_POST['amapid'] = intval($ampaid);
                } else {//修改地图的坐标
                    $amap->update($thisCompany['amapid'], $_POST['name'], $_POST['longitude'] . ',' . $_POST['latitude'], $_POST['tel'], $_POST['address']);
                }
                //
                if ($this->company_model->create()) {
                    if (empty($_POST['password'])) {
                        unset($_POST['password']);
                    }
                    if ($this->company_model->where($where)->save($_POST)) {
                        if ($this->isBranch) {
                            $this->success('修改成功', U('Company/shangjia_list', array('token' => $this->token, 'isBranch' => $this->isBranch)));
                        }
                    } else {
                        $this->error('操作失败');
                    }
                } else {
                    $this->error($this->company_model->getError());
                }
            }
        }else{
            $this->assign('set', $thisCompany);
            $this->display();
        }
    }
    public function shangjia_del() {
        $where = array('token' => $this->token, 'id' => intval($_GET['id']));
        $thisCompany = $this->company_model->where($where)->find();
        $rt = $this->company_model->where($where)->delete();
        if ($rt == true) {
            $amap = new amap();
            $amap->delete($thisCompany['amapid']);
            $this->success('删除成功', U('Company/shangjia_list', array('token' => $this->token, 'isBranch' => 2)));
        } else {
            $this->error('服务器繁忙,请稍后再试', U('Company/shangjia_list', array('token' => $this->token, 'isBranch' => 2)));
        }
    }
    
    public function delete() {
        $where = array('token' => $this->token, 'id' => intval($_GET['id']));
        $thisCompany = $this->company_model->where($where)->find();
        $rt = $this->company_model->where($where)->delete();
        if ($rt == true) {
            $amap = new amap();
            $amap->delete($thisCompany['amapid']);
            $this->success('删除成功', U('Company/branches', array('token' => $this->token, 'isBranch' => 1)));
        } else {
            $this->error('服务器繁忙,请稍后再试', U('Company/branches', array('token' => $this->token, 'isBranch' => 1)));
        }
    }

}

?>
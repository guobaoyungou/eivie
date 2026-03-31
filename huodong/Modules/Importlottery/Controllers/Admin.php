<?php
require_once MODULE_PATH.DIRECTORY_SEPARATOR.'Adminbase.php';
use Modules\Importlottery\Models\Importlottery_model;
use Modules\Prize\Controllers\Api;

class Admin extends Adminbase{
    /**
     * 构造函数
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 导入信息抽奖设置
     * 
     * @return void
     */
    public function index()
    {
        $search=isset($_GET['txt'])?strval($_GET['txt']):'';
        $page=isset($_GET['page'])?intval($_GET['page']):1;
        $pagesize=20;
        $this->setTitle('导入抽奖设置');
        $this->setDescription('在此导入您的excel信息，抽奖系统会读取excel表格的前3列信息。');
        $importlottery_model=new Importlottery_model();
        $columns_name=$importlottery_model->getColumnname();
        $data=$importlottery_model->getAllData($search,$page,$pagesize);
        $url='/Modules/module.php?m=importlottery&c=admin&a=index';
        if($search!=''){
            $url.='&txt='.$search;
        }
        $this->assign('searhtxt',$search);
        $this->assign('data',$data['data']);
        $this->assign('pagehtml', $this->pagerhtml($page, $pagesize, $data['count'], $url));
        $this->assign('columnnames',$columns_name);
        $this->show("index.html");
    }

    /**
     * 内定列表和内定设置 
     * 
     * */
    public function designated(){
        $this->setTitle('导入抽奖设置');
        $this->setDescription('导入抽奖的内定设置。');
        $prize_api=new Api();
        $designatedlist=$prize_api->getdesignatedlist('importlottery',1);
        $importlottery_model=new Importlottery_model();
        $data=$importlottery_model->getAll(true);
        $newdata=[];
        foreach($designatedlist as $k=>$v){
            $item=$this->_processdesignateddata($v,$data);
            $newdata[]=$item;
        }
        $prizes=$prize_api->getprizes('importlottery',1);
        $this->assign('data',$newdata);
        $this->assign('prizes',$prizes['data']);
        $this->show("designated.html");
    }

    public function exportdata(){
        $importlottery_model=new Importlottery_model();
        $winners=$importlottery_model->getAllWinners();
        $prize_api=new Api();
        $prizes=$prize_api->getprizes('importlottery',1);
        require_once BASEPATH.DIRECTORY_SEPARATOR.'myadmin'.DIRECTORY_SEPARATOR.'biaoqing.php';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("新云网讯网络科技有限公司")
                                    ->setLastModifiedBy("新云网讯网络科技有限公司")
                                    ->setTitle("Office 2007 XLSX 现场活动大屏幕系统导入数据抽奖中奖结果")
                                    ->setSubject("Office 2007 XLSX 现场活动大屏幕系统导入数据抽奖中奖结果")
                                    ->setDescription("现场活动大屏幕系统导入数据抽奖中奖结果.")
                                    ->setKeywords("现场活动大屏幕系统导入数据抽奖中奖结果")
                                    ->setCategory("新云网讯网络科技有限公司程序导出文件");
        $sheetindex=0;
        $oldactivityid=0;
        $currentactivityid=0;
        $rownum=2;
        $activesheet=null;
        $columns_name=$importlottery_model->getColumnname();
        $activesheet=$this->_createsheet($columns_name,$objPHPExcel);
        foreach($winners as $row){
            $this->_writedata($row,$prizes['data'],$rownum,$activesheet);
            $rownum++;
        }
        // exit();
        $title='现场活动大屏幕系统导入数据抽奖中奖结果';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$title.'.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        return;
    }
    private function _createsheet($columns_name,$excelobj){
        if($index>0){
            $excelobj->createSheet();
        }
        $activesheet=$excelobj->setActiveSheetIndex(0);
        $activesheet->setCellValue('A1', $columns_name['col_name1']);
        $activesheet->setCellValue('B1', $columns_name['col_name2']);
        $activesheet->setCellValue('C1', $columns_name['col_name3']);
        $activesheet->setCellValue('D1', '奖品名称');
        $activesheet->setCellValue('E1', '状态');
        $activesheet->setCellValue('F1', '兑奖码');
        $activesheet->setCellValue('G1', '获奖时间');
        $activesheet->setCellValue('H1', '发奖时间');
        $title='导入数据抽奖中奖结果';
        $excelobj->getActiveSheet()->setTitle($title);
        return $activesheet;
    }

    private function _writedata($row,$prizes,$rownum,$activesheet){
        $statustext=['','','未发','已发'];
        // echo var_export($prizes[$row['prizeid']]['prizename']);
        $activesheet->setCellValue('A'.$rownum, $row['col1']);
        $activesheet->setCellValue('B'.$rownum, $row['col2']);
        $activesheet->setCellValue('C'.$rownum, $row['col3']);
        $activesheet->setCellValue('D'.$rownum, $prizes[$row['prizeid']]['prizename']);
        $activesheet->setCellValue('E'.$rownum, $statustext[$row['status']]);
        $activesheet->setCellValue('F'.$rownum, $row['verifycode']);
        $activesheet->setCellValue('G'.$rownum,  date('Y-m-d H:i:s',$row['wintime']));
        $activesheet->setCellValue('H'.$rownum, empty($row['awardtime'])?'': date('Y-m-d H:i:s',$row['awardtime']));
    }  

    private function _processdesignateddata($item,$importdata){
        $item['importinfo']['col1']=$importdata[$item['userid']]['col1'];
        $item['importinfo']['col2']=$importdata[$item['userid']]['col2'];
        $item['importinfo']['col3']=$importdata[$item['userid']]['col3'];
        return $item;
    }
    /**
     * 搜索一下数据
     */
    public function ajax_act_get_data(){
        $search=isset($_GET['txt'])?strval($_GET['txt']):'';
        if($search==''){
            $returndata=['code'=>1,'message'=>'','data'=>[]];
            echo json_encode($returndata);
            return;
        }
        $importlottery_model=new Importlottery_model();
        $data=$importlottery_model->getAll(false,$search);
        $returndata=['code'=>1,'message'=>'','data'=>$data];
        echo json_encode($returndata);
        return;
    }
    /**
     * 中奖列表
     * 
     * */
    public function zjlist(){
        $this->setTitle('导入抽奖中奖结果');
        $this->setDescription('导入抽奖的中奖结果名单。');
        $prize_api=new Api();
        $winners=$prize_api->getwinners('importlottery',1,false);
        if($winners['code']>0){
            $winnerlist=$winners['data'];
            $importlottery_model=new Importlottery_model();
            $data=$importlottery_model->getAll(true);
            foreach($winnerlist as $k=>$v){
                $item=$this->_processdesignateddata($v,$data);
                $newdata[]=$item;
            }
        }
        $this->assign('data',$newdata);
        $this->show("zjlist.html");
    }

    /**
     * 获取一条数据
     */
    public function ajax_act_get_dataitem(){
        $id=isset($_GET['id'])?intval($_GET['id']):0;
        if($id<=0){
            $returndata=['code'=>-1,'message'=>'数据格式错误'];
            echo json_encode($returndata);
            return;
        }
        $importlottery_model=new Importlottery_model();
        $item=$importlottery_model->getById($id);
        if(empty($item)){
            $returndata=['code'=>-2,'message'=>'数据不存在'];
            echo json_encode($returndata);
            return;
        }
        $returndata=['code'=>1,'message'=>"",'data'=>$item];
        echo json_encode($returndata);
        return;
    }
    /**
     * 保存一条数据
     */
    public function ajax_act_save_dataitem(){
        $id=isset($_POST['id'])?intval($_POST['id']):0;
        $col1=isset($_POST['col1'])?strval($_POST['col1']):'';
        $col2=isset($_POST['col2'])?strval($_POST['col2']):'';
        $col3=isset($_POST['col3'])?strval($_POST['col3']):'';
        $imageid=0;
        if (!empty($_FILES['imagepath']['type'])) {
            //上传的文件
            $this->_load->model('Attachment_model');
            $file=$this->_load->attachment_model->saveFormFile($_FILES['imagepath']);
            $imageid=$file['id'];
        }else{
            $imageid=isset($_POST['imageid'])?strval($_POST['imageid']):0;
        }
        $importlottery_model=new Importlottery_model();
        $return = $importlottery_model->saveDataItem(['id'=>$id,'col1'=>$col1,'col2'=>$col2,'col3'=>$col3,'imgid'=>$imageid]);
        $returndata=['code'=>-1,'message'=>'保存失败'];
        if($return){
            $returndata=['code'=>1,'message'=>'保存成功'];
        }
        echo json_encode($returndata);
        return;
    }

    public function ajax_act_delete_dataitem(){
        $id=isset($_GET['id'])?intval($_GET['id']):0;
        $prize_api=new Api();
        $result=$prize_api->deleteUserPrizebyUserid('importlottery',1,$id);
        $returndata=[];
        if($result){
            $importlottery_model=new Importlottery_model();
            $return=$importlottery_model->deleteById($id);
            if($return){
                $returndata=['code'=>1,'message'=>'删除成功'];
            }else{  
                $returndata=['code'=>-1,'message'=>'删除失败'];
            }
        }else{
            $returndata=['code'=>-2,'message'=>'相关中奖信息删除失败,导致记录删除失败'];
        }
        echo json_encode($returndata);
        return;
    }
    /**
     * 保存excel文件
     * 
     */
    public function ajax_act_save_excel(){
        $file=$_FILES['fileexcel'];
        $filepath=$file['tmp_name'];
        if(!file_exists($filepath)){
            $returndata=['code'=>-1,'message'=>"文件上传出错，文件不存在"];
            echo json_encode($returndata);
            return;
        }
        $reader=PHPExcel_IOFactory::createReader("Excel2007");
        $canread=$reader->canRead($filepath);
        if(!$canread){
            $returndata=['code'=>-2,'message'=>"文件可能已经损坏了，无法读取"];
            echo json_encode($returndata);
            return;
        }
        $excel=$reader->load($filepath);
        $data=$excel->getActiveSheet()->toArray(null,false,false,false);
        if(!is_array($data) || count($data)==0){
            $returndata=['code'=>-3,'message'=>"数据读取失败，可能没有数据"];
            echo json_encode($returndata);
            return;
        }
        $column_name=array_shift($data);
        $importlottery_model=new Importlottery_model();
        $importlottery_model->saveColumnName($column_name);
        $importlottery_model->saveData($data);
        //导入完成数据后 还需恢复奖品数量
        $prize_api=new Api();
        $prize_api->restoreprize('importlottery',1);
        
        $returndata=['code'=>1,'message'=>'导入完成，请核对数据是否一致'];
        echo json_encode($returndata);
        return;
    }

    public function ajax_act_clear_userprize(){
        //导入完成数据后 还需恢复奖品数量
        $prize_api=new Api();
        $prize_api->restoreprize('importlottery',1);
        $returndata=['code'=>1,'message'=>'数据已经清空'];
        echo json_encode($returndata);
        return;
    }
}